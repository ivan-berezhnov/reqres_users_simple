<?php

namespace Drupal\Tests\reqres_users_simple\Unit;

use Drupal\reqres_users_simple\Event\UserFilterEvent;
use Drupal\reqres_users_simple\Service\ReqresApiClient;
use Drupal\reqres_users_simple\Service\UserManager;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tests the UserManager service.
 *
 * @group reqres_users_simple
 * @coversDefaultClass \Drupal\reqres_users_simple\Service\UserManager
 */
class UserManagerTest extends UnitTestCase {

  /**
   * The Reqres API client.
   *
   * @var \Drupal\reqres_users_simple\Service\ReqresApiClient|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $apiClient;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $eventDispatcher;

  /**
   * The user manager.
   *
   * @var \Drupal\reqres_users_simple\Service\UserManager
   */
  protected $userManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->apiClient = $this->prophesize(ReqresApiClient::class);
    $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

    $this->userManager = new UserManager(
      $this->apiClient->reveal(),
      $this->eventDispatcher->reveal()
    );
  }

  /**
   * Tests the getFilteredUsers method with data.
   *
   * @covers ::getFilteredUsers
   */
  public function testGetFilteredUsersWithData() {
    $mock_users = [
      [
        'id' => 1,
        'email' => 'george.bluth@reqres.in',
        'first_name' => 'George',
        'last_name' => 'Bluth',
        'avatar' => 'https://reqres.in/img/faces/1-image.jpg',
      ],
      [
        'id' => 2,
        'email' => 'janet.weaver@reqres.in',
        'first_name' => 'Janet',
        'last_name' => 'Weaver',
        'avatar' => 'https://reqres.in/img/faces/2-image.jpg',
      ],
    ];

    $mock_data = [
      'page' => 1,
      'per_page' => 6,
      'total' => 12,
      'total_pages' => 2,
      'data' => $mock_users,
    ];

    $filtered_users = [
      [
        'id' => 1,
        'email' => 'george.bluth@reqres.in',
        'first_name' => 'George',
        'last_name' => 'Bluth',
        'avatar' => 'https://reqres.in/img/faces/1-image.jpg',
      ],
    ];

    $this->apiClient->getUsers(1, 6)->willReturn($mock_data);

    $this->eventDispatcher->dispatch(
      Argument::that(function ($event) use ($mock_users, $filtered_users) {
        if (!$event instanceof UserFilterEvent) {
          return FALSE;
        }
        
        // Simulate an event subscriber filtering the users
        $event->setUsers($filtered_users);
        return TRUE;
      }),
      UserFilterEvent::EVENT_NAME
    )->shouldBeCalled();

    $result = $this->userManager->getFilteredUsers(1, 6);
    
    $expected = $mock_data;
    $expected['data'] = $filtered_users;
    
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests the getFilteredUsers method with no data.
   *
   * @covers ::getFilteredUsers
   */
  public function testGetFilteredUsersWithNoData() {
    $mock_data = [
      'page' => 1,
      'per_page' => 6,
      'total' => 0,
      'total_pages' => 0,
      'data' => [],
    ];

    $this->apiClient->getUsers(1, 6)->willReturn($mock_data);
    $this->eventDispatcher->dispatch(Argument::cetera())->shouldNotBeCalled();

    $result = $this->userManager->getFilteredUsers(1, 6);
    $this->assertEquals($mock_data, $result);
  }

  /**
   * Tests the getFilteredUsers method with empty response.
   *
   * @covers ::getFilteredUsers
   */
  public function testGetFilteredUsersWithEmptyResponse() {
    $this->apiClient->getUsers(1, 6)->willReturn([]);

    $expected = [
      'data' => [],
      'page' => 1,
      'per_page' => 6,
      'total' => 0,
      'total_pages' => 0,
    ];

    $result = $this->userManager->getFilteredUsers(1, 6);
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests the getTotalPages method.
   *
   * @covers ::getTotalPages
   */
  public function testGetTotalPages() {
    $this->apiClient->getTotalPages(6)->willReturn(2);
    $result = $this->userManager->getTotalPages(6);
    $this->assertEquals(2, $result);
  }

  /**
   * Tests the getTotalUsers method.
   *
   * @covers ::getTotalUsers
   */
  public function testGetTotalUsers() {
    $this->apiClient->getTotalUsers()->willReturn(12);
    $result = $this->userManager->getTotalUsers();
    $this->assertEquals(12, $result);
  }

}
