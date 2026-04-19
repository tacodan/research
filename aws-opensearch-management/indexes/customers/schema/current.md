# Customer Schema Current State

This page captures the active customer search document contract as validated from the CRM application code and the live OpenSearch recovery and cutover work completed on `2026-04-16`.

## Status

The current customer indexing code targets schema version `6`, and the live OpenSearch write path has been cut over to match that version.

The following items are validated:

- search alias: `customers-search`
- current live write index: `customers_v6`
- current CRM indexing schema version variable: `6`
- previous active index retained for rollback: `customers_v5`
- production customer search reads remain DB-backed
- alias-based schema transitions are still the intended model
- the customer document contains top-level fields plus nested `service_addresses` and `contacts`
- the current document includes top-level `source` and `reassigned_status`
- `contacts.billing_contact` must be mapped as `integer` because the current PHP code sends `0` / `1`
- `sales_rep_name` must have `fielddata = true` because the current PHP search path sorts directly on `sales_rep_name`

## Confirmed Conventions

- Application code is expected to read from the alias instead of hard-coding a versioned index name when the OpenSearch read path is enabled.
- Schema changes are managed with versioned indexes and alias swaps.
- Production user-facing customer search still uses the DB path as of `2026-04-16`.
- Historical mappings and migration procedures are documented separately from this page.

## Live Validated Index State

- Alias: `customers-search`
- Current index: `customers_v6`
- Alias write flag: `true`
- Canonical mapping JSON: [customers_v6.mapping.json](customers_v6.mapping.json)
- Canonical Dev Tools request: [customers_v6.create-index.http](customers_v6.create-index.http)

To re-check the live mapping later, use:

```http
GET /customers_v6/_mapping
GET /_cat/aliases/customers-search?v
```

## Current Document Shape

### Top-Level Fields

| Field | Mapping | Notes |
| --- | --- | --- |
| `id` | `keyword` | Customer identifier |
| `brand` | `keyword` | Customer brand |
| `num_service_addresses` | `integer` | Count of service addresses |
| `addy_1` | `text` + `.keyword` | Primary address line |
| `city` | `text` + `.keyword` | City |
| `state` | `keyword` | State |
| `account_number` | `keyword` | Account number, nullable in code |
| `source` | `integer` | Lead source or source identifier |
| `reassigned_status` | `integer` | Reassignment flag used by customer filters |
| `sales_rep_id` | `keyword` | Sales representative identifier |
| `sales_rep_name` | `text` + `.keyword`, `fielddata=true` | Derived from cached user data; raw field is used for sorting in current PHP |
| `company_name` | `text` + `.keyword` | Company name |
| `last_updated` | `date` | Indexed as an ISO 8601 timestamp string |
| `status_state` | `keyword` | Primary status |
| `status_state_sub` | `keyword` | Secondary status |
| `service_addresses` | `nested` | Nested array of service-address objects |
| `contacts` | `nested` | Nested array of contact objects |

### Service Address Objects

Each `service_addresses[]` item is currently mapped as:

| Field | Mapping |
| --- | --- |
| `branch_id` | `keyword` |
| `address_name` | `text` + `.keyword` |
| `addy_1` | `text` + `.keyword` |
| `addy_2` | `text` + `.keyword` |
| `city` | `text` + `.keyword` |
| `state` | `keyword` |
| `zip` | `keyword` |
| `location` | `geo_point` |
| `sales_rep_id` | `keyword` |
| `service_status` | `keyword` |

### Contact Objects

Each `contacts[]` item is currently mapped as:

| Field | Mapping | Notes |
| --- | --- | --- |
| `primary_contact` | `boolean` | Current PHP casts this field to boolean |
| `first_name` | `text` + `.keyword` |  |
| `last_name` | `text` + `.keyword` |  |
| `position` | `text` + `.keyword` |  |
| `email` | `keyword` |  |
| `office_phone` | `keyword` |  |
| `office_ext` | `keyword` |  |
| `cell` | `keyword` |  |
| `billing_contact` | `integer` | Live fix applied during recovery because current PHP sends `0` / `1`, not boolean JSON |
| `addy_1` | `text` + `.keyword` |  |
| `addy_2` | `text` + `.keyword` |  |
| `city` | `text` + `.keyword` |  |
| `state` | `keyword` |  |
| `zip` | `keyword` |  |

## Recovery And Cutover Notes

These live fixes were required during the rebuild:

1. `contacts.billing_contact` was first mapped as `boolean`, but bulk indexing failed because the current PHP writes integer values. The live mapping was corrected to `integer`.
2. Sorting by the sales-rep column failed because the current PHP sorts on `sales_rep_name` instead of `sales_rep_name.keyword`. The live mapping was updated with `fielddata = true` on `sales_rep_name`.
3. Schema version `6` added top-level `source` and `reassigned_status` so the OpenSearch document contract could support the current CRM filter set.
4. A temporary dual-write migration was used to backfill `customers_v6`, after which the `customers-search` alias was moved from `customers_v5` to `customers_v6`.

These are not theoretical notes. They were observed and corrected during the live rebuild on `2026-04-16`.

## Exact Dev Tools Shape

The canonical copy-paste schema files live beside this spec:

- [customers_v6.mapping.json](customers_v6.mapping.json)
- [customers_v6.create-index.http](customers_v6.create-index.http)

The previous `customers_v5` artifacts are still kept beside them as rollback and migration references.

These files should be updated whenever the live customer index mapping changes.

## Related Docs

- [customers_v6.mapping.json](customers_v6.mapping.json)
- [customers_v6.create-index.http](customers_v6.create-index.http)
- [customers_v5.mapping.json](customers_v5.mapping.json)
- [customers_v5.create-index.http](customers_v5.create-index.http)
- [history/v1.md](history/v1.md)
- [../indexing-pipeline.md](../indexing-pipeline.md)
- [../migrations/v1-to-v2.md](../migrations/v1-to-v2.md)

## Sources

- [../../../application/crm/keptCrmOpenSearch.class.php](../../../application/crm/keptCrmOpenSearch.class.php)
- [../../../inbox/Customers Index.md](../../../inbox/Customers%20Index.md)
- [../../../inbox/Schema Migration Guide (v1 to v2).md](../../../inbox/Schema%20Migration%20Guide%20%28v1%20to%20v2%29.md)
