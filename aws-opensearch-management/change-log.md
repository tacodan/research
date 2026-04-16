# OpenSearch Change Log

Use this file as the reverse-chronological log for changes and notable events affecting this single AWS OpenSearch deployment.

## 2026-04-16

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
