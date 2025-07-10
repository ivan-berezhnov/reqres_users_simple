<?php

namespace Drupal\reqres_users_simple\Adapter;

use Drupal\reqres_users_simple\Model\UserInterface;

/**
 * Interface for user data adapters.
 */
interface UserDataAdapterInterface {

  /**
   * Converts API-specific user data to a standardized user model.
   *
   * @param array $data
   *   The raw user data from an API.
   *
   * @return \Drupal\reqres_users_simple\Model\UserInterface
   *   The standardized user model.
   */
  public function convertToUser(array $data): UserInterface;

  /**
   * Converts a collection of API-specific user data to standardized user models.
   *
   * @param array $data_collection
   *   The raw collection of user data from an API.
   *
   * @return array
   *   An array of standardized user models.
   */
  public function convertCollection(array $data_collection): array;

}
