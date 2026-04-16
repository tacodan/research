# Customer Index

This section contains the maintained documentation for the customer search index and its schema lifecycle.

## Current State

- Search alias confirmed in code: `customers-search`
- Current CRM code schema version: `5`
- Historical versioned index names in notes: `customers_v1`, `customers_v2`
- Current application-code field contract: documented in [schema/current.md](schema/current.md)
- Live alias target in OpenSearch: still needs independent validation

Do not use the historical v1 or v1-to-v2 notes as proof of the current production schema. Treat [schema/current.md](schema/current.md) as the current code-confirmed contract and validate the live domain separately when needed.

## Document Map

- [schema/current.md](schema/current.md)
- [schema/history/v1.md](schema/history/v1.md)
- [indexing-pipeline.md](indexing-pipeline.md)
- [migrations/v1-to-v2.md](migrations/v1-to-v2.md)

## Source Notes

- [../../inbox/Customers Index.md](../../inbox/Customers%20Index.md)
- [../../inbox/Schema Migration Guide (v1 to v2).md](../../inbox/Schema%20Migration%20Guide%20%28v1%20to%20v2%29.md)
- [../../application/search-query-builder.md](../../application/search-query-builder.md)
- [../../application/crm/keptCrmOpenSearch.class.php](../../application/crm/keptCrmOpenSearch.class.php)
