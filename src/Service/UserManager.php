<?php

namespace Drupal\reqres_users_simple\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\reqres_users_simple\Event\UserFilterEvent;
use Drupal\reqres_users_simple\Exception\ReqresApiConnectionException;
use Drupal\reqres_users_simple\Exception\ReqresApiDataException;
use Drupal\reqres_users_simple\Exception\ReqresApiException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Service for managing users from the Reqres API.
 */
class UserManager {

  /**
   * The Reqres API client.
   *
   * @var \Drupal\reqres_users_simple\Service\ReqresApiClient
   */
  protected $apiClient;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

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
   * Constructs a new UserManager.
   *
   * @param \Drupal\reqres_users_simple\Service\ReqresApiClient $api_client
   *   The Reqres API client.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(
    ReqresApiClient $api_client,
    EventDispatcherInterface $event_dispatcher,
    MessengerInterface $messenger,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->apiClient = $api_client;
    $this->eventDispatcher = $event_dispatcher;
    $this->messenger = $messenger;
    $this->logger = $logger_factory->get('reqres_users_simple');
  }

  /**
   * Get filtered users.
   *
   * @param int $page
   *   The page number.
   * @param int $per_page
   *   The number of items per page.
   *
   * @return array
   *   An array of filtered users.
   */
  public function getFilteredUsers(int $page = 1, int $per_page = 6): array {
    $empty_result = [
      'data' => [],
      'page' => $page,
      'per_page' => $per_page,
      'total' => 0,
      'total_pages' => 0,
    ];
    
    try {
      $data = $this->apiClient->getUsers($page, $per_page);
      
      if (empty($data['data'])) {
        return $empty_result;
      }
      
      // Create and dispatch the event to allow filtering
      $event = new UserFilterEvent($data['data']);
      $this->eventDispatcher->dispatch($event, UserFilterEvent::EVENT_NAME);
      
      // Return the filtered data
      $data['data'] = $event->getUsers();
      
      return $data;
    }
    catch (ReqresApiConnectionException $e) {
      $this->logger->error('Connection exception: @message', ['@message' => $e->getMessage(), 'exception' => $e]);
      $this->messenger->addError(t('Unable to connect to the users API. Please try again later.'));
      return $empty_result;
    }
    catch (ReqresApiDataException $e) {
      $this->logger->error('Data format exception: @message', ['@message' => $e->getMessage(), 'exception' => $e]);
      $this->messenger->addError(t('The users API returned invalid data. Please try again later.'));
      return $empty_result;
    }
    catch (ReqresApiException $e) {
      $this->logger->error('API exception: @message', ['@message' => $e->getMessage(), 'exception' => $e]);
      $this->messenger->addError(t('An error occurred while fetching users. Please try again later.'));
      return $empty_result;
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
    try {
      return $this->apiClient->getTotalPages($per_page);
    }
    catch (ReqresApiException $e) {
      $this->logger->error('API exception in getTotalPages: @message', ['@message' => $e->getMessage(), 'exception' => $e]);
      return 0;
    }
  }

  /**
   * Get total users count.
   *
   * @return int
   *   The total number of users.
   */
  public function getTotalUsers(): int {
    try {
      return $this->apiClient->getTotalUsers();
    }
    catch (ReqresApiException $e) {
      $this->logger->error('API exception in getTotalUsers: @message', ['@message' => $e->getMessage(), 'exception' => $e]);
      return 0;
    }
  }
}
