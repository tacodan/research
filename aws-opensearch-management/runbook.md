# OpenSearch Runbook

This file is for repeatable operational procedures for this single AWS OpenSearch deployment.

## Operational Boundaries

- Keep durable domain facts in `inventory.md`.
- Keep IAM role setup details in [security/iam-role-authentication.md](security/iam-role-authentication.md).
- Keep customer index schema and migration procedures under `indexes/customers/`.

## Access

- Required AWS account or role:
- Access method:
- VPN, bastion, or network prerequisites:
- Dashboards login path:

## Default Health Checks

Use these as the baseline checks until live commands and thresholds are filled in:

1. Confirm the domain status in AWS.
2. Check cluster health.
3. Check storage, CPU, JVMMemoryPressure, and cluster status alarms.
4. Confirm Dashboards access if it is part of normal operations.
5. Record abnormal findings in `change-log.md`.

## Common Procedures

### Review Domain Health

- Check cluster status and recent alarms.
- Confirm node count and storage headroom.
- Review recent indexing or query error trends.
- Note whether any issue is domain-wide or isolated to a single index.

### Review Or Change Access

- Record the requested change and requester.
- Confirm the IAM role, backend-role mapping, or resource policy update.
- Validate access after the change.
- Update `inventory.md` if the steady-state access model changed.
- Log the change in `change-log.md`.

### Prepare For Maintenance

- Confirm recent backups or automated snapshots.
- Identify expected user impact.
- Save current settings that may need rollback.
- Log the maintenance window and outcome.

## Incident Checklist

- Identify the start time and observed symptoms.
- Check AWS service health and domain status.
- Review CloudWatch alarms and recent configuration changes.
- Check access policy, network path, and authentication dependencies.
- If the issue is index-specific, continue with the relevant docs under `indexes/`.
- Capture commands, findings, and recovery steps in `change-log.md`.

## Backup And Recovery

- Snapshot method:
- Snapshot frequency:
- Restore prerequisites:
- Recovery validation steps:

## Open Questions

- Which checks should become the official daily or weekly routine?
- What is the approved recovery objective for this domain?
- Which alarms should trigger immediate paging?
