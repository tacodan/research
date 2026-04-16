# OpenSearch: Creating the Customer Index (v1)

This document outlines the process for creating version 1 of the `customers` search index in OpenSearch using the Dev Tools within OpenSearch Dashboards. This initial version uses a flat structure based on data from the primary customer table.

***
## Field Definitions

The following fields will be included in the v1 customer document.

| Field | Description |
| :--- | :--- |
| `id` | The unique customer ID from the primary database. |
| `num_service_addresses` | The total count of active service addresses for the customer. |
| `addy_1` | The primary street address line. |
| `city` | The city of the primary address. |
| `state` | The two-letter state or province code. |
| `sales_rep_id`| The ID of the assigned sales representative. |
| `company_name`| The legal name of the customer's company. |
| `last_updated`| The timestamp of the last modification to the customer record. |
| `status_state`| The primary status of the customer account (e.g., Prospect, Active). |
| `status_state_sub` | The secondary, more detailed status of the account. |

***
## Mapping Strategy and Field Types

The mapping defines how data in each field is stored and indexed. The choices below are optimized for the intended search functionality.

### `keyword` Type
This type is used for data that you want to filter, sort, or aggregate based on its exact value. It is not analyzed or broken into smaller words.

* **Fields:** `id`, `state`, `sales_rep_id`, `status_state`, `status_state_sub`
* **Reason:** These fields represent unique identifiers or specific codes. You will perform queries like `WHERE state = 'TX'` or sort by `sales_rep_id`.

### `text` and `keyword` (Multi-field)
This approach is used for text that needs to be searchable in a flexible, full-text manner, but also requires exact-match sorting or filtering.

* **Fields:** `company_name`, `addy_1`, `city`
* **Reason:** The main field is mapped as `text` to allow users to search for parts of a name (e.g., "Global" matches "Global Corporation"). A sub-field, accessed as `company_name.keyword`, is mapped as `keyword` to allow for precise alphabetical sorting and filtering.

### `integer` Type
This is used for whole numbers.

* **Field:** `num_service_addresses`
* **Reason:** Mapping this as a numeric type allows for range queries (e.g., customers with more than 5 addresses) and proper numeric sorting.

### `date` Type
This type is optimized for chronological data.

* **Field:** `last_updated`
* **Reason:** It allows for powerful date-range filtering (e.g., customers updated in the last 30 days). The application code must convert all database timestamps to a standard ISO 8601 format (e.g., `2025-07-28T12:05:06Z`) before indexing.

***
## Implementation Steps

Run the following commands in the OpenSearch Dashboards **Dev Tools**.

### 1. Create the Index with Mapping
This command creates the index with a versioned name (`customers_v1`) and applies the mapping defined above.

```json
PUT /customers_v1
{
  "mappings": {
    "properties": {
      "id": { "type": "keyword" },
      "num_service_addresses": { "type": "integer" },
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
      "state": { "type": "keyword" },
      "sales_rep_id": { "type": "keyword" },
      "company_name": {
        "type": "text",
        "fields": {
          "keyword": {
            "type": "keyword",
            "ignore_above": 256
          }
        }
      },
      "last_updated": { "type": "date" },
      "status_state": { "type": "keyword" },
      "status_state_sub": { "type": "keyword" }
    }
  }
}
```

### 2. Create the Search Alias
This command creates a user-friendly alias (`customers-search`) that points to our new versioned index. Your application should always read from and write to this alias.

```json
POST /_aliases
{
  "actions": [
    {
      "add": {
        "index": "customers_v1",
        "alias": "customers-search"
      }
    }
  ]
}
```

### 3. Verify the Index and Alias
Run these commands to confirm that the index, mapping, and alias were created successfully.


**Check indices:**
```
GET /_cat/indices?v
``` 

**Check the mapping:** 
```
GET /customers_v1/_mapping
```

**Check the alias:** 
```
GET /_cat/aliases?v
```

### MySQL update (triggers for updating)

![[Pasted image 20250729111314.png]]

The definition above is as follows:
```sql
IF (OLD.os_requires_update = 1 AND NEW.os_requires_update = 0) THEN
    SET NEW.os_update_attempts = NEW.os_update_attempts;
ELSE
    SET NEW.os_requires_update = 1;
END IF;
```
