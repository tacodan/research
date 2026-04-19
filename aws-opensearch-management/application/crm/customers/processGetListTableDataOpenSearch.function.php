	<?php

  function processGetListTableDataOpenSearch($post) {
    global $session, $dbi, $sD, $common, $bD, $dataCache;
        
    //TODO:
    //1. make sure that the length is a reasonable number (maybe even hard code it)
    
    //this needs to be returned as is, we cast it to int for security
    $draw = (int) $post['draw'];
    $retVal['draw'] = $draw;
    $reqStart = (int) $post['start'];
    //$reqLength = (int) $post['length'];
    $reqLength = 10;
    $reqOrderCol = (int) $post['order'][0]['column'];
    $reqOrderDirection = ($post['order'][0]['dir'] == "desc" ? "desc" : "asc");
    
    $builder = new SearchQueryBuilder('customers-search', '');
    //$searchService = new SearchService();
    //$results = $searchService->search($builder);
    
    //lets pull some data from the database
    //$q = "select count(*) from ".CUSTOMERS_TABLE.";";
    //$s = $dbi->prepare($q);
    //$totalResult = $dbi->getSingle($s);
    //$numTotalRecords = $totalResult['count(*)'];
        
    $numActiveCustomFilters = 0;
    $leftJoin = "";
    $filterSql = "";
    $bindTypes = "";
    $bindValues = array();
    
    //custom filtering
    
    //customer type filtering
    if(isset($post['statusStateFilter']) && $post['statusStateFilter'] != "0") {
      switch($post['statusStateFilter']) {
        case 30: //Prospect:All Prospects
          //$filterSql .= CUSTOMERS_TABLE.".status_state = ? and ";
          //$bindTypes .= "i";
          //$bindValues[] = 2; 
          //$numActiveCustomFilters ++;
          $builder->addFilter("status_state", 2);
          break;
        case 60: //Prospect:Needs Quote Reviewed and Sent
          //$filterSql .= CUSTOMERS_TABLE.".status_state = ? and ".CUSTOMERS_TABLE.".sales_rep_id=? and ";
          //$bindTypes .= "ii";
          //$bindValues[] = 2; 
          //$bindValues[] = $session->uid; 
          //$numActiveCustomFilters ++;
          $builder->addFilter("status_state", 2);
          $builder->addFilter("sales_rep_id", $session->uid);
          break;        
        case 32: //Prospect:Needs Quoting
          //$filterSql .= CUSTOMERS_TABLE.".status_state_sub = ? and ";
          //$bindTypes .= "i";
          //$bindValues[] = 32; 
          //$numActiveCustomFilters ++;
          $builder->addFilter("status_state_sub", 32);
          break;
        case 31: //Prospect:Quote Sent
          //$filterSql .= CUSTOMERS_TABLE.".status_state_sub = ? and ";
          //$bindTypes .= "i";
          //$bindValues[] = 31; 
          //$numActiveCustomFilters ++;
          $builder->addFilter("status_state_sub", 31);
          break;
        case 33: //Prospect:Needs Re-Quoting
          //$filterSql .= CUSTOMERS_TABLE.".status_state_sub = ? and ";
          //$bindTypes .= "i";
          //$bindValues[] = 33; 
          //$numActiveCustomFilters ++;
          $builder->addFilter("status_state_sub", 33);
          break;          
        case 35: //Prospect:Dead
          //$filterSql .= CUSTOMERS_TABLE.".status_state_sub = ? and ";
          //$bindTypes .= "i";
          //$bindValues[] = 35; 
          //$numActiveCustomFilters ++;
          $builder->addFilter("status_state_sub", 35);
          break;
        case 49: //Customer:All Customers
          //$filterSql .= CUSTOMERS_TABLE.".status_state = ? and ";
          //$bindTypes .= "i";
          //$bindValues[] = 3;
          //$numActiveCustomFilters ++;
          $builder->addFilter("status_state", 3);
          break;
        case 61: //Customer:Assigned to Me
          //$filterSql .= CUSTOMERS_TABLE.".status_state = ? and ".CUSTOMERS_TABLE.".sales_rep_id=? and ";
          //$bindTypes .= "ii";
          //$bindValues[] = 3;
          //$bindValues[] = $session->uid;
          //$numActiveCustomFilters ++;
          $builder->addFilter("status_state", 3);
          $builder->addFilter("sales_rep_id", $session->uid);
          break;
        case 50: //Customer:Needs New Quote
          //$filterSql .= CUSTOMERS_TABLE.".status_state_sub = ? and ";
          //$bindTypes .= "i";
          //$bindValues[] = 50;
          //$numActiveCustomFilters ++;
          $builder->addFilter("status_state_sub", 50);
          break;          
        case 52: //Customer:Needs Scheduling
          //$filterSql .= CUSTOMERS_TABLE.".status_state_sub = ? and ";
          //$bindTypes .= "i";
          //$bindValues[] = 52;
          //$numActiveCustomFilters ++;
          $builder->addFilter("status_state_sub", 52);
          break;          
        case 54: //Customer:Scheduled
          //$filterSql .= CUSTOMERS_TABLE.".status_state_sub = ? and ";
          //$bindTypes .= "i";
          //$bindValues[] = 54;
          //$numActiveCustomFilters ++;
          $builder->addFilter("status_state_sub", 54);
          break;          
        case 55: //Customer:Service Suspended
          //$filterSql .= CUSTOMERS_TABLE.".status_state_sub = ? and ";
          //$bindTypes .= "i";
          //$bindValues[] = 55;
          //$numActiveCustomFilters ++;
          $builder->addFilter("status_state_sub", 55);
          break;
        case 57: //Customer:Dead
          //$filterSql .= CUSTOMERS_TABLE.".status_state_sub = ? and ";
          //$bindTypes .= "i";
          //$bindValues[] = 57;
          //$numActiveCustomFilters ++;
          $builder->addFilter("status_state_sub", 57);
          break;
        case 70: //All:Has Service Addresses Needing Quote
          //$filterSql .= CUSTOMERS_TABLE.".id in (select distinct ".CUSTOMER_SERVICE_ADDRESS_TABLE.".customer_id from ".CUSTOMER_SERVICE_ADDRESS_TABLE." where ".CUSTOMER_SERVICE_ADDRESS_TABLE.".service_status=0 and ".CUSTOMER_SERVICE_ADDRESS_TABLE.".status=1) and ".CUSTOMERS_TABLE.".num_service_addresses !=0 and ".CUSTOMERS_TABLE.".status_state_sub not in (35,57) and ";
          //$numActiveCustomFilters ++;
          
          //block out dead quotes
          $builder->addTermsNotInFilter('status_state_sub', [35, 57]);          
          
          $builder->addNestedTermsFilter(
            'service_addresses',                // The path to the nested object
            'service_addresses.service_status', // The full path to the field you are filtering on
            ['0']                               // The array of state values to match
          );          
          break;          
        case 71: //All:Reassigned to Me
          $builder->addFilter("sales_rep_id", $session->uid);
          $builder->addFilter("reassigned_status", 1);
          break;
      }
    }
    
    //assigned user filtering
    if($post['mainFilterDD'] == "1" && isset($post['assignedFilterDD']) && $post['assignedFilterDD'] != "0" && $post['statusStateFilter'] != 12) {
      //$filterSql .= CUSTOMERS_TABLE.".sales_rep_id = ? and ";
      //$bindTypes .= "i";
      //$bindValues[] = (int) $post['assignedFilterDD']; //TODO: we should do some strlen checking on this
      //$numActiveCustomFilters ++;
      $builder->addFilter("sales_rep_id", (int) $post['assignedFilterDD']);
    }
    
    //customer name filtering
    if($post['mainFilterDD'] == "2" && isset($post['customerNameFilter']) && $post['customerNameFilter'] != "") {
      //$filterSql .= CUSTOMERS_TABLE.".company_name like ? and ";
      //$bindTypes .= "s";
      //$bindValues[] = "%" . str_replace("%", "", $post['customerNameFilter']) . "%";
      //$numActiveCustomFilters ++;
      $builder->addMustContainsWildcard('company_name.keyword', $post['customerNameFilter']);
    }
    
    //customer addy1 filtering
    if($post['mainFilterDD'] == "3" && isset($post['addressFilter']) && $post['addressFilter'] != "") {
      //$filterSql .= CUSTOMERS_TABLE.".addy_1 like ? and ";
      //$bindTypes .= "s";
      //$bindValues[] = "%" . str_replace("%", "", $post['addressFilter']) . "%";
      //$numActiveCustomFilters ++;
      $builder->addMustContainsWildcard('addy_1.keyword', $post['addressFilter']);
    }
    
    //customer city filtering
    if($post['mainFilterDD'] == "5" && isset($post['cityFilter']) && $post['cityFilter'] != "") {
      //$filterSql .= CUSTOMERS_TABLE.".city like ? and ";
      //$bindTypes .= "s";
      //$bindValues[] = "%" . str_replace("%", "", $post['cityFilter']) . "%";
      //$numActiveCustomFilters ++;
      $builder->addMustContainsWildcard('city.keyword', $post['cityFilter']);
    }
    
    //state filtering
    if($post['mainFilterDD'] == "6" && isset($post['stateFilterDD']) && $post['stateFilterDD'] != "0") {
      //$filterSql .= CUSTOMERS_TABLE.".state = ? and ";
      //$bindTypes .= "s";
      //$bindValues[] = $post['stateFilterDD']; //TODO: we should do some strlen checking on this
      //$numActiveCustomFilters ++;
      $builder->addFilter('state', $post['stateFilterDD']);
    }
    
    //customer zip filtering
    if($post['mainFilterDD'] == "7" && isset($post['zipFilter']) && $post['zipFilter'] != "") {
      //$filterSql .= CUSTOMERS_TABLE.".zip like ? and ";
      //$bindTypes .= "s";
      //$bindValues[] = "%" . str_replace("%", "", $post['zipFilter']) . "%";
      //$numActiveCustomFilters ++;
      $builder->addNestedMustWildcard('service_addresses', 'service_addresses.zip', $post['zipFilter']);
    }
    
    //lead source filtering
    if($post['mainFilterDD'] == "8" && isset($post['leadSourceFilterDD']) && $post['leadSourceFilterDD'] != "0") {
      //$filterSql .= CUSTOMERS_TABLE.".source = ? and ";
      //$bindTypes .= "i";
      //$bindValues[] = $post['leadSourceFilterDD'];
      //$numActiveCustomFilters ++;

      $builder->addFilter('source', $post['leadSourceFilterDD']);
    }
    
    //branch filtering
    if($post['mainFilterDD'] == "11" && isset($post['branchFilterDD']) && $post['branchFilterDD'] != "0") {
      //$leftJoin .= " left join ".CUSTOMER_SERVICE_ADDRESS_TABLE." on (".CUSTOMERS_TABLE.".id = ".CUSTOMER_SERVICE_ADDRESS_TABLE.".customer_id) ";
      //$filterSql .= "(".CUSTOMERS_TABLE.".branch_id = ? or ".CUSTOMER_SERVICE_ADDRESS_TABLE.".branch_id = ?) and ";
      //$bindTypes .= "ii";
      //$bindValues[] = $post['branchFilterDD'];
      //$bindValues[] = $post['branchFilterDD'];
      //$numActiveCustomFilters ++;
      $builder->addNestedTermsFilter(
        'service_addresses',          // The path to the nested object
        'service_addresses.branch_id',    // The full path to the field you are filtering on
        [$post['branchFilterDD']]                  // The array of state values to match
        //['TX', 'CA']                  // The array of state values to match
      );      
    }
    
    //customer number filtering
    if($post['mainFilterDD'] == "12" && isset($post['customerNumberFilter']) && $post['customerNumberFilter'] != "") {
      //$filterSql .= CUSTOMERS_TABLE.".account_number like ? and ";
      //$bindTypes .= "s";
      //$bindValues[] = "%" . str_replace("%", "", $post['customerNumberFilter']) . "%";
      //$numActiveCustomFilters ++;
      
      $builder->addMustContainsWildcard('account_number', $post['customerNumberFilter']);
    }    
    
    //add in left join for users first names (needing the ability to sort by first name)
    //$leftJoin .= " left join ".USER_TABLE." on (".USER_TABLE.".id = ".CUSTOMERS_TABLE.".sales_rep_id) ";
    
    
    //brand filtering
    //$filterSql .= CUSTOMERS_TABLE.".brand = ? and ";
    //$bindTypes .= "i";
    //$bindValues[] = $bD->brandID;
    //$numActiveCustomFilters ++;
    $builder->addFilter("brand", $bD->brandID);    
        
    //$filterSql = ($numActiveCustomFilters > 0 ? " where ".$filterSql : $filterSql);
    //$filterSql = ($numActiveCustomFilters > 0 ? rtrim($filterSql, " and ") : $filterSql);
    
    //we might need to get the filtered total, if so we do that here
    /*
    if($numActiveCustomFilters > 0) {
      $q = "select count(distinct ".CUSTOMERS_TABLE.".id) from ".CUSTOMERS_TABLE.$leftJoin.$filterSql.";";
      $s = $dbi->prepare($q);
      $s->bind_param($bindTypes, ...$bindValues);
      $totalFilteredResults = $dbi->getSingle($s);
      $numFiltered = $totalFilteredResults['count(distinct '.CUSTOMERS_TABLE.'.id)'];
    }
    */
    
    $searchService = new SearchService();
    
    //get total filtered records
    $numTotalRecords = $searchService->count($builder);
    
    //add pagination
    $displayPage = ($reqStart / $reqLength) + 1;
    $builder->setPagination($displayPage, $reqLength);
    
    //add sort
    switch($reqOrderCol) {
      case 0:
        //$orderSql = " order by ".CUSTOMERS_TABLE.".company_name ".$reqOrderDirection;
        $builder->addSort('company_name.keyword', $reqOrderDirection);
        break;
      case 2:
        //$orderSql = " order by ".USER_TABLE.".first_name ".$reqOrderDirection;
        $builder->addSort('sales_rep_name', $reqOrderDirection);
        break;
      case 3:
        //$orderSql = " order by ".CUSTOMERS_TABLE.".last_updated ".$reqOrderDirection;
        $builder->addSort('last_updated', $reqOrderDirection);
        break;
      //case 0:
      default:
        //$orderSql = " order by ".CUSTOMERS_TABLE.".last_updated ".$reqOrderDirection;
        $builder->addSort('last_updated', $reqOrderDirection);
        break;
    }        
    
    //get results
    $results = $searchService->search($builder);    
    //$numFiltered = $results['total'];
    
    //die($q);
    
    //add in left join for users first names (needing the ability to sort by first name)
    //$leftJoin .= " left join ".USER_TABLE." on (".USER_TABLE.".id = ".CUSTOMERS_TABLE.".sales_rep_id) ";
    
    //manuall add the pager limits
    //$bindTypes .= "ii";
    //$bindValues[] = $reqStart;
    //$bindValues[] = $reqLength;
    
    
    //$q = "select ".CUSTOMERS_TABLE.".*, ".USER_TABLE.".first_name from ".CUSTOMERS_TABLE.$leftJoin.$filterSql." group by ".CUSTOMERS_TABLE.".id ".$orderSql." limit ?, ?;";
    
    //die($q);
    //$s = $dbi->prepare($q);
    //$s->bind_param($bindTypes, ...$bindValues);
    //$customerResults = $dbi->getAssoc($s);
    
    //if(!is_null($results)) {
      //$common->printArray($results);
      //die();
    //}
    
    if(!is_null($results)) {
      foreach($results['documents'] as $a => $data) {
        
        //stop giving all data, only provide what is exactly needed
        //$finalData[$a] = $data; 
        
        $finalData[$a]['id'] = $data['id'];
        $finalData[$a]['num_service_addresses'] = $data['num_service_addresses'];
        $finalData[$a]['addy_1'] = $data['addy_1'];
        $finalData[$a]['city'] = $data['city'];
        $finalData[$a]['state'] = $data['state'];
        $finalData[$a]['sales_rep_id'] = $data['sales_rep_id'];
        $finalData[$a]['company_name'] = $common->handyCaps($data['company_name']);        
        
        $unix_last_updated = strtotime($data['last_updated']);
        $finalData[$a]['printLastUpdateText'] = $common->formatDate($unix_last_updated, 13, true);
        $finalData[$a]['printLastUpdateAgoText'] = $common->timeElapsed($unix_last_updated, 7, true);
        
        $finalData[$a]['printRepGroup'] = $dataCache->user_cache_info[$data['sales_rep_id']]['b_group_text'];
        $finalData[$a]['printRepClass'] = $dataCache->user_cache_info[$data['sales_rep_id']]['b_group_class'];
        $finalData[$a]['printRepInitals'] = $dataCache->user_cache_info[$data['sales_rep_id']]['initals'];
        //$finalData[$a]['printRepName'] = $dataCache->user_cache_info[$data['sales_rep_id']]['first_name'].' '.$dataCache->user_cache_info[$data['sales_rep_id']]['last_name'];
        $finalData[$a]['printRepName'] = $data['sales_rep_name'];
        $finalData[$a]['printRepProfileImg'] = $dataCache->user_cache_info[$data['sales_rep_id']]['profile_img'];        
        $finalData[$a]['printStatusStateText'] = $sD->customerStatusState[$data['status_state']]['name'];
        $finalData[$a]['printStatusStateClass'] = $sD->customerStatusState[$data['status_state']]['class'];        
        $finalData[$a]['printStatusStateSubText'] = $sD->customerStatusStateSub[$data['status_state_sub']]['name'];
        $finalData[$a]['printStatusStateSubClass'] = $sD->customerStatusStateSub[$data['status_state_sub']]['class'];
        $finalData[$a]['profile_portrait_url'] = CF_RESOURCE_URL."/staff-avatars/".$dataCache->user_cache_info[$data['sales_rep_id']]['profile_img'];
        //$finalData[$a]['printCustomerType'] = $sD->customerTypes[$data['customer_type']]['name'];
        //$finalData[$a]['printCustomerType'] = "Commercial";
      }
    }

    //$retVal['recordsTotal'] = $numTotalRecords;
    //$retVal['recordsFiltered'] = ($numActiveCustomFilters > 0 ? $numFiltered : $numTotalRecords);
    $retVal['recordsTotal'] = $numTotalRecords;
    $retVal['recordsFiltered'] = $numTotalRecords;
    
    $retVal['data'] = $finalData;
    echo json_encode($retVal);
		exit;
  }
