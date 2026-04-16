# Customer Indexing Pipeline

This page captures the current customer indexing flow described by both the raw notes and the CRM application code.

## Overview

Database changes mark customer records for OpenSearch updates, a CRM worker rebuilds customer documents, and application reads happen through the alias `customers-search`.

## Implementation Sources

- CRM implementation: [../../application/crm/keptCrmOpenSearch.class.php](../../application/crm/keptCrmOpenSearch.class.php)
- Shared connector: [../../application/shared/connector.class.php](../../application/shared/connector.class.php)
- Shared builder: [../../application/shared/queryBuilder.class.php](../../application/shared/queryBuilder.class.php)

## Database Flags

The notes and current CRM code reference these customer-table fields:

- `os_requires_update`
- `os_update_attempts`
- `os_last_update`
- `os_version`

The intended pattern is:

- normal customer changes set `os_requires_update = 1`
- the worker looks for flagged records
- once indexing succeeds, the worker clears the update flag, resets attempts, stamps `os_last_update`, and records the schema version in `os_version`

## Trigger Semantics From The Source Note

The source note includes this trigger logic:

```sql
IF (OLD.os_requires_update = 1 AND NEW.os_requires_update = 0) THEN
    SET NEW.os_update_attempts = NEW.os_update_attempts;
ELSE
    SET NEW.os_requires_update = 1;
END IF;
```

Operationally, this reads as:

- if the worker is clearing the update flag, do not immediately re-flag the row
- otherwise, customer changes should mark the row as needing an OpenSearch refresh

## Worker Flow

The current CRM code in `updateCustomerIndex()` runs this flow:

1. Select a batch of customer IDs where `os_requires_update = 1` and `os_update_attempts < 4`.
2. Build documents through `getCustomerDocuments(...)`.
3. Bulk index them to the alias `customers-search`.
4. If indexing fails, increment `os_update_attempts` for the batch.
5. If indexing succeeds, clear the update flag, reset attempts, stamp `os_last_update`, and write the schema version into `os_version`.

The current code also keeps a dual-write path for controlled schema migrations.

## Current Code-Level Configuration

These values are confirmed from the CRM code currently stored in this project:

- current schema version variable: `5`
- write alias: `customers-search`
- batch size: `1000`
- max update attempts: `4`
- dual write default: `false`
- new schema version variable: `5`

Treat these as the current application-code settings. They still need live-environment validation before being treated as production truth.

## Document Build Inputs

`getCustomerDocuments(...)` currently builds each customer document from:

- base customer fields from the main customer table
- active service addresses from `CUSTOMER_SERVICE_ADDRESS_TABLE`
- active contacts from `CUSTOMER_CONTACTS_TABLE`
- cached sales-rep name data from `$dataCache`

The resulting document shape is described in [schema/current.md](schema/current.md).

## Alias Write Path

- Application reads should target `customers-search`.
- Normal write behavior in the current CRM code targets the alias `customers-search`.
- Controlled migration mode can also write directly to a versioned index such as `customers_v5`.
- Versioned indexes should otherwise be treated as implementation detail except during schema migration work.

## Full Reindex Lifecycle

The notes and code imply this lifecycle:

1. Mark records as needing update.
2. Let the worker rebuild and index documents.
3. Confirm the queue is drained.
4. Use a dedicated migration procedure if the target schema version changes.

Example full reindex SQL from the migration note:

```sql
UPDATE customers SET os_requires_update = 1, os_update_attempts = 0;
```

## Source Note Cleanup

- The raw v1 note contains an Obsidian-style pasted-image reference that is not available in this project.
- This page keeps the inline SQL logic from that note and treats the missing image as an unresolved source artifact, not part of the canonical docs.

## Related Docs

- [schema/current.md](schema/current.md)
- [schema/history/v1.md](schema/history/v1.md)
- [migrations/v1-to-v2.md](migrations/v1-to-v2.md)

## Sources

- [../../application/crm/keptCrmOpenSearch.class.php](../../application/crm/keptCrmOpenSearch.class.php)
- [../../inbox/Customers Index.md](../../inbox/Customers%20Index.md)
- [../../inbox/Schema Migration Guide (v1 to v2).md](../../inbox/Schema%20Migration%20Guide%20%28v1%20to%20v2%29.md)
