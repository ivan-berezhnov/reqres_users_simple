<?php

namespace Drupal\reqres_users_simple\Adapter;

use Drupal\reqres_users_simple\Model\User;
use Drupal\reqres_users_simple\Model\UserInterface;

/**
 * Adapter for Reqres API user data.
 */
class ReqresUserDataAdapter implements UserDataAdapterInterface {

  /**
   * {@inheritdoc}
   */
  public function convertToUser(array $data): UserInterface {
    return new User(
      $data['id'] ?? 0,
      $data['email'] ?? '',
      $data['first_name'] ?? '',
      $data['last_name'] ?? '',
      $data['avatar'] ?? ''
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
