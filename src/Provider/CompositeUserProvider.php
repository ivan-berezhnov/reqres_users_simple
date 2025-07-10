<?php

namespace Drupal\reqres_users_simple\Provider;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Composite provider that aggregates users from multiple sources.
 */
class CompositeUserProvider implements UserProviderInterface {

  /**
   * The user providers.
   *
   * @var \Drupal\reqres_users_simple\Provider\UserProviderInterface[]
   */
  protected $providers = [];

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new CompositeUserProvider.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->logger = $logger_factory->get('reqres_users_simple');
  }

  /**
   * Adds a user provider.
   *
   * @param \Drupal\reqres_users_simple\Provider\UserProviderInterface $provider
   *   The user provider.
   *
   * @return $this
   */
  public function addProvider(UserProviderInterface $provider) {
    $this->providers[$provider->getName()] = $provider;
    return $this;
  }

  /**
   * Gets all providers.
   *
   * @return \Drupal\reqres_users_simple\Provider\UserProviderInterface[]
   *   The providers.
   */
  public function getProviders(): array {
    return $this->providers;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsers(int $page = 1, int $per_page = 6): array {
    $users = [];
    
    foreach ($this->providers as $provider) {
      try {
        $provider_users = $provider->getUsers($page, $per_page);
        $users = array_merge($users, $provider_users);
      }
      catch (\Exception $e) {
        $this->logger->error('Error fetching users from provider @provider: @message', [
          '@provider' => $provider->getName(),
          '@message' => $e->getMessage(),
        ]);
      }
    }
    
    // Sort users by ID to maintain consistency
    usort($users, function ($a, $b) {
      return $a->getId() - $b->getId();
    });
    
    return $users;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalPages(int $per_page = 6): int {
    $max_pages = 0;
    
    foreach ($this->providers as $provider) {
      try {
        $pages = $provider->getTotalPages($per_page);
        $max_pages = max($max_pages, $pages);
      }
      catch (\Exception $e) {
        $this->logger->error('Error fetching total pages from provider @provider: @message', [
          '@provider' => $provider->getName(),
          '@message' => $e->getMessage(),
        ]);
      }
    }
    
    return $max_pages;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalUsers(): int {
    $total = 0;
    
    foreach ($this->providers as $provider) {
      try {
        $total += $provider->getTotalUsers();
      }
      catch (\Exception $e) {
        $this->logger->error('Error fetching total users from provider @provider: @message', [
          '@provider' => $provider->getName(),
          '@message' => $e->getMessage(),
        ]);
      }
    }
    
    return $total;
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return 'composite';
  }
  
  /**
   * {@inheritdoc}
   */
  public function getProviderName(): string {
    return $this->getName();
  }

}
