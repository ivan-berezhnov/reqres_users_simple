<?php

namespace Drupal\reqres_users_simple\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\reqres_users_simple\Event\UserFilterEvent;
use Drupal\reqres_users_simple\Provider\CompositeUserProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Service for managing users from multiple providers.
 */
class UserProviderManager {

  /**
   * The composite user provider.
   *
   * @var \Drupal\reqres_users_simple\Provider\CompositeUserProvider
   */
  protected $compositeProvider;

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
   * Constructs a new UserProviderManager.
   *
   * @param \Drupal\reqres_users_simple\Provider\CompositeUserProvider $composite_provider
   *   The composite user provider.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    CompositeUserProvider $composite_provider,
    EventDispatcherInterface $event_dispatcher,
    MessengerInterface $messenger,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->compositeProvider = $composite_provider;
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
  public function getFilteredUsers(int $page = 1, int $per_page = 6, array $settings = []): array {
    $empty_result = [
      'data' => [],
      'page' => $page,
      'per_page' => $per_page,
      'total' => 0,
      'total_pages' => 0,
    ];
    
    try {
      $users = $this->compositeProvider->getUsers($page, $per_page, $settings);
      
      if (empty($users)) {
        return $empty_result;
      }
      
      // Extract metadata from the first user object if available
      $metadata = [];
      if (!empty($users[0]) && method_exists($users[0], 'getMetadata')) {
        $metadata = $users[0]->getMetadata();
      }
      
      // Convert users to array format for the event
      $user_data = array_map(function ($user) {
        return $user->toArray();
      }, $users);
      
      // Create and dispatch the event to allow filtering
      $event = new UserFilterEvent($user_data);
      $this->eventDispatcher->dispatch($event, UserFilterEvent::EVENT_NAME);
      
      // Return the filtered data with metadata
      $data = [
        'data' => $event->getUsers(),
        'page' => $metadata['page'] ?? $page,
        'per_page' => $metadata['per_page'] ?? $per_page,
        'total' => $metadata['total'] ?? $this->compositeProvider->getTotalUsers($settings),
        'total_pages' => $metadata['total_pages'] ?? $this->compositeProvider->getTotalPages($per_page, $settings),
      ];
      
      return $data;
    }
    catch (\Exception $e) {
      $this->logger->error('Error in getFilteredUsers: @message', ['@message' => $e->getMessage(), 'exception' => $e]);
      $this->messenger->addError('Error fetching users. Please try again later.');
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
  public function getTotalPages(int $per_page = 6, array $settings = []): int {
    try {
      return $this->compositeProvider->getTotalPages($per_page, $settings);
    }
    catch (\Exception $e) {
      $this->logger->error('Error in getTotalPages: @message', ['@message' => $e->getMessage(), 'exception' => $e]);
      return 0;
    }
  }

  /**
   * Get total users count.
   *
   * @return int
   *   The total number of users.
   */
  public function getTotalUsers(array $settings = []): int {
    try {
      return $this->compositeProvider->getTotalUsers($settings);
    }
    catch (\Exception $e) {
      $this->logger->error('Error in getTotalUsers: @message', ['@message' => $e->getMessage(), 'exception' => $e]);
      return 0;
    }
  }

}
