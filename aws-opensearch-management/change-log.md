# OpenSearch Change Log

Use this file as the reverse-chronological log for changes and notable events affecting this single AWS OpenSearch deployment.

## 2026-04-16

### Live Domain Rebuilt

- Recreated the customer index on the new OpenSearch domain as `customers_v5`.
- Recreated the `customers-search` alias and marked `customers_v5` as the write index.
- Rebuilt IAM role access in Dashboards with `crm_app_role` mapped to `arn:aws:iam::794148609003:role/fw-prod-instance-role`.
- Corrected the live mapping so `contacts.billing_contact` is `integer`.
- Enabled `fielddata` on `sales_rep_name` so the existing PHP sort path works without code changes.
- Verified successful bulk indexing from the CRM application into the rebuilt domain.
- Added canonical schema artifacts for the rebuilt customer index as both JSON and Dev Tools copy-paste files.

### Docs Updated From Code

- Updated the shared query-builder documentation to match the current PHP implementation, including wildcard support and pagination behavior.
- Updated the customer indexing pipeline docs to reflect the current CRM worker logic, queue fields, and schema version settings.
- Updated the customer current-state schema docs to reflect the current code-confirmed version 5 document shape.

### Documentation Reorganized

- Kept the raw source notes in `inbox/` unchanged as source captures.
- Added curated docs for IAM auth, shared query-builder behavior, and customer index documentation.
- Split customer material into current-state, historical schema, indexing pipeline, and migration docs.
- Marked the live customer schema version as unconfirmed until validated from production or code.

### Documentation Initialized

- Created the management folder structure.
- Added starter files for inventory, runbook, and change tracking.
- Instance-specific AWS details still need to be filled in.

## Entry Template

Copy this section for each new event and place it above older entries.

### YYYY-MM-DD

#### Short Title

- Change or incident:
- Reason:
- Impact:
- Validation:
- Follow-up:
