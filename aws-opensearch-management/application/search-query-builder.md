# SearchQueryBuilder

This document is the maintained summary of the PHP `SearchQueryBuilder` implementation and the original raw note.

## Purpose

`SearchQueryBuilder` is a fluent PHP helper for assembling OpenSearch query arrays before passing them to `SearchService`.

## Implementation Source

- Code: [shared/queryBuilder.class.php](shared/queryBuilder.class.php)
- Related connector: [shared/connector.class.php](shared/connector.class.php)
- Original note: [inbox/keptQuery Class.md](../inbox/keptQuery%20Class.md)

## Constructor And Output

- Constructor signature in code: `__construct($index, string $queryText = '')`
- The builder takes an index or alias name plus an optional global search term.
- `build()` returns a full search parameter array.
- `buildCount()` returns the query body for count-style requests without pagination or sorting.
- Default result window in code is `size = 10` and `from = 0` until pagination is set.

## Query Capabilities

### General Search

- `addSearchFields(array $fields)`
- `addNestedSearch(string $path, array $fields)`

These methods use the constructor query text and add broad search clauses.

### Required Conditions

- `addMustMatch(string $field, string $text)`
- `addMustWildcard(string $field, string $text)`
- `addMustMultiMatch(string $text, array $fields)`
- `addNestedMustMatch(string $path, string $field, string $text)`
- `addNestedMustWildcard(string $path, string $field, string $text)`

These methods add conditions that must be true for a document to match.

### Filtering

- `addFilter(string $field, $value)`
- `addTermsFilter(string $field, array $values)`
- `addTermsNotInFilter(string $field, array $values)`
- `addNestedTermsFilter(string $path, string $field, array $values)`
- `addDateRange(string $field, ?string $startDate = null, ?string $endDate = null)`
- `addDateAfter(string $field, string $date)`
- `addDateBefore(string $field, string $date)`

The source note treats filters as exact-match or range constraints that do not affect score.

### Sorting And Pagination

- `addSort(string $field, string $direction = 'asc')`
- `setPagination(int $page, int $size)`

The current code converts a one-based page number into the OpenSearch `from` offset and consistently sorts exact text values using `.keyword` sub-fields.

## Shared Usage Pattern

1. Instantiate the builder with an index alias and optional search text.
2. Add search clauses, required conditions, and filters.
3. Get a total count with `buildCount()` or `SearchService->count(...)` when the UI needs total matches.
4. Apply pagination and sorting.
5. Execute the final search through `SearchService`.

## Usage Notes

- The source note uses `customers-search` in most examples, but the builder itself is not customer-index specific.
- Nested queries require explicit nested paths.
- The note recommends the usual two-step flow for paginated UIs: count first, then fetch the current page.
- The current code supports both top-level and nested wildcard "starts with" matching.
- `addMustWildcard()` currently applies `case_insensitive = true`; `addNestedMustWildcard()` currently does not add that flag.

## Known Example Areas

The raw note includes examples for:

- general customer search
- nested contact search
- status and state filtering
- date-range filtering
- paginated DataTables integration

Keep future index-specific examples under the relevant `indexes/<name>/` docs instead of growing this page into a customer-only guide.

## Source Note

- [inbox/keptQuery Class.md](../inbox/keptQuery%20Class.md)
