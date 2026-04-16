# AWS OpenSearch Management

This folder is the managed documentation workspace for one AWS OpenSearch deployment and its current application search integration.

## Current Goal

Turn the external notes in `inbox/` into maintained, canonical docs without losing the original source material.

## Quick Facts

- Environment:
- AWS account:
- AWS region:
- OpenSearch domain name:
- OpenSearch domain ARN:
- OpenSearch endpoint:
- OpenSearch Dashboards endpoint:
- Primary owner:
- Secondary owner:

## Navigation

### Operations

- [inventory.md](inventory.md)
- [runbook.md](runbook.md)
- [change-log.md](change-log.md)

### Curated Docs

- [security/iam-role-authentication.md](security/iam-role-authentication.md)
- [application/search-query-builder.md](application/search-query-builder.md)
- [indexes/customers/README.md](indexes/customers/README.md)

### Raw Source Notes

- [inbox/General Setup Notes.md](inbox/General%20Setup%20Notes.md)
- [inbox/Customers Index.md](inbox/Customers%20Index.md)
- [inbox/keptQuery Class.md](inbox/keptQuery%20Class.md)
- [inbox/Schema Migration Guide (v1 to v2).md](inbox/Schema%20Migration%20Guide%20%28v1%20to%20v2%29.md)

## Inbox Policy

- Files in `inbox/` are preserved as source captures.
- Canonical docs live outside `inbox/`.
- Curated docs should link back to the source note they came from.
- Do not treat raw notes as the published structure for ongoing maintenance.

## Document Status

| Area | Status | Notes |
| --- | --- | --- |
| Domain inventory | Starter | Needs live domain values and ownership details. |
| Operations runbook | Starter | Needs production commands, alarm names, and recovery steps. |
| IAM role auth | Curated | Distilled from the general setup note. |
| SearchQueryBuilder | Curated | Updated against the shared PHP implementation and the original application note. |
| Customer schema current state | Code-confirmed | Current CRM code targets schema version 5; live alias target still needs domain validation. |
| Customer schema v1 history | Curated | Historical v1 mapping and creation flow extracted from the source note. |
| Customer indexing pipeline | Curated | Worker, flags, alias write path, and reindex lifecycle documented from both notes and CRM code. |
| Customer v1 to v2 migration | Curated | Historical zero-downtime migration procedure extracted from the source note. |

## Next Update

- Fill in the live domain metadata in `inventory.md`.
- Validate the live alias target in OpenSearch against the code-documented schema version 5.
- Add approved operational commands and alarm names to `runbook.md`.
- Add dated entries to `change-log.md` whenever access, schema, or migration docs change.
