<?php

namespace Drupal\reqres_users_simple\ApiClient;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\reqres_users_simple\Exception\ApiException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Service for interacting with the JSONPlaceholder API.
 */
class JsonPlaceholderApiClient implements UserApiClientInterface {

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
   * The base URL for the JSONPlaceholder API.
   *
   * @var string
   */
  protected $baseUrl = 'https://jsonplaceholder.typicode.com';
  
  /**
   * Rate limit configuration.
   *
   * @var array
   */
  protected $rateLimit = [
    'limit' => 100,     // 100 requests
    'period' => 3600,   // per hour
    'remaining' => 100, // initial value
  ];

  /**
   * Constructs a new JsonPlaceholderApiClient.
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
    $cache_id = "reqres_users_simple:jsonplaceholder:users:$page:$per_page";
    
    if ($cache = $this->cache->get($cache_id)) {
      return $cache->data;
    }

    try {
      $client = $this->httpClientFactory->fromOptions();
      
      // JSONPlaceholder doesn't support pagination directly, so we handle it manually
      $response = $client->request('GET', "{$this->baseUrl}/users", [
        'timeout' => 5,
        'connect_timeout' => 3,
      ]);

      $content = $response->getBody()->getContents();
      
      try {
        $all_users = Json::decode($content);
      }
      catch (\Exception $e) {
        throw new ApiException('Invalid JSON response from API: ' . $e->getMessage(), 0, $e);
      }
      
      // Validate response structure
      if (!is_array($all_users)) {
        throw new ApiException('Invalid API response structure: expected array of users');
      }
      
      // Calculate pagination
      $total = count($all_users);
      $total_pages = ceil($total / $per_page);
      $offset = ($page - 1) * $per_page;
      
      // Get users for the current page
      $users = array_slice($all_users, $offset, $per_page);
      
      $data = [
        'data' => $users,
        'page' => $page,
        'per_page' => $per_page,
        'total' => $total,
        'total_pages' => $total_pages,
      ];
      
      // Get rate limit information
      $rate_limit = $this->getRateLimit();
      
      // Calculate cache lifetime based on rate limit
      $cache_lifetime = $rate_limit['period'];
      if ($rate_limit['remaining'] < $rate_limit['limit'] * 0.3) {
        // If less than 30% of requests remain, double the cache lifetime
        $cache_lifetime *= 2;
      }
      
      // Cache the data
      $this->cache->set($cache_id, $data, time() + $cache_lifetime);
      
      return $data;
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
    $data = $this->getUsers(1, $per_page);
    return !empty($data['total_pages']) ? $data['total_pages'] : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalUsers(): int {
    $data = $this->getUsers();
    return count($data);
  }

  /**
   * {@inheritdoc}
   */
  public function getRateLimit(): array {
    // For JsonPlaceholder API, the limit is 100 requests per hour
    // In a real scenario, this value could be obtained from API response headers
    return $this->rateLimit;
  }
}
