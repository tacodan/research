<?php
Class KeptCrmOpenSearch {
  
  function __construct() {
    
  }

  function updateCustomerIndex($debug = false) {
    global $dbi, $common;
   
    // --- CONFIGURATION ---
    $disableTextOutput = ($debug ? false : true); //if this is false then there will be echos that happen when this function runs (keep this disabled unless you are debugging)
    $schemaVersion = 5;
    $indexAlias = 'customers-search';
    $batchSize = 1000;
    $maxUpdateAttempts = 4;
    
    // --- DUAL WRITE IF WE ARE MOVING TO A NEW VERSION ---
    $dualWrite = false;
    $newSchemaVersion = 5; //update this before you change the above value to true, also make sure the new index exists first!
    $newIndexName = "customers_v".$newSchemaVersion;
    
    // --- SCRIPT LOGIC ---
    if(!$disableTextOutput) echo "Starting OS update worker...<br>";
    
    $searchService = new SearchService();
    $totalProcessed = 0;
    
    // 1. Get a batch of customer IDs that need an update
    $q = "SELECT id FROM ".CUSTOMERS_TABLE." WHERE os_requires_update = 1 AND os_update_attempts < ? LIMIT ?;";
    $s = $dbi->prepare($q);
    $s->bind_param("ii", $maxUpdateAttempts, $batchSize);
    $updateIds = $dbi->getAssoc($s);
    
    if($updateIds === 0) {
      if(!$disableTextOutput) echo "No more customers to update. Exiting.<br>";
      exit; // Exit the loop
    }

    $idsToUpdate = array_column($updateIds, 'id');
    $idList = implode(',', array_map('intval', $idsToUpdate));
    if(!$disableTextOutput) echo "Found " . count($idsToUpdate) . " customers in this batch. Processing...<br>";
    
    $batchFailed = false;
    $dbSchemaVersion = $schemaVersion;
    
    //index the documents
    $documentsToIndex = $this->getCustomerDocuments($idsToUpdate, $schemaVersion);
    if(empty($documentsToIndex)) {
      if(!$disableTextOutput) echo " - WARNING: Could not build documents for this batch. Skipping.<br>";
      $batchFailed = true;
    }
    if(!$batchFailed) {
      $response = $searchService->bulkIndex($indexAlias, $documentsToIndex);
      if(is_null($response) || (isset($response['errors']) && $response['errors'])) {
        if(!$disableTextOutput) echo " - ERROR: The bulk request failed. Check logs for details.\n";
        $batchFailed = true;
      }      
    }
    
    //if we are dual writing lets also write to the new index
    if($dualWrite && $schemaVersion !== $newSchemaVersion) {
      $dbSchemaVersion = $newSchemaVersion;
      
      $documentsToIndex = $this->getCustomerDocuments($idsToUpdate, $newSchemaVersion);
      if(empty($documentsToIndex)) {
        if(!$disableTextOutput) echo " - WARNING: Could not build documents for this batch. Skipping.<br>";
        $batchFailed = true;
      }
      if(!$batchFailed) {
        $response = $searchService->bulkIndex($newIndexName, $documentsToIndex);
        if(is_null($response) || (isset($response['errors']) && $response['errors'])) {
          if(!$disableTextOutput) echo " - ERROR: The bulk request failed. Check logs for details.\n";
          $batchFailed = true;
        }      
      }
    }
    
    // Handle success or failure for the batch
    if($batchFailed) {
      // A failure occurred, increment the attempt counter for this batch
      $q = "UPDATE ".CUSTOMERS_TABLE." SET os_update_attempts = os_update_attempts + 1 WHERE id IN ($idList);";
      $s = $dbi->prepare($q);
      $dbi->query($s);
      if(!$disableTextOutput) echo " - Incremented attempt counter for " . count($idsToUpdate) . " customers.<br>";
    }
    else {
      //Success, mark the records as updated and reset attempt counter
      $q = "UPDATE ".CUSTOMERS_TABLE." SET os_requires_update = 0, os_update_attempts = 0, os_last_update = NOW(), os_version = ? WHERE id IN ($idList);";
      $s = $dbi->prepare($q);
      $s->bind_param("i", $dbSchemaVersion);
      $dbi->query($s);
      
      $totalProcessed += count($idsToUpdate);
      if(!$disableTextOutput) echo " - Successfully indexed " . count($idsToUpdate) . " customers.<br>";
    }    
      
          
    if(!$disableTextOutput) echo "\nWorker finished. Total customers processed: $totalProcessed<br>"; 
  }
  
  private function getCustomerDocuments(array $customerIds, int $version = 0): array {
    global $dbi, $common, $sD, $dataCache;
  
    $documents = [];
  
    if(empty($customerIds)) {
      return $documents;
    }
  
    $placeholders = implode(',', array_fill(0, count($customerIds), '?'));
    $types = str_repeat('i', count($customerIds));
  
    // 1. Get base customer data
    $q = "SELECT " .
    CUSTOMERS_TABLE.".id, ".
    CUSTOMERS_TABLE.".brand, ".
    CUSTOMERS_TABLE.".num_service_addresses, ".
    CUSTOMERS_TABLE.".addy_1, ".
    CUSTOMERS_TABLE.".city, ".
    CUSTOMERS_TABLE.".state, ".
    CUSTOMERS_TABLE.".account_number, ".
    CUSTOMERS_TABLE.".source, ".
    CUSTOMERS_TABLE.".reassigned_status, ".
    CUSTOMERS_TABLE.".sales_rep_id, ".
    CUSTOMERS_TABLE.".company_name, ".
    CUSTOMERS_TABLE.".last_updated, ".
    CUSTOMERS_TABLE.".status_state, ".
    CUSTOMERS_TABLE.".status_state_sub ".
    "FROM ".CUSTOMERS_TABLE." WHERE ".CUSTOMERS_TABLE.".id IN ($placeholders);";
    
    $s = $dbi->prepare($q);
    $s->bind_param($types, ...$customerIds);
    $customerResults = $dbi->getAssoc($s);
  
    // 2. Get all related service addresses in one query
    $addressesByCustomer = [];
    $q_addresses = "SELECT customer_id, branch_id, address_name, addy_1, addy_2, city, state, zip, geo_lat, geo_lng, sales_rep_id, service_status, status FROM ".CUSTOMER_SERVICE_ADDRESS_TABLE." WHERE customer_id IN ($placeholders) and status !=0;";
    $s_addresses = $dbi->prepare($q_addresses);
    $s_addresses->bind_param($types, ...$customerIds);
    $addressResults = $dbi->getAssoc($s_addresses);
  
    if($addressResults !== 0) {
      foreach($addressResults as $address) {
        // Format for OpenSearch, including the geo_point location field
        switch($version) {
          case 0:
          default: //default will be the current version      
            $addressesByCustomer[$address['customer_id']][] = [
              'branch_id'       => $address['branch_id'],
              'address_name'    => $address['address_name'],
              'addy_1'          => $address['addy_1'],
              'addy_2'          => $address['addy_2'],
              'city'            => $address['city'],
              'state'           => $address['state'],
              'zip'             => $address['zip'],
              'location'        => ['lat' => (float)$address['geo_lat'], 'lon' => (float)$address['geo_lng']],
              'sales_rep_id'    => $address['sales_rep_id'],
              'service_status'  => $address['service_status'],
            ];
            break;
        }          
      }
    }
  
    // 3. Get all related contacts in one query
    $contactsByCustomer = [];
    $q_contacts = "SELECT customer_id, primary_contact, first_name, last_name, position, email, office_phone, office_ext, cell, billing_contact, addy_1, addy_2, city, state, zip FROM ".CUSTOMER_CONTACTS_TABLE." WHERE customer_id IN ($placeholders) and status=1;";
    $s_contacts = $dbi->prepare($q_contacts);
    $s_contacts->bind_param($types, ...$customerIds);
    $contactResults = $dbi->getAssoc($s_contacts);
  
    if($contactResults !== 0) {
      foreach($contactResults as $contact) {
        // Group contacts by their customer_id
        switch($version) {
          case 0:
          default: //default will be the current version
            $contactsByCustomer[$contact['customer_id']][] = [
              'primary_contact' => (bool)$contact['primary_contact'],
              'first_name'      => $contact['first_name'],
              'last_name'       => $contact['last_name'],
              'position'        => $contact['position'],
              'email'           => $contact['email'],
              'office_phone'    => $contact['office_phone'],
              'office_ext'      => $contact['office_ext'],
              'cell'            => $contact['cell'],
              'billing_contact' => $contact['billing_contact'],
              'addy_1'          => $contact['addy_1'],
              'addy_2'          => $contact['addy_2'],
              'city'            => $contact['city'],
              'state'           => $contact['state'],
              'zip'             => $contact['zip'],
            ];
            break;
        } 
      }
    }
  
    if($customerResults !== 0) {
      foreach($customerResults as $a => $data) {
        $osFormattedDate = null;
        if(!empty($data['last_updated']) && $data['last_updated'] > 0) {
          $date = new DateTime("@" . $data['last_updated']);
          $osFormattedDate = $date->format('c');
        }
  
        // Build the document based on the requested version
        switch($version) {
          case 6:
            $documents[] = [
              'id'                    => $data['id'],
              'brand'                 => $data['brand'],
              'num_service_addresses' => $data['num_service_addresses'],
              'addy_1'                => $data['addy_1'],
              'city'                  => $data['city'],
              'state'                 => $data['state'],
              'account_number'        => $data['account_number'] ?? null,
              'source'                => $data['source'] ?? null,
              'reassigned_status'     => $data['reassigned_status'] ?? null,
              'sales_rep_id'          => $data['sales_rep_id'],
              'sales_rep_name'        => $dataCache->user_cache_info[$data['sales_rep_id']]['fullName'],
              'company_name'          => $data['company_name'],
              'last_updated'          => $osFormattedDate,
              'status_state'          => $data['status_state'],
              'status_state_sub'      => $data['status_state_sub'],
              'service_addresses'     => $addressesByCustomer[$data['id']] ?? [],
              'contacts'              => $contactsByCustomer[$data['id']] ?? [],            
            ];
            break;
          case 0:
          default: //default will be the current version
            $documents[] = [
              'id'                    => $data['id'],
              'brand'                 => $data['brand'],
              'num_service_addresses' => $data['num_service_addresses'],
              'addy_1'                => $data['addy_1'],
              'city'                  => $data['city'],
              'state'                 => $data['state'],
              'account_number'        => $data['account_number'] ?? null,
              'sales_rep_id'          => $data['sales_rep_id'],
              'sales_rep_name'        => $dataCache->user_cache_info[$data['sales_rep_id']]['fullName'],
              'company_name'          => $data['company_name'],
              'last_updated'          => $osFormattedDate,
              'status_state'          => $data['status_state'],
              'status_state_sub'      => $data['status_state_sub'],
              'service_addresses'     => $addressesByCustomer[$data['id']] ?? [],
              'contacts'              => $contactsByCustomer[$data['id']] ?? [],            
            ];
            break;
        }
      }
    }
  
    return $documents;
  }
  
}
?>
