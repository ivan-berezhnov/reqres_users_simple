<?php

namespace Drupal\reqres_users_simple\ApiClient;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\reqres_users_simple\Exception\ApiException;
use Drupal\reqres_users_simple\Exception\ReqresApiConnectionException;
use Drupal\reqres_users_simple\Exception\ReqresApiDataException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Service for interacting with the Reqres API.
 */
class ReqresApiClient implements UserApiClientInterface {

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
   * The base URL for the Reqres API.
   *
   * @var string
   */
  protected $baseUrl = 'https://reqres.in/api';
  
  /**
   * Rate limit configuration.
   *
   * @var array
   */
  protected $rateLimit = [
    'limit' => 60,      // 60 requests
    'period' => 3600,   // per hour
    'remaining' => 60,  // initial value
  ];

  /**
   * Constructs a new ReqresApiClient.
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
    $cache_id = "reqres_users_simple:users:$page:$per_page";
    
    if ($cache = $this->cache->get($cache_id)) {
      return $cache->data;
    }

    try {
      $client = $this->httpClientFactory->fromOptions();
      $response = $client->request('GET', "{$this->baseUrl}/users", [
        'query' => [
          'page' => $page,
          'per_page' => $per_page,
        ],
        'headers' => [
          'x-api-key' => 'reqres-free-v1',
        ],
        'timeout' => 5,
        'connect_timeout' => 3,
      ]);

      $content = $response->getBody()->getContents();
      
      try {
        $data = Json::decode($content);
      }
      catch (\Exception $e) {
        throw new ReqresApiDataException('Invalid JSON response from API: ' . $e->getMessage(), 0, $e);
      }
      
      // Validate response structure
      if (!isset($data['data']) || !is_array($data['data'])) {
        throw new ReqresApiDataException('Invalid API response structure: missing or invalid data array');
      }
      
      // Get rate limit information
      $rate_limit = $this->getRateLimit();
      
      // Calculate cache lifetime based on rate limit
      // If few requests remain, increase cache lifetime
      $cache_lifetime = $rate_limit['period'];
      if ($rate_limit['remaining'] < $rate_limit['limit'] * 0.2) {
        // If less than 20% of requests remain, double the cache lifetime
        $cache_lifetime *= 2;
      }
      
      // Cache the data
      $this->cache->set($cache_id, $data, time() + $cache_lifetime);
      
      return $data;
    }
    catch (GuzzleException $e) {
      throw new ReqresApiConnectionException('Connection error when fetching users from API: ' . $e->getMessage(), 0, $e);
    }
    catch (ReqresApiDataException $e) {
      // Re-throw data exceptions
      throw $e;
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
    $data = $this->getUsers(1, 1);
    return !empty($data['total']) ? $data['total'] : 0;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getRateLimit(): array {
    // For Reqres API, the limit is 60 requests per hour
    // This value could be obtained from API response headers
    // Currently using static values
    
    // In a real project, we could store these values in state or cache
    // and update them based on API response headers
    return $this->rateLimit;
  }
}
