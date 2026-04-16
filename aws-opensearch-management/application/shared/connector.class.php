<?php
use Aws\Credentials\CredentialProvider;
use Aws\Signature\SignatureV4;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;

class SearchService {
  private $endpoint;
  private $region;
  private $service;
  private $provider;
  private $signer;
  private $httpClient;

  public function __construct() {
    $this->endpoint = 'vpc-kept-os-test-bcrkbkbojbhz4krojts5la34im.us-east-1.es.amazonaws.com';
    $this->region = 'us-east-1';
    $this->service = 'es';
    $this->provider = CredentialProvider::defaultProvider();
    $this->signer = new SignatureV4($this->service, $this->region);
    // The only change is here: ensuring Guzzle fails on HTTP errors
    $this->httpClient = new GuzzleClient(['http_errors' => true]);
  }

  private function sendSignedRequest(RequestInterface $request) {
    $credentials = ($this->provider)()->wait();
    $signedRequest = $this->signer->signRequest($request, $credentials);
    $response = $this->httpClient->send($signedRequest);
    return json_decode($response->getBody(), true);
  }

  public function rawSearch(SearchQueryBuilder $builder): ?array {
    try {
      $params = $builder->build();
      $index = $params['index'];
      $queryBody = $params['body'];

      $request = new Request(
        'POST',
        'https://' . $this->endpoint . '/' . $index . '/_search',
        ['Content-Type' => 'application/json'],
        json_encode($queryBody)
      );
      $response = $this->sendSignedRequest($request);
      return $response;
    }
    catch(Exception $e) {
      error_log("OpenSearch Search Error: " . $e->getMessage());
      return null;
    }
  }

  public function search(SearchQueryBuilder $builder): ?array {
    $response = $this->rawSearch($builder);

    if (is_null($response)) {
      return null;
    }

    $formattedResults = [];
    if (isset($response['hits']['hits'])) {
      foreach ($response['hits']['hits'] as $hit) {
        // Merge the _source data with the important meta fields
        $formattedResults[] = array_merge(
          ['_id' => $hit['_id'], '_score' => $hit['_score'], '_index' => $hit['_index']],
          $hit['_source']
        );
      }
    }
    
    return [
      'total' => $response['hits']['total']['value'] ?? 0,
      'took' => $response['took'] ?? 0,
      'timed_out' => $response['timed_out'] ?? false,
      'documents' => $formattedResults
    ];
  }

  public function count(SearchQueryBuilder $builder): ?int {
    try {
      $params = $builder->buildCount(); // Use the new buildCount() method
      $index = $params['index'];
      $queryBody = $params['body'];

      $request = new Request(
        'POST',
        'https://' . $this->endpoint . '/' . $index . '/_count', // Note the /_count endpoint
        ['Content-Type' => 'application/json'],
        json_encode($queryBody)
      );
      $response = $this->sendSignedRequest($request);
      return $response['count'] ?? 0;
    }
    catch(Exception $e) {
      error_log("OpenSearch Count Error: " . $e->getMessage());
      return null;
    }
  }

  public function indexDocument(string $index, string $id, array $body): bool {
    try {
      $request = new Request(
        'PUT',
        'https://' . $this->endpoint . '/' . $index . '/_doc/' . $id,
        ['Content-Type' => 'application/json'],
        json_encode($body)
      );
      $this->sendSignedRequest($request);
      return true;
    }
    catch(Exception $e) {
      error_log("OpenSearch Index Error: " . $e->getMessage());
      return false;
    }
  }
  
  public function bulkIndex(string $index, array $documents): ?array {
    try {
      $body = '';
      foreach($documents as $doc) {
        $action = [
          'index' => [
            '_index' => $index,
            '_id' => $doc['id']
          ]
        ];
        $body .= json_encode($action) . "\n";
        $body .= json_encode($doc) . "\n";
      }

      $request = new Request(
        'POST',
        'https://' . $this->endpoint . '/_bulk',
        ['Content-Type' => 'application/x-ndjson'],
        $body
      );
      
      $response = $this->sendSignedRequest($request);

      // Add this check to log the response if it contains errors
      if (isset($response['errors']) && $response['errors'] === true) {
        error_log("OpenSearch Bulk Response contains errors: " . json_encode($response));
      }

      return $response;
    }
    catch(Exception $e) {
      error_log("OpenSearch Bulk Index Error: " . $e->getMessage());
      return null;
    }
  }
  
}