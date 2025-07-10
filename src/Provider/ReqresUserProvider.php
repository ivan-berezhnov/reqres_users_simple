<?php

namespace Drupal\reqres_users_simple\Provider;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\reqres_users_simple\Adapter\UserDataAdapterInterface;
use Drupal\reqres_users_simple\ApiClient\UserApiClientInterface;
use Drupal\reqres_users_simple\Exception\ApiException;

/**
 * Provider for Reqres API users.
 */
class ReqresUserProvider implements UserProviderInterface {

  /**
   * The Reqres API client.
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
   * Constructs a new ReqresUserProvider.
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
      $api_data = $this->apiClient->getUsers($page, $per_page);
      
      if (empty($api_data['data'])) {
        return $empty_result;
      }
      
      // Convert user data to User objects
      $users = $this->adapter->convertCollection($api_data['data']);
      
      // Store pagination metadata in the first user object
      if (!empty($users)) {
        $users[0]->setMetadata([
          'total' => $api_data['total'] ?? 0,
          'total_pages' => $api_data['total_pages'] ?? 0,
          'page' => $api_data['page'] ?? $page,
          'per_page' => $api_data['per_page'] ?? $per_page,
        ]);
      }
      
      return $users;
    }
    catch (ApiException $e) {
      $this->logger->error('API exception in getUsers: @message', ['@message' => $e->getMessage(), 'exception' => $e]);
      $this->messenger->addError(t('An error occurred while fetching users. Please try again later.'));
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
    return 'reqres';
  }
  
  /**
   * {@inheritdoc}
   */
  public function getProviderName(): string {
    return $this->getName();
  }

}
