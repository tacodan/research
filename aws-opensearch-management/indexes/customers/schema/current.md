# Customer Schema Current State

This page captures the active customer search document contract as confirmed by the CRM application code currently stored in this project.

## Status

The current CRM code targets schema version `5`.

The following items are confirmed from the code:

- the alias `customers-search`
- a schema version variable set to `5`
- alias-based schema transitions
- a customer document with top-level fields plus nested `service_addresses` and `contacts`

The live alias target in OpenSearch has not yet been independently validated from the domain itself, so this page reflects current application-code intent.

## Confirmed Conventions

- Application code is expected to read from the alias instead of hard-coding a versioned index name.
- Schema changes are managed with versioned indexes and alias swaps.
- Historical mappings and migration procedures are documented separately from this page.

## Current Document Shape

### Top-Level Fields

| Field | Notes |
| --- | --- |
| `id` | Customer identifier |
| `brand` | Customer brand |
| `num_service_addresses` | Count of service addresses |
| `addy_1` | Primary address line |
| `city` | City |
| `state` | State |
| `account_number` | Account number, nullable in code |
| `sales_rep_id` | Sales representative identifier |
| `sales_rep_name` | Derived from cached user data |
| `company_name` | Company name |
| `last_updated` | Indexed as an ISO 8601 timestamp string |
| `status_state` | Primary status |
| `status_state_sub` | Secondary status |
| `service_addresses` | Nested array of service-address objects |
| `contacts` | Nested array of contact objects |

### Service Address Objects

Each `service_addresses[]` item currently includes:

- `branch_id`
- `address_name`
- `addy_1`
- `addy_2`
- `city`
- `state`
- `zip`
- `location` with `lat` and `lon`
- `sales_rep_id`
- `service_status`

### Contact Objects

Each `contacts[]` item currently includes:

- `primary_contact`
- `first_name`
- `last_name`
- `position`
- `email`
- `office_phone`
- `office_ext`
- `cell`
- `billing_contact`
- `addy_1`
- `addy_2`
- `city`
- `state`
- `zip`

## Still Needs Validation

These items are still not confirmed from a live mapping export:

- Active versioned index currently behind `customers-search`
- Field mappings and analyzers for the version 5 index
- Exact nested mappings for `service_addresses` and `contacts`
- Any additional derived fields added outside `getCustomerDocuments(...)`

## Related Docs

- [history/v1.md](history/v1.md)
- [../indexing-pipeline.md](../indexing-pipeline.md)
- [../migrations/v1-to-v2.md](../migrations/v1-to-v2.md)

## Sources

- [../../../application/crm/keptCrmOpenSearch.class.php](../../../application/crm/keptCrmOpenSearch.class.php)
- [../../../inbox/Customers Index.md](../../../inbox/Customers%20Index.md)
- [../../../inbox/Schema Migration Guide (v1 to v2).md](../../../inbox/Schema%20Migration%20Guide%20%28v1%20to%20v2%29.md)
