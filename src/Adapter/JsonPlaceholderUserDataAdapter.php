<?php

namespace Drupal\reqres_users_simple\Adapter;

use Drupal\reqres_users_simple\Model\User;
use Drupal\reqres_users_simple\Model\UserInterface;

/**
 * Adapter for JSONPlaceholder API user data.
 */
class JsonPlaceholderUserDataAdapter implements UserDataAdapterInterface {

  /**
   * {@inheritdoc}
   */
  public function convertToUser(array $data): UserInterface {
    // JSONPlaceholder has a different data structure than Reqres
    return new User(
      $data['id'] ?? 0,
      $data['email'] ?? '',
      $data['name'] ?? '', // JSONPlaceholder has 'name' instead of 'first_name'
      '', // No last name in JSONPlaceholder
      'https://via.placeholder.com/150' // Default avatar as JSONPlaceholder doesn't provide avatars
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
