# OpenSearch Inventory

This file is for stable, domain-level facts about this single AWS OpenSearch deployment.

## Domain Identity

- Environment:
- Domain name:
- Domain ARN:
- AWS account ID:
- AWS region:
- Engine/version:
- Deployment type:

## Endpoints

- OpenSearch endpoint:
- OpenSearch Dashboards endpoint:
- Custom endpoint, if any:

## Network Placement

- VPC ID:
- Subnets:
- Security groups:
- Access path:
- Public access enabled:

## Access And Ownership

- Primary owner:
- Secondary owner:
- IAM roles used for administration:
- IAM roles or users used for application access:
- SSO or federated access details:
- Resource policy location:
- Related auth documentation: [security/iam-role-authentication.md](security/iam-role-authentication.md)

## Data Protection

- At-rest encryption:
- Node-to-node encryption:
- Fine-grained access control:
- Snapshot repository or automated snapshots:
- Retention notes:

## Related Resources

- CloudWatch alarms:
- CloudWatch log groups:
- SNS topics or paging destinations:
- KMS key:
- Terraform or IaC reference:

## Boundaries

- Keep only durable domain facts here.
- Keep step-by-step procedures in `runbook.md`.
- Keep index-specific schema, pipeline, and migration details under `indexes/`.
- Record changes over time in `change-log.md`.
