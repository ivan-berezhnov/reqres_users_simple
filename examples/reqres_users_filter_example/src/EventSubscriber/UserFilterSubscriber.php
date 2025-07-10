<?php

namespace Drupal\reqres_users_filter_example\EventSubscriber;

use Drupal\reqres_users_simple\Event\UserFilterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for filtering users from the reqres.in API.
 */
class UserFilterSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      UserFilterEvent::EVENT_NAME => 'filterUsers',
    ];
  }

  /**
   * Filter users from the reqres.in API.
   *
   * This example filters out users with even IDs.
   *
   * @param \Drupal\reqres_users_simple\Event\UserFilterEvent $event
   *   The event.
   */
  public function filterUsers(UserFilterEvent $event) {
    $users = $event->getUsers();
    $filtered_users = array_filter($users, function ($user) {
      // Example: Only include users with odd IDs
      return isset($user['id']) && ($user['id'] % 2 !== 0);
    });
    
    $event->setUsers(array_values($filtered_users));
  }

}
