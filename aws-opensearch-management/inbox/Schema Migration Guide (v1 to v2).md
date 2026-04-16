
This document outlines the process for performing a zero-downtime schema migration for the customer index using index aliases. This allows the application to remain fully operational while a new index version is built and populated in the background.

The example scenario is adding a new field, `status_state_sub_text`, to the customer document.

***

## Step 1: Create the New Index (`customers_v2`)

First lets get the mappings from the old index using the following command in the dev tools:

```json
GET /customers_v1/_mapping
```

It will return something like this:

```json
{
  "customers_v1": {
    "mappings": {
      "properties": {
        "addy_1": {
          "type": "text",
          "fields": {
            "keyword": {
              "type": "keyword",
              "ignore_above": 256
            }
          }
        },
        "city": {
          "type": "text",
          "fields": {
            "keyword": {
              "type": "keyword",
              "ignore_above": 256
            }
          }
        },
        "company_name": {
          "type": "text",
          "fields": {
            "keyword": {
              "type": "keyword",
              "ignore_above": 256
            }
          }
        },
        "id": {
          "type": "keyword"
        },
        "last_updated": {
          "type": "date"
        },
        "num_service_addresses": {
          "type": "integer"
        },
        "sales_rep_id": {
          "type": "keyword"
        },
        "state": {
          "type": "keyword"
        },
        "status_state": {
          "type": "keyword"
        },
        "status_state_sub": {
          "type": "keyword"
        }
      }
    }
  }
}
```

We can use this as the base to modify the index and make any field changes we want, when we get it modified we can create the new index with the new fields like this:

```json
PUT /customers_v2
{
  "mappings": {
    "properties": {
      "id": { "type": "keyword" },
      "num_service_addresses": { "type": "integer" },
      "addy_1": {
        "type": "text",
        "fields": { "keyword": { "type": "keyword", "ignore_above": 256 } }
      },
      "city": {
        "type": "text",
        "fields": { "keyword": { "type": "keyword", "ignore_above": 256 } }
      },
      "state": { "type": "keyword" },
      "sales_rep_id": { "type": "keyword" },
      "company_name": {
        "type": "text",
        "fields": { "keyword": { "type": "keyword", "ignore_above": 256 } }
      },
      "last_updated": { "type": "date" },
      "status_state": { "type": "keyword" },
      "status_state_sub": { "type": "keyword" }
    }
  }
}
```

---

## Step 2: Prepare the Application Code

Modify your PHP application to handle both the v1 and v2 document schemas.

### A. Update the Document Builder
In your `getCustomerDocuments` function, add a `case 2:` to the `switch` statement to build the new, enriched document.

```php
function getCustomerDocuments(array $customerIds, int $version = 1): array {
  global $dbi, $sD, $dataCache;
  // ... (rest of the function setup) ...

  if($customerResults !== 0) {
    foreach($customerResults as $a => $data) {
      // ... (date formatting and other transformations) ...
      $statusStateSubText = $sD->customerStatusStateSub[$data['status_state_sub']]['name'] ?? null;

      switch($version) {
        case 2:
          // Version 2 document with the new field
          $documents[] = [
            'id'                    => $data['id'],
            'num_service_addresses' => $data['num_service_addresses'],
            'addy_1'                => $data['addy_1'],
            'city'                  => $data['city'],
            'state'                 => $data['state'],
            'sales_rep_id'          => $data['sales_rep_id'],
            'company_name'          => $data['company_name'],
            'last_updated'          => $osFormattedDate,
            'status_state'          => $data['status_state'],
            'new_field'             => $newStuffHere, // New field
          ];
          break;
        case 0:
        default:
          // Version 1 document (unchanged)
          $documents[] = [
            'id'                    => $data['id'],
            'num_service_addresses' => $data['num_service_addresses'],
            'addy_1'                => $data['addy_1'],
            'city'                  => $data['city'],
            'state'                 => $data['state'],
            'sales_rep_id'          => $data['sales_rep_id'],
            'company_name'          => $data['company_name'],
            'last_updated'          => $osFormattedDate,
            'status_state'          => $data['status_state'],
            'status_state_sub'      => $data['status_state_sub'],
          ];
          break;
      }
    }
  }

  return $documents;
}
```

### B. Implement "Dual Writing" in the Worker

Modify the config of the worker to set `$dualWrite = true` and update the `$newSchemaVersion`.

PHP

```php
  // --- DUAL WRITE IF WE ARE MOVING TO A NEW VERSION ---
  $dualWrite = false;
  $newSchemaVersion = 2;
  $newIndexName = "customers_v".$newSchemaVersion;
```

---

## Step 3: Deploy and Flag All Records

1. **Deploy** your updated PHP files to your server. From this moment on, any live customer updates will be written to both `customers_v1` and `customers_v2`, keeping them in sync.
    
2. **Flag** all existing customer records for a full re-index. Run this SQL query on your database:
    
    SQL
    
    ```
    UPDATE customers SET os_requires_update = 1, os_update_attempts = 0;
    ```
    

---

## Step 4: Run the Migration Worker

Execute your `worker.php` script from the command line. The script will now:

1. Find all flagged records.
    
2. Build the new **v2** documents using the updated `getCustomerDocuments` function.
    
3. Index them into both `customers_v1` (via the alias) and `customers_v2` (directly).
    
4. Mark the records as updated in the database.
    

Let the worker run until the queue of customers needing updates is empty.

---

## Step 5: Atomically Swap the Alias

This is the zero-downtime switch. Once the worker has finished, run this command in **Dev Tools**. It instantly points the `customers-search` alias from the old index to the new one.

JSON

```
POST /_aliases
{
  "actions": [
    { "remove": { "index": "customers_v1", "alias": "customers-search" } },
    { "add":    { "index": "customers_v2", "alias": "customers-search" } }
  ]
}
```

Your application is now using `customers_v2` for all operations.

---

## Step 6: Cleanup

After you have verified that your application is stable and working correctly with the new index:

1. **Delete the old index** to free up disk space.
    
    ```
    DELETE /customers_v1
    ```
    
2. **Remove the dual-writing code** from your worker script, as it is no longer needed.