<?php

namespace Drupal\reqres_users_simple\ApiClient;

use Drupal\reqres_users_simple\Exception\ApiException;

/**
 * Interface for user API clients.
 */
interface UserApiClientInterface {

  /**
   * Get the rate limit for this API.
   *
   * @return array
   *   An array containing rate limit information with keys:
   *   - 'limit': The maximum number of requests allowed in the time period.
   *   - 'period': The time period in seconds (e.g., 3600 for hourly).
   *   - 'remaining': The number of requests remaining in the current period.
   */
  public function getRateLimit(): array;

  /**
   * Get users from the API.
   *
   * @param int $page
   *   The page number to fetch.
   * @param int $per_page
   *   The number of items per page.
   *
   * @return array
   *   An array of users in raw format from the API.
   *
   * @throws \Drupal\reqres_users_simple\Exception\ApiException
   *   Thrown when there are API-related issues.
   */
  public function getUsers(int $page = 1, int $per_page = 6): array;

  /**
   * Get total pages count.
   *
   * @param int $per_page
   *   The number of items per page.
   *
   * @return int
   *   The total number of pages.
   *
   * @throws \Drupal\reqres_users_simple\Exception\ApiException
   *   Thrown when there are API-related issues.
   */
  public function getTotalPages(int $per_page = 6): int;

  /**
   * Get total users count.
   *
   * @return int
   *   The total number of users.
   *
   * @throws \Drupal\reqres_users_simple\Exception\ApiException
   *   Thrown when there are API-related issues.
   */
  public function getTotalUsers(): int;

}
