<?php

namespace Drupal\reqres_users_simple\Event;

use Drupal\reqres_users_simple\Model\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event for filtering users from multiple API sources.
 */
class UserFilterEvent extends Event {

  /**
   * The name of the event.
   */
  const EVENT_NAME = 'reqres_users_simple.filter_users';

  /**
   * The users to filter.
   *
   * @var \Drupal\reqres_users_simple\Model\UserInterface[]
   */
  protected $users;

  /**
   * Constructs a new UserFilterEvent.
   *
   * @param \Drupal\reqres_users_simple\Model\UserInterface[] $users
   *   The users to filter.
   */
  public function __construct(array $users) {
    $this->users = $users;
  }

  /**
   * Gets the users.
   *
   * @return \Drupal\reqres_users_simple\Model\UserInterface[]
   *   The users.
   */
  public function getUsers(): array {
    return $this->users;
  }

  /**
   * Sets the users.
   *
   * @param \Drupal\reqres_users_simple\Model\UserInterface[] $users
   *   The users.
   */
  public function setUsers(array $users): void {
    $this->users = $users;
  }

}
