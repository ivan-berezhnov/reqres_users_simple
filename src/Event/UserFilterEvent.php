<?php

namespace Drupal\reqres_users_simple\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event for filtering users from the Reqres API.
 */
class UserFilterEvent extends Event {

  /**
   * The name of the event.
   */
  const EVENT_NAME = 'reqres_users_simple.filter_users';

  /**
   * The users to filter.
   *
   * @var array
   */
  protected $users;

  /**
   * Constructs a new UserFilterEvent.
   *
   * @param array $users
   *   The users to filter.
   */
  public function __construct(array $users) {
    $this->users = $users;
  }

  /**
   * Gets the users.
   *
   * @return array
   *   The users.
   */
  public function getUsers(): array {
    return $this->users;
  }

  /**
   * Sets the users.
   *
   * @param array $users
   *   The users.
   */
  public function setUsers(array $users): void {
    $this->users = $users;
  }

}
