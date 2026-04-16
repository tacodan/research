This document provides a comprehensive guide to using the `SearchQueryBuilder` PHP class. This class is a fluent interface designed to programmatically build complex query arrays for OpenSearch in a clean and readable way.

***
## Table of Contents
* [General Usage](#General%20Usage)
* [Function Reference](#Function%20Reference)
  * [Constructor](#Constructor)
  * [General Search](#General%20Search)
    * [addSearchFields()](#addSearchFields())
    * [addNestedSearch()](#addNestedSearch())
  * [Required Conditions](#Required%20Conditions)
    * [addMustMatch()](#addMustMatch())
    * [addMustMultiMatch()](#addMustMultiMatch())
    * [addNestedMustMatch()](#addNestedMustMatch())
    * [addNestedMustWildcard()](#addNestedMustWildcard())
  * [Filtering](#Filtering)
    * [addFilter()](#addFilter())
    * [addTermsFilter()](#addTermsFilter())
    * [addTermsNotInFilter()](#addTermsNotInFilter())
    * [addNestedTermsFilter()](#addNestedTermsFilter())
    * [addDateRange()](#addDateRange())
    * [addDateAfter()](#addDateAfter())
    * [addDateBefore()](#addDateBefore())
  * [Sorting & Pagination](#Sorting%20&%20Pagination)
    * [addSort()](#addSort())
    * [setPagination()](#setPagination())
  * [Building the Query](#Building%20the%20Query)
    * [build()](#build())
    * [buildCount()](#buildcount)
* [Common Workflows](#Common%20Workflows)
  * [Pagination with Total Count](#Pagination%20With%20Total%20Count)
* [Combined Examples](#Combined%20Examples)
  * [Power Search](#Power%20Search)
  * [Filtered and Sorted Report](#Filtered%20and%20Sorted%20Report)

***
## General Usage
The primary purpose of this class is to abstract away the complexity of writing raw JSON queries for OpenSearch. The standard workflow is to instantiate the class, call methods to build the query, and finally call the `build()` method to get the parameter array, which is then passed to the `SearchService`.

### Basic Workflow
```php
// 1. Instantiate the builder for a specific index and with an optional global search term.
$builder = new SearchQueryBuilder('customers-search', 'Acme');

// 2. Call methods to add clauses to the query.
$builder->addSearchFields(['company_name']);
$builder->addFilter('state', 'CA');
$builder->addSort('company_name.keyword', 'asc');

// 3. Build the final parameter array.
$params = $builder->build();

// 4. Pass the parameters to the search service.
$results = $searchService->search($builder); // Assuming the service takes the builder object
```
---
## Function Reference

This section details each public method available in the class.

### Constructor

Initializes a new query builder instance.

**Signature:** `public function __construct($index, string $queryText = '')`

**Usage:**
```php
// For a general search against the 'customers' index for the text "Global"
$builder = new SearchQueryBuilder('customers-search', 'Global');

// For building a query with only specific filters (no global search term)
$builder = new SearchQueryBuilder('customers-search', '');
```

### General Search

These methods are used for broad, full-text searches. They are only active if a `$queryText` is provided in the constructor. Results will match if any (`should`) of these clauses are true.

#### addSearchFields()

Searches for the constructor's `$queryText` across multiple top-level fields.

**Signature:** `public function addSearchFields(array $fields): self`

**Example:**

```php
$builder = new SearchQueryBuilder('customers-search', 'Main Street');
$builder->addSearchFields(['company_name', 'addy_1']);
```

#### addNestedSearch()

Searches for the constructor's `$queryText` within fields of a nested object.

**Signature:** `public function addNestedSearch(string $path, array $fields): self`

**Example:**

```php
$builder = new SearchQueryBuilder('customers-search', 'John Smith');
$builder->addNestedSearch('contacts', ['contacts.name']);
```

### Required Conditions

These methods add conditions that **must** all be true for a document to match, similar to using `AND` in a SQL query.

#### addMustMatch()

Requires that a single field contains a specific text.

**Signature:** `public function addMustMatch(string $field, string $text): self`

**Example:**

```php
$builder = new SearchQueryBuilder('customers-search', '');
// Find documents where the company_name contains "Acme"
$builder->addMustMatch('company_name', 'Acme');
```

#### addMustMultiMatch()

Requires that a specific text is found in at least one of the provided fields.

**Signature:** `public function addMustMultiMatch(string $text, array $fields): self`

**Example:**

```php
$builder = new SearchQueryBuilder('customers-search', '');
// Find documents where "active" appears in either the status or a note field
$builder->addMustMultiMatch('active', ['status_state', 'notes']);
```

#### addNestedMustMatch()

Requires that a specific text is found within a field of a nested object.

**Signature:** `public function addNestedMustMatch(string $path, string $field, string $text): self`

**Example:**

```php
$builder = new SearchQueryBuilder('customers-search', '');
// Find documents that have a contact named "Jane"
$builder->addNestedMustMatch('contacts', 'contacts.name', 'Jane');
```

#### addNestedMustWildcard()

Requires that a field within a nested object starts with a specific string of text.

This is a specialized version of a nested "must" clause that uses an OpenSearch `wildcard` query. It's particularly useful for "starts with" or autocomplete-style functionality within nested data. Note that wildcard queries can be less performant than `match` queries on very large datasets.

**Signature:** `public function addNestedMustWildcard(string $path, string $field, string $text): self`

**Example:**

```php
$builder = new SearchQueryBuilder('customers-search', '');
// Find customers who have a contact whose first name starts with "J"
$builder->addNestedMustWildcard(
    'contacts',                 // The path to the nested object
    'contacts.first_name',      // The field to search
    'J'                         // The text it must start with
);
```

**Resulting OpenSearch Query:**

```json
{
  "query": {
    "bool": {
      "must": [
        {
          "nested": {
            "path": "contacts",
            "query": {
              "wildcard": {
                "contacts.first_name": {
                  "value": "J*"
                }
              }
            }
          }
        }
      ]
    }
  }
}
```

### Filtering

Filters are "yes/no" questions that do not affect the score of the results. They are very fast and are cached by OpenSearch.

#### addFilter()

Filters for documents where a field has an exact value. This is the equivalent of `WHERE status = 'Active'`.

**Signature:** `public function addFilter(string $field, $value): self`

**Example:**

```php
$builder = new SearchQueryBuilder('customers-search', '');
// Find customers where the state is exactly 'CA'
$builder->addFilter('state', 'CA');
```

#### addTermsFilter()

Filters for documents where a field matches any value in a provided array. This is the equivalent of `WHERE status_state IN (1, 2, 3)`.

**Signature:** `public function addTermsFilter(string $field, array $values): self`

**Example:**

```php
$builder = new SearchQueryBuilder('customers-search', '');
// Find customers with a specific set of status codes
$builder->addTermsFilter('status_state', [1, 5, 8]);
```

#### addTermsNotInFilter()

Filters for documents where a field does not match any value in a provided array. This is the equivalent of a SQL `WHERE status NOT IN (...)` clause.

**Signature:** `public function addTermsNotInFilter(string $field, array $values): self`

**Example:**
```php
$builder = new SearchQueryBuilder('customers-search', '');

// Find customers whose status is NOT 1 or 2
$builder->addTermsNotInFilter('status_state', [1, 2]);
````

**Resulting OpenSearch Query:**

```json
{
  "query": {
    "bool": {
      "filter": [
        {
          "bool": {
            "must_not": [
              {
                "terms": {
                  "status_state": [1, 2]
                }
              }
            ]
          }
        }
      ]
    }
  }
}
```

#### addNestedTermsFilter()

Filters for documents where a field *within a nested object* matches any value in a provided array.

This is the correct way to perform an `IN (...)` style query on a nested field. A standard `addTermsFilter()` will not work correctly because OpenSearch needs to know that you are filtering within the scope of a single nested sub-document. This function wraps the `terms` query in a `nested` clause to preserve that context.

**Signature:** `public function addNestedTermsFilter(string $path, string $field, array $values): self`

**Example:**

```php
$builder = new SearchQueryBuilder('customers-search', '');
// Find customers who have at least one service address in either Texas ('TX') or California ('CA')
$builder->addNestedTermsFilter(
    'service_addresses',          // The path to the nested object
    'service_addresses.state',    // The full path to the field you are filtering on
    ['TX', 'CA']                  // The array of state values to match
);
```

**Resulting OpenSearch Query:**

```json
{
  "query": {
    "bool": {
      "filter": [
        {
          "nested": {
            "path": "service_addresses",
            "query": {
              "terms": {
                "service_addresses.state": ["TX", "CA"]
              }
            }
          }
        }
      ]
    }
  }
}
```

#### addDateRange()

Filters for documents where a date field falls within a given range. Start and end dates are optional and inclusive. OpenSearch date math (e.g., `now-7d`) is supported.

**Signature:** `public function addDateRange(string $field, ?string $startDate = null, ?string $endDate = null): self`

**Example:**

```php
$builder = new SearchQueryBuilder('customers-search', '');
// Find customers updated during June 2025
$builder->addDateRange('last_updated', '2025-06-01', '2025-06-30');
```

#### addDateAfter()

A convenience method to find documents where a date is after a certain point in time.

**Signature:** `public function addDateAfter(string $field, string $date): self`

**Example:**

```php
$builder = new SearchQueryBuilder('customers-search', '');
// Find customers updated in the last 7 days
$builder->addDateAfter('last_updated', 'now-7d');
```

#### addDateBefore()

A convenience method to find documents where a date is before a certain point in time.

**Signature:** `public function addDateBefore(string $field, string $date): self`

**Example:**

```php
$builder = new SearchQueryBuilder('customers-search', '');
// Find customers created before 2024
$builder->addDateBefore('created_date', '2024-01-01');
```

### Sorting & Pagination

#### addSort()

Adds a sort order to the query. Can be called multiple times for multi-level sorting. For exact-match sorting on text fields, use the `.keyword` sub-field.

**Signature:** `public function addSort(string $field, string $direction = 'asc'): self`

**Example:**

```php
$builder = new SearchQueryBuilder('customers-search', '');
// Sort alphabetically by company name, then by last updated descending
$builder->addSort('company_name.keyword', 'asc');
$builder->addSort('last_updated', 'desc');
```

#### setPagination()

Sets the `size` (results per page) and `from` (offset) for pagination.

**Signature:** `public function setPagination(int $page, int $size): self`

**Example:**

```php
$builder = new SearchQueryBuilder('customers-search', '');
// Get the third page of results, with 25 results per page
$builder->setPagination(3, 25);
```

### Building the Query

#### build()

Assembles all the method calls into the final array that the OpenSearch client expects.

**Signature:** `public function build(): array`

**Example:**

```php
$params = $builder->build();
// $params is now a complete array, e.g., ['index' => 'customers', 'body' => [...]]
```

#### buildCount()

Assembles only the query clauses into a body suitable for the `_count` API. It ignores pagination and sorting.
**Signature:** `public function buildCount(): array`

---

## Common Workflows

### Pagination with Total Count

OpenSearch has a default limit of 10,000 documents for standard pagination. To display the total number of results to the user while efficiently fetching pages, the best practice is to use a two-query approach.

1.  **Get the Total Count:** First, use the `buildCount()` method with a corresponding `searchService->count()` method to get the total number of documents that match the filters. This query is fast because it doesn't retrieve any documents.
2.  **Get the Page Results:** Then, use the same builder instance, add pagination and sorting, and call the `build()` method with `searchService->search()` to get the actual documents for the current page.

This pattern provides the information needed for the UI (e.g., "Showing 1-10 of 4,500 results") while keeping the data retrieval fast and scalable.

#### Example 1: Basic Usage

```php
// Instantiate the search service
$searchService = new SearchService();

// 1. Create and configure the builder with filters
$builder = new SearchQueryBuilder('customers-search', '');
$builder->addFilter('state', 'CA');
$builder->addMustMatch('company_name', 'Acme');

// 2. Get the total count for the UI
$totalRecords = $searchService->count($builder);
echo "Total matching records: " . $totalRecords;

// 3. Add pagination and sorting for the current page view
$builder->setPagination(1, 10);
$builder->addSort('company_name.keyword', 'asc');

// 4. Get the actual results for the page
$results = $searchService->search($builder);
print_r($results);
```

#### Example 2: DataTables Integration

This shows how you would integrate with a system like DataTables that provides `start` and `length`.

```php
// Values from DataTables
$start = $_POST['start'];
$length = $_POST['length'];
$searchTerm = $_POST['search']['value'];

// Instantiate the search service and builder
$searchService = new SearchService();
$builder = new SearchQueryBuilder('customers-search', $searchTerm);
$builder->addSearchFields(['company_name', 'city', 'addy_1']);

// Get total count for DataTables' "recordsFiltered" property
$totalFiltered = $searchService->count($builder);

// Calculate page number and set pagination/sorting
$page = ($start / $length) + 1;
$builder->setPagination($page, $length);
$builder->addSort('company_name.keyword', 'asc');

// Get the data for the current page for DataTables' "data" property
$results = $searchService->search($builder);

// Return JSON response to DataTables...
```

-----

## Combined Examples

Here are some examples that combine multiple methods to build more complex queries.

### Power Search

**Goal:** Find customers in Texas (`TX`) or California (`CA`) where the company name contains "Service", and they also have a contact named "Smith".

**PHP Code:**

```php
$builder = new SearchQueryBuilder('customers-search', '');

$builder->addTermsFilter('state', ['TX', 'CA']);
$builder->addMustMatch('company_name', 'Service');
$builder->addNestedMustMatch('contacts', 'contacts.name', 'Smith');

$params = $builder->build();
```

**Resulting OpenSearch Query:**

```json
{
  "index": "customers-search",
  "body": {
    "query": {
      "bool": {
        "must": [
          { "match": { "company_name": "Service" } },
          { "nested": { "path": "contacts", "query": { "match": { "contacts.name": "Smith" } } } }
        ],
        "filter": [
          { "terms": { "state": ["TX", "CA"] } }
        ]
      }
    },
    "size": 10,
    "from": 0
  }
}
```

### Filtered and Sorted Report

**Goal:** Get the first page of 50 active customers who were last updated in the past month, sorted by company name.

**PHP Code:**

```php
$builder = new SearchQueryBuilder('customers-search', '');

$builder->addFilter('status_state', 'Active');
$builder->addDateAfter('last_updated', 'now-1M');
$builder->addSort('company_name.keyword', 'asc');
$builder->setPagination(1, 50);

$params = $builder->build();
```

**Resulting OpenSearch Query:**

```json
{
  "index": "customers-search",
  "body": {
    "query": {
      "bool": {
        "filter": [
          { "term": { "status_state": "Active" } },
          { "range": { "last_updated": { "gte": "now-1M" } } }
        ]
      }
    },
    "size": 50,
    "from": 0,
    "sort": [
      { "company_name.keyword": { "order": "asc" } }
    ]
  }
}
```