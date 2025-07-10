<?php

namespace Drupal\reqres_users_simple\ApiClient;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\reqres_users_simple\Exception\ApiException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Service for interacting with the Random User API.
 */
class RandomUserApiClient implements UserApiClientInterface {

  /**
   * The HTTP client factory.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $httpClientFactory;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The base URL for the Random User API.
   *
   * @var string
   */
  protected $baseUrl = 'https://randomuser.me/api';
  
  /**
   * Rate limit configuration.
   *
   * @var array
   */
  protected $rateLimit = [
    'limit' => 200,     // 200 requests
    'period' => 3600,   // per hour
    'remaining' => 200, // initial value
  ];

  /**
   * Constructs a new RandomUserApiClient.
   *
   * @param \Drupal\Core\Http\ClientFactory $http_client_factory
   *   The HTTP client factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   */
  public function __construct(
    ClientFactory $http_client_factory,
    LoggerChannelFactoryInterface $logger_factory,
    CacheBackendInterface $cache
  ) {
    $this->httpClientFactory = $http_client_factory;
    $this->logger = $logger_factory->get('reqres_users_simple');
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsers(int $page = 1, int $per_page = 6): array {
    $cache_id = "reqres_users_simple:randomuser:users:$page:$per_page";
    
    if ($cache = $this->cache->get($cache_id)) {
      return $cache->data;
    }

    try {
      $client = $this->httpClientFactory->fromOptions();
      $response = $client->request('GET', $this->baseUrl, [
        'query' => [
          'page' => $page,
          'results' => $per_page,
          'seed' => 'drupal', // Use consistent seed for reproducible results
        ],
        'timeout' => 5,
        'connect_timeout' => 3,
      ]);

      $content = $response->getBody()->getContents();
      
      try {
        $data = Json::decode($content);
      }
      catch (\Exception $e) {
        throw new ApiException('Invalid JSON response from API: ' . $e->getMessage(), 0, $e);
      }
      
      // Validate response structure
      if (!isset($data['results']) || !is_array($data['results'])) {
        throw new ApiException('Invalid API response structure: missing or invalid results array');
      }
      
      // Format data to match our expected structure
      $formatted_data = [
        'data' => $data['results'],
        'page' => $page,
        'per_page' => $per_page,
        'total' => $data['info']['results'] * 10, // Approximate total
        'total_pages' => 10, // Random User API has many pages
      ];
      
      // Get rate limit information
      $rate_limit = $this->getRateLimit();
      
      // Calculate cache lifetime based on rate limit
      $cache_lifetime = $rate_limit['period'];
      if ($rate_limit['remaining'] < $rate_limit['limit'] * 0.25) {
        // If less than 25% of requests remain, double the cache lifetime
        $cache_lifetime *= 2;
      }
      
      // Cache the data
      $this->cache->set($cache_id, $formatted_data, time() + $cache_lifetime);
      
      return $formatted_data;
    }
    catch (GuzzleException $e) {
      throw new ApiException('Connection error when fetching users from API: ' . $e->getMessage(), 0, $e);
    }
    catch (\Exception $e) {
      throw new ApiException('Unexpected error when fetching users: ' . $e->getMessage(), 0, $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalPages(int $per_page = 6): int {
    return 10; // Random User API has many pages
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalUsers(): int {
    // RandomUser API doesn't provide total users count
    // For demo purposes, we'll return a fixed number
    return 1000;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getRateLimit(): array {
    // For RandomUser API, the limit is 200 requests per hour
    // This value could be obtained from API response headers
    // For example, RandomUser API returns X-RateLimit-Limit and X-RateLimit-Remaining headers
    
    // In a real project, we could update these values based on response headers
    return $this->rateLimit;
  }
}
