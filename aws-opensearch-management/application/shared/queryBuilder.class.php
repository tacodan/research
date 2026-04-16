<?php
class SearchQueryBuilder {
  private $index;
  private string $queryText;
  private array $searchClauses = [];
  private array $mustClauses = [];
  private array $filterClauses = [];
  private array $sortClauses = [];
  private int $size = 10;
  private int $from = 0;

  public function __construct($index, string $queryText = '') {
    $this->index = $index;
    $this->queryText = $queryText;
  }

  public function addSearchFields(array $fields): self {
    if(!empty($this->queryText)) {
      $this->searchClauses[] = ['multi_match' => ['query' => $this->queryText, 'fields' => $fields]];
    }
    return $this;
  }

  public function addNestedSearch(string $path, array $fields): self {
    if(!empty($this->queryText)) {
      $this->searchClauses[] = ['nested' => ['path' => $path, 'query' => ['multi_match' => ['query' => $this->queryText, 'fields' => $fields]]]];
    }
    return $this;
  }

  public function addMustMatch(string $field, string $text): self {
    $this->mustClauses[] = ['match' => [$field => $text]];
    return $this;
  }

  public function addMustWildcard(string $field, string $text): self {
    // Note: Wildcard queries can be slow if used on very large datasets without care.
    // We add the '*' automatically to create a "starts with" search.
    $this->mustClauses[] = [
      'wildcard' => [
        $field => [
          'value' => $text . '*',
          'case_insensitive' => true
        ]
      ]
    ];
    return $this;    
  }

  public function addMustContainsWildcard(string $field, string $text): self {
    $text = trim(str_replace(['*', '?'], '', $text));
    if($text === '') {
      return $this;
    }

    $this->mustClauses[] = [
      'wildcard' => [
        $field => [
          'value' => '*' . $text . '*',
          'case_insensitive' => true
        ]
      ]
    ];
    return $this;
  }
  
  public function addMustMultiMatch(string $text, array $fields): self {
    $this->mustClauses[] = ['multi_match' => ['query' => $text, 'fields' => $fields]];
    return $this;
  }  

  public function addNestedMustMatch(string $path, string $field, string $text): self {
    $this->mustClauses[] = ['nested' => ['path' => $path, 'query' => ['match' => [$field => $text]]]];
    return $this;
  }
  
  public function addNestedMustWildcard(string $path, string $field, string $text): self {
    $this->mustClauses[] = [
      'nested' => [
        'path' => $path,
        'query' => [
          'wildcard' => [
            $field => [
              'value' => $text . '*'
            ]
          ]
        ]
      ]
    ];
    return $this;
  }  
  
  public function addDateRange(string $field, ?string $startDate = null, ?string $endDate = null): self {
    $rangeQuery = [];
    if(!is_null($startDate)) {
      $rangeQuery['gte'] = $startDate;
    }
    if(!is_null($endDate)) {
      $rangeQuery['lte'] = $endDate;
    }
    
    if(!empty($rangeQuery)) {
      $this->filterClauses[] = ['range' => [$field => $rangeQuery]];
    }
    return $this;
  }

  public function addDateAfter(string $field, string $date): self {
    return $this->addDateRange($field, $date, null);
  }

  public function addDateBefore(string $field, string $date): self {
    return $this->addDateRange($field, null, $date);
  }  

  public function addFilter(string $field, $value): self {
    $this->filterClauses[] = ['term' => [$field => $value]];
    return $this;
  }
  
  public function addTermsFilter(string $field, array $values): self {
    $this->filterClauses[] = ['terms' => [$field => $values]];
    return $this;
  }
  
  public function addTermsNotInFilter(string $field, array $values): self {
    $this->filterClauses[] = [
      'bool' => [
        'must_not' => [
          ['terms' => [$field => $values]]
        ]
      ]
    ];
    return $this;
  }  
  
  public function addNestedTermsFilter(string $path, string $field, array $values): self {
    $this->filterClauses[] = [
      'nested' => [
        'path' => $path,
        'query' => [
          'terms' => [
            $field => $values
          ]
        ]
      ]
    ];
    return $this;
  }  

  public function setPagination(int $page, int $size): self {
    $this->size = $size;
    $this->from = ($page > 0) ? ($page - 1) * $size : 0;
    return $this;
  }

  public function addSort(string $field, string $direction = 'asc'): self {
    $this->sortClauses[] = [$field => ['order' => $direction]];
    return $this;
  }

  public function buildCount(): array {
    $body = [];
    $query = ['bool' => []];

    if(!empty($this->searchClauses)) {
      $query['bool']['should'] = $this->searchClauses;
      $query['bool']['minimum_should_match'] = 1;
    }

    if(!empty($this->mustClauses)) {
      $query['bool']['must'] = $this->mustClauses;
    }

    if(!empty($this->filterClauses)) {
      $query['bool']['filter'] = $this->filterClauses;
    }

    if(count(array_filter(array_keys($query['bool']))) > 0) {
      $body['query'] = $query;
    }

    return [
      'index' => $this->index,
      'body' => $body
    ];
  }

  public function build(): array {
    $body = [];
    $query = ['bool' => []];

    if(!empty($this->searchClauses)) {
      $query['bool']['should'] = $this->searchClauses;
      $query['bool']['minimum_should_match'] = 1;
    }

    if(!empty($this->mustClauses)) {
      $query['bool']['must'] = $this->mustClauses;
    }

    if(!empty($this->filterClauses)) {
      $query['bool']['filter'] = $this->filterClauses;
    }

    if(count(array_filter(array_keys($query['bool']))) > 0) {
      $body['query'] = $query;
    }

    $body['size'] = $this->size;
    $body['from'] = $this->from;

    if(!empty($this->sortClauses)) {
      $body['sort'] = $this->sortClauses;
    }

    return [
      'index' => $this->index,
      'body' => $body
    ];
  }
}

?>
