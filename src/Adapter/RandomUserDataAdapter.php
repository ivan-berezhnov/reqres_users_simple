<?php

namespace Drupal\reqres_users_simple\Adapter;

use Drupal\reqres_users_simple\Model\User;
use Drupal\reqres_users_simple\Model\UserInterface;

/**
 * Adapter for Random User API data.
 */
class RandomUserDataAdapter implements UserDataAdapterInterface {

  /**
   * {@inheritdoc}
   */
  public function convertToUser(array $data): UserInterface {
    return new User(
      // Generate ID from login uuid since Random User API doesn't have numeric IDs
      crc32($data['login']['uuid'] ?? ''),
      $data['email'] ?? '',
      $data['name']['first'] ?? '',
      $data['name']['last'] ?? '',
      $data['picture']['medium'] ?? ''
    );
  }

  /**
   * {@inheritdoc}
   */
  public function convertCollection(array $data_collection): array {
    $users = [];
    foreach ($data_collection as $data) {
      $users[] = $this->convertToUser($data);
    }
    return $users;
  }

}
