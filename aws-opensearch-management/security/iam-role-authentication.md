# IAM Role Authentication

This document captures the current note-based process for granting an AWS IAM role access to indexes in this OpenSearch domain.

## Purpose

Use OpenSearch security roles to define index permissions inside the domain, then map an AWS IAM role to that OpenSearch role as a backend role.

## Current Pattern

1. Create or update an OpenSearch role in Dashboards.
2. Assign index permissions to the role for the required index patterns.
3. Map the AWS IAM role ARN to that OpenSearch role as a backend role.
4. Validate that the application can access only the intended indexes.

## OpenSearch Role Setup

Create the application role inside OpenSearch Dashboards:

1. Sign in to OpenSearch Dashboards with an account that can manage security roles.
2. Go to `Security` -> `Roles`.
3. Create a role for the application workload.
4. Add index permissions for the required index patterns.
5. Save the role.

## Live Validated Setup

The current rebuilt domain was configured with:

- OpenSearch role name: `crm_app_role`
- Cluster permission used: `cluster_composite_ops`
- Index pattern used: `customers*`
- Index action group used: `crud`

This setup was validated by successful bulk indexing from the CRM application on `2026-04-16`.

## IAM Role Mapping

After the OpenSearch role exists:

1. Open the role in Dashboards.
2. Go to the mapped-users view.
3. Add the AWS IAM role ARN under backend roles.
4. Save the mapping.

The intended result is passwordless access for workloads already running under that IAM role.

## Live Validated Mapping

The current rebuilt domain was mapped to this backend role:

- `arn:aws:iam::794148609003:role/fw-prod-instance-role`

That ARN was entered under `Backend roles`, not under `Users`.

## Access Boundaries

- The permission model is defined inside OpenSearch, not in IAM alone.
- IAM grants identity; OpenSearch role mappings grant index-level access.
- The mapped role should be scoped to the smallest set of index patterns and actions the application needs.
- Record the steady-state role names, index patterns, and owners in `inventory.md`.

## Source Note

- [inbox/General Setup Notes.md](../inbox/General%20Setup%20Notes.md)
