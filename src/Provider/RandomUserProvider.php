<?php

namespace Drupal\reqres_users_simple\Provider;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\reqres_users_simple\Adapter\UserDataAdapterInterface;
use Drupal\reqres_users_simple\ApiClient\UserApiClientInterface;
use Drupal\reqres_users_simple\Exception\ApiException;

/**
 * Provider for Random User API users.
 */
class RandomUserProvider implements UserProviderInterface {

  /**
   * The Random User API client.
   *
   * @var \Drupal\reqres_users_simple\ApiClient\UserApiClientInterface
   */
  protected $apiClient;

  /**
   * The user data adapter.
   *
   * @var \Drupal\reqres_users_simple\Adapter\UserDataAdapterInterface
   */
  protected $adapter;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new RandomUserProvider.
   *
   * @param \Drupal\reqres_users_simple\ApiClient\UserApiClientInterface $api_client
   *   The API client.
   * @param \Drupal\reqres_users_simple\Adapter\UserDataAdapterInterface $adapter
   *   The user data adapter.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    UserApiClientInterface $api_client,
    UserDataAdapterInterface $adapter,
    MessengerInterface $messenger,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->apiClient = $api_client;
    $this->adapter = $adapter;
    $this->messenger = $messenger;
    $this->logger = $logger_factory->get('reqres_users_simple');
  }

  /**
   * {@inheritdoc}
   */
  public function getUsers(int $page = 1, int $per_page = 6): array {
    $empty_result = [];
    
    try {
      $data = $this->apiClient->getUsers($page, $per_page);
      
      if (empty($data['data'])) {
        return $empty_result;
      }
      
      return $this->adapter->convertCollection($data['data']);
    }
    catch (ApiException $e) {
      $this->logger->error('API exception in getUsers: @message', ['@message' => $e->getMessage(), 'exception' => $e]);
      return $empty_result;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalPages(int $per_page = 6): int {
    try {
      return $this->apiClient->getTotalPages($per_page);
    }
    catch (ApiException $e) {
      $this->logger->error('API exception in getTotalPages: @message', ['@message' => $e->getMessage(), 'exception' => $e]);
      return 0;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalUsers(): int {
    try {
      return $this->apiClient->getTotalUsers();
    }
    catch (ApiException $e) {
      $this->logger->error('API exception in getTotalUsers: @message', ['@message' => $e->getMessage(), 'exception' => $e]);
      return 0;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return 'randomuser';
  }
  
  /**
   * {@inheritdoc}
   */
  public function getProviderName(): string {
    return $this->getName();
  }

}
