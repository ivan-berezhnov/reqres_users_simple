<?php

namespace Drupal\Tests\reqres_users_simple\Unit\Event;

use Drupal\reqres_users_simple\Event\UserFilterEvent;
use Drupal\reqres_users_simple\Model\User;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the UserFilterEvent.
 *
 * @group reqres_users_simple
 * @coversDefaultClass \Drupal\reqres_users_simple\Event\UserFilterEvent
 */
class UserFilterEventTest extends UnitTestCase {

  /**
   * Tests the UserFilterEvent.
   *
   * @covers ::__construct
   * @covers ::getUsers
   * @covers ::setUsers
   */
  public function testUserFilterEvent() {
    $user1 = new User(1, 'George', 'Bluth', 'george.bluth@reqres.in', 'https://reqres.in/img/faces/1-image.jpg');
    $user2 = new User(2, 'Janet', 'Weaver', 'janet.weaver@reqres.in', 'https://reqres.in/img/faces/2-image.jpg');
    
    $users = [$user1, $user2];
    
    $event = new UserFilterEvent($users);
    
    // Test getUsers().
    $this->assertSame($users, $event->getUsers());
    
    // Test setUsers().
    $user3 = new User(3, 'Emma', 'Wong', 'emma.wong@reqres.in', 'https://reqres.in/img/faces/3-image.jpg');
    $newUsers = [$user1, $user3];
    
    $event->setUsers($newUsers);
    $this->assertSame($newUsers, $event->getUsers());
  }

}
