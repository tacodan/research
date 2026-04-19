# Customer Index

This section contains the maintained documentation for the customer search index and its schema lifecycle.

## Current State

- Search alias: `customers-search`
- Current live index: `customers_v6`
- Alias write target: `customers_v6`
- Current CRM indexing schema version: `6`
- Production customer search reads: DB-backed
- Previous active index retained for rollback: `customers_v5`
- Current live mapping contract: documented in [schema/current.md](schema/current.md)
- Historical versioned index names in notes: `customers_v1`, `customers_v2`

Do not use the historical v1 or v1-to-v2 notes as proof of the current production schema. Treat [schema/current.md](schema/current.md) as the current live-validated contract for the rebuilt domain and the `customers_v6` cutover completed on `2026-04-16`.

## Document Map

- [schema/current.md](schema/current.md)
- [schema/customers_v6.mapping.json](schema/customers_v6.mapping.json)
- [schema/customers_v6.create-index.http](schema/customers_v6.create-index.http)
- [schema/customers_v5.mapping.json](schema/customers_v5.mapping.json)
- [schema/customers_v5.create-index.http](schema/customers_v5.create-index.http)
- [schema/history/v1.md](schema/history/v1.md)
- [indexing-pipeline.md](indexing-pipeline.md)
- [migrations/v1-to-v2.md](migrations/v1-to-v2.md)

## Source Notes

- [../../inbox/Customers Index.md](../../inbox/Customers%20Index.md)
- [../../inbox/Schema Migration Guide (v1 to v2).md](../../inbox/Schema%20Migration%20Guide%20%28v1%20to%20v2%29.md)
- [../../application/search-query-builder.md](../../application/search-query-builder.md)
- [../../application/crm/keptCrmOpenSearch.class.php](../../application/crm/keptCrmOpenSearch.class.php)
