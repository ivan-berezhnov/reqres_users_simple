<?php

namespace Drupal\Tests\reqres_users_simple\Unit;

use Drupal\reqres_users_simple\Event\UserFilterEvent;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the UserFilterEvent class.
 *
 * @group reqres_users_simple
 * @coversDefaultClass \Drupal\reqres_users_simple\Event\UserFilterEvent
 */
class UserFilterEventTest extends UnitTestCase {

  /**
   * Tests the getUsers and setUsers methods.
   *
   * @covers ::getUsers
   * @covers ::setUsers
   */
  public function testGetSetUsers() {
    $users = [
      [
        'id' => 1,
        'email' => 'george.bluth@reqres.in',
        'first_name' => 'George',
        'last_name' => 'Bluth',
      ],
      [
        'id' => 2,
        'email' => 'janet.weaver@reqres.in',
        'first_name' => 'Janet',
        'last_name' => 'Weaver',
      ],
    ];

    $event = new UserFilterEvent($users);
    $this->assertEquals($users, $event->getUsers());

    $filtered_users = [
      [
        'id' => 1,
        'email' => 'george.bluth@reqres.in',
        'first_name' => 'George',
        'last_name' => 'Bluth',
      ],
    ];

    $event->setUsers($filtered_users);
    $this->assertEquals($filtered_users, $event->getUsers());
  }

}
