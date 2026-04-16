# Customer Migration v1 To v2

This page captures the historical zero-downtime migration procedure described in the raw note for moving the customer alias from `customers_v1` to `customers_v2`.

## Status

This is a documented migration pattern, not proof that the migration has already been completed in production.

## Preconditions

- The application reads through the `customers-search` alias.
- A new versioned index can be created alongside the current one.
- The worker can build customer documents for more than one schema version.

## Procedure

### 1. Create The New Index

- Start from the existing mapping, using `GET /customers_v1/_mapping`.
- Define the new `customers_v2` mapping with the intended schema changes.
- Create the new index before any alias swap.

### 2. Prepare The Application Code

- Update the customer document builder to support a v2 document shape.
- Update the worker configuration so it can dual-write during the migration window.

The source note describes the migration as adding `status_state_sub_text`, but the sample PHP snippet uses a placeholder `new_field`. Treat the process as authoritative and the field name in the example as illustrative only.

### 3. Deploy And Flag All Records

- Deploy the updated PHP code.
- Mark all customer rows for reindexing:

```sql
UPDATE customers SET os_requires_update = 1, os_update_attempts = 0;
```

### 4. Run The Worker

- Let the worker process the reindex queue.
- During the migration, the worker writes to both the current alias target and the new versioned index.
- Wait until the queue is drained before moving the alias.

### 5. Swap The Alias

Use an atomic alias update once the new index is fully populated:

```json
POST /_aliases
{
  "actions": [
    { "remove": { "index": "customers_v1", "alias": "customers-search" } },
    { "add":    { "index": "customers_v2", "alias": "customers-search" } }
  ]
}
```

### 6. Cleanup

- Validate the application against the new alias target.
- Delete the old index when it is no longer needed.
- Remove temporary dual-write logic from the worker.

## Related Docs

- [../schema/current.md](../schema/current.md)
- [../indexing-pipeline.md](../indexing-pipeline.md)

## Source Note

- [../../../inbox/Schema Migration Guide (v1 to v2).md](../../../inbox/Schema%20Migration%20Guide%20%28v1%20to%20v2%29.md)
