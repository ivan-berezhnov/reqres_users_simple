<?php

namespace Drupal\Tests\reqres_users_simple\Unit\ApiClient;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\reqres_users_simple\ApiClient\ReqresApiClient;
use Drupal\reqres_users_simple\Exception\ApiException;
use Drupal\reqres_users_simple\Exception\ReqresApiConnectionException;
use Drupal\reqres_users_simple\Exception\ReqresApiDataException;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;

/**
 * Tests the ReqresApiClient service.
 *
 * @group reqres_users_simple
 * @coversDefaultClass \Drupal\reqres_users_simple\ApiClient\ReqresApiClient
 */
class ReqresApiClientTest extends UnitTestCase {

  /**
   * The HTTP client factory.
   *
   * @var \Drupal\Core\Http\ClientFactory|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $httpClientFactory;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $httpClient;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $logger;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $loggerFactory;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $cache;

  /**
   * The API client.
   *
   * @var \Drupal\reqres_users_simple\ApiClient\ReqresApiClient
   */
  protected $apiClient;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->httpClient = $this->prophesize(Client::class);
    $this->httpClientFactory = $this->prophesize(ClientFactory::class);
    $this->httpClientFactory->fromOptions(Argument::any())->willReturn($this->httpClient->reveal());

    $this->logger = $this->prophesize(LoggerChannelInterface::class);
    $this->loggerFactory = $this->prophesize(LoggerChannelFactoryInterface::class);
    $this->loggerFactory->get('reqres_users_simple')->willReturn($this->logger->reveal());

    $this->cache = $this->prophesize(CacheBackendInterface::class);

    $this->apiClient = new ReqresApiClient(
      $this->httpClientFactory->reveal(),
      $this->loggerFactory->reveal(),
      $this->cache->reveal()
    );
  }

  /**
   * Tests the getUsers method with successful response.
   *
   * @covers ::getUsers
   */
  public function testGetUsersSuccess() {
    $mock_data = [
      'page' => 1,
      'per_page' => 6,
      'total' => 12,
      'total_pages' => 2,
      'data' => [
        [
          'id' => 1,
          'email' => 'george.bluth@reqres.in',
          'first_name' => 'George',
          'last_name' => 'Bluth',
          'avatar' => 'https://reqres.in/img/faces/1-image.jpg',
        ],
      ],
    ];

    $response = new Response(200, [], Json::encode($mock_data));
    $this->httpClient->request('GET', 'https://reqres.in/api/users', Argument::any())
      ->willReturn($response);

    $this->cache->get('reqres_users_simple:users:1:6')->willReturn(FALSE);
    $this->cache->set('reqres_users_simple:users:1:6', $mock_data, Argument::any())->shouldBeCalled();

    $result = $this->apiClient->getUsers(1, 6);
    $this->assertEquals($mock_data, $result);
  }

  /**
   * Tests the getUsers method with cached response.
   *
   * @covers ::getUsers
   */
  public function testGetUsersCached() {
    $mock_data = [
      'page' => 1,
      'per_page' => 6,
      'total' => 12,
      'total_pages' => 2,
      'data' => [
        [
          'id' => 1,
          'email' => 'george.bluth@reqres.in',
          'first_name' => 'George',
          'last_name' => 'Bluth',
          'avatar' => 'https://reqres.in/img/faces/1-image.jpg',
        ],
      ],
    ];

    $cache_item = new \stdClass();
    $cache_item->data = $mock_data;

    $this->cache->get('reqres_users_simple:users:1:6')->willReturn($cache_item);
    $this->httpClient->request()->shouldNotBeCalled();

    $result = $this->apiClient->getUsers(1, 6);
    $this->assertEquals($mock_data, $result);
  }

  /**
   * Tests the getUsers method with API error.
   *
   * @covers ::getUsers
   */
  public function testGetUsersError() {
    $request = new Request('GET', 'https://reqres.in/api/users');
    $exception = new RequestException('Error', $request);

    $this->cache->get('reqres_users_simple:users:1:6')->willReturn(FALSE);
    $this->httpClient->request('GET', 'https://reqres.in/api/users', Argument::any())
      ->willThrow($exception);

    $this->expectException(ReqresApiConnectionException::class);
    $this->apiClient->getUsers(1, 6);
  }

  /**
   * Tests the getUsers method with invalid JSON.
   *
   * @covers ::getUsers
   */
  public function testGetUsersInvalidJson() {
    $response = new Response(200, [], '{invalid json}');
    $this->httpClient->request('GET', 'https://reqres.in/api/users', Argument::any())
      ->willReturn($response);

    $this->cache->get('reqres_users_simple:users:1:6')->willReturn(FALSE);

    $this->expectException(ReqresApiDataException::class);
    $this->apiClient->getUsers(1, 6);
  }

  /**
   * Tests the getUsers method with invalid data structure.
   *
   * @covers ::getUsers
   */
  public function testGetUsersInvalidStructure() {
    $response = new Response(200, [], Json::encode(['invalid' => 'structure']));
    $this->httpClient->request('GET', 'https://reqres.in/api/users', Argument::any())
      ->willReturn($response);

    $this->cache->get('reqres_users_simple:users:1:6')->willReturn(FALSE);

    $this->expectException(ReqresApiDataException::class);
    $this->apiClient->getUsers(1, 6);
  }

  /**
   * Tests the getTotalPages method.
   *
   * @covers ::getTotalPages
   */
  public function testGetTotalPages() {
    $mock_data = [
      'page' => 1,
      'per_page' => 6,
      'total' => 12,
      'total_pages' => 2,
      'data' => [],
    ];

    $response = new Response(200, [], Json::encode($mock_data));
    $this->httpClient->request('GET', 'https://reqres.in/api/users', Argument::any())
      ->willReturn($response);

    $this->cache->get('reqres_users_simple:users:1:6')->willReturn(FALSE);
    $this->cache->set('reqres_users_simple:users:1:6', $mock_data, Argument::any())->shouldBeCalled();

    $result = $this->apiClient->getTotalPages(6);
    $this->assertEquals(2, $result);
  }

  /**
   * Tests the getTotalUsers method.
   *
   * @covers ::getTotalUsers
   */
  public function testGetTotalUsers() {
    $mock_data = [
      'page' => 1,
      'per_page' => 1,
      'total' => 12,
      'total_pages' => 12,
      'data' => [],
    ];

    $response = new Response(200, [], Json::encode($mock_data));
    $this->httpClient->request('GET', 'https://reqres.in/api/users', Argument::any())
      ->willReturn($response);

    $this->cache->get('reqres_users_simple:users:1:1')->willReturn(FALSE);
    $this->cache->set('reqres_users_simple:users:1:1', $mock_data, Argument::any())->shouldBeCalled();

    $result = $this->apiClient->getTotalUsers();
    $this->assertEquals(12, $result);
  }

}
