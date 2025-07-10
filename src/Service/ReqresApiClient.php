<?php

namespace Drupal\reqres_users_simple\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\reqres_users_simple\Exception\ReqresApiConnectionException;
use Drupal\reqres_users_simple\Exception\ReqresApiDataException;
use Drupal\reqres_users_simple\Exception\ReqresApiException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Service for interacting with the Reqres API.
 */
class ReqresApiClient {

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
   * Get users from the API.
   *
   * @param int $page
   *   The page number to fetch.
   * @param int $per_page
   *   The number of items per page.
   *
   * @return array
   *   An array of users.
   *
   * @throws \Drupal\reqres_users_simple\Exception\ReqresApiConnectionException
   *   Thrown when there are connection issues with the API.
   * @throws \Drupal\reqres_users_simple\Exception\ReqresApiDataException
   *   Thrown when there are data format issues with the API response.
   * @throws \Drupal\reqres_users_simple\Exception\ReqresApiException
   *   Thrown when there are other API-related issues.
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
      
      // Cache for 1 hour
      $this->cache->set($cache_id, $data, time() + 3600);
      
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
      throw new ReqresApiException('Unexpected error when fetching users: ' . $e->getMessage(), 0, $e);
    }
  }

  /**
   * Get total pages count.
   *
   * @param int $per_page
   *   The number of items per page.
   *
   * @return int
   *   The total number of pages.
   */
  public function getTotalPages(int $per_page = 6): int {
    $data = $this->getUsers(1, $per_page);
    return !empty($data['total_pages']) ? $data['total_pages'] : 0;
  }

  /**
   * Get total users count.
   *
   * @return int
   *   The total number of users.
   */
  public function getTotalUsers(): int {
    $data = $this->getUsers(1, 1);
    return !empty($data['total']) ? $data['total'] : 0;
  }
}
