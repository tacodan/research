# Customer Indexing Pipeline

This page captures the note-based view of how customer records are flagged, rebuilt, and written into OpenSearch. Treat it as the maintained process summary, not a substitute for validating the live worker code.

## Overview

The source notes describe a pipeline where database changes mark customer records for OpenSearch updates, a worker rebuilds customer documents, and application reads happen through the alias `customers-search`.

## Database Flags

The notes reference these customer-table fields:

- `os_requires_update`
- `os_update_attempts`

The intended pattern is:

- normal customer changes set `os_requires_update = 1`
- the worker looks for flagged records
- once indexing succeeds, the worker clears the update flag

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

Based on the source notes, the worker process is:

1. Query for customers flagged for OpenSearch update.
2. Build customer documents through `getCustomerDocuments(...)`.
3. Write documents to the active customer search target.
4. Clear or update the queue-tracking fields in the database.

The migration note adds a version-aware mode where the worker can build a new schema version and dual-write during a live migration.

## Alias Write Path

- Application reads should target `customers-search`.
- Normal write behavior should follow the active alias unless a controlled migration requires dual writing.
- Versioned indexes such as `customers_v1` and `customers_v2` should be treated as implementation detail except during schema migration work.

## Full Reindex Lifecycle

The notes imply this baseline lifecycle:

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

## Source Notes

- [../../inbox/Customers Index.md](../../inbox/Customers%20Index.md)
- [../../inbox/Schema Migration Guide (v1 to v2).md](../../inbox/Schema%20Migration%20Guide%20%28v1%20to%20v2%29.md)
