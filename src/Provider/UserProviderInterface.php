<?php

namespace Drupal\reqres_users_simple\Provider;

/**
 * Interface for user providers.
 */
interface UserProviderInterface {

  /**
   * Gets users from the provider.
   *
   * @param int $page
   *   The page number.
   * @param int $per_page
   *   The number of items per page.
   * @param array $settings
   *   Optional settings to override defaults.
   *
   * @return array
   *   An array of standardized user models.
   */
  public function getUsers(int $page = 1, int $per_page = 6, array $settings = []): array;

  /**
   * Gets the total number of pages.
   *
   * @param int $per_page
   *   The number of items per page.
   * @param array $settings
   *   Optional settings to override defaults.
   *
   * @return int
   *   The total number of pages.
   */
  public function getTotalPages(int $per_page = 6, array $settings = []): int;

  /**
   * Gets the total number of users.
   *
   * @param array $settings
   *   Optional settings to override defaults.
   *
   * @return int
   *   The total number of users.
   */
  public function getTotalUsers(array $settings = []): int;

  /**
   * Gets the provider name.
   *
   * @return string
   *   The provider name.
   */
  public function getName(): string;
  
  /**
   * Gets the provider name (alias for getName).
   *
   * @return string
   *   The provider name.
   */
  public function getProviderName(): string;

}
