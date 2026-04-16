# Customer Schema Current State

This page is reserved for the active customer search document contract.

## Status

The current live customer schema version is not confirmed by the available notes.

The source material confirms that the customer index uses:

- the alias `customers-search`
- versioned physical indexes such as `customers_v1` and `customers_v2`
- alias-based schema transitions

The source material does not confirm which schema version is currently live.

## Confirmed Conventions

- Application code is expected to read from the alias instead of hard-coding a versioned index name.
- Schema changes are managed with versioned indexes and alias swaps.
- Historical mappings and migration procedures are documented separately from this page.

## Confirmation Needed

Fill in these items only after checking the live domain or the source application code:

- Active versioned index behind `customers-search`
- Approved field list for the active document
- Field types and multi-field strategy
- Any nested objects currently in use
- Required derived fields added after v1

## Related Docs

- [history/v1.md](history/v1.md)
- [../indexing-pipeline.md](../indexing-pipeline.md)
- [../migrations/v1-to-v2.md](../migrations/v1-to-v2.md)

## Source Notes

- [../../../inbox/Customers Index.md](../../../inbox/Customers%20Index.md)
- [../../../inbox/Schema Migration Guide (v1 to v2).md](../../../inbox/Schema%20Migration%20Guide%20%28v1%20to%20v2%29.md)
