<?php

namespace Drupal\Tests\reqres_users_simple\Unit\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\reqres_users_simple\Event\UserFilterEvent;
use Drupal\reqres_users_simple\Model\User;
use Drupal\reqres_users_simple\Model\UserInterface;
use Drupal\reqres_users_simple\Provider\CompositeUserProvider;
use Drupal\reqres_users_simple\Service\UserProviderManager;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tests the UserProviderManager service.
 *
 * @group reqres_users_simple
 * @coversDefaultClass \Drupal\reqres_users_simple\Service\UserProviderManager
 */
class UserProviderManagerTest extends UnitTestCase {

  /**
   * The composite user provider.
   *
   * @var \Drupal\reqres_users_simple\Provider\CompositeUserProvider|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $compositeProvider;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $eventDispatcher;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $logger;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $loggerFactory;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $messenger;

  /**
   * The user provider manager.
   *
   * @var \Drupal\reqres_users_simple\Service\UserProviderManager
   */
  protected $userProviderManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->compositeProvider = $this->prophesize(CompositeUserProvider::class);
    $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
    $this->logger = $this->prophesize(LoggerChannelInterface::class);
    $this->loggerFactory = $this->prophesize(LoggerChannelFactoryInterface::class);
    $this->loggerFactory->get('reqres_users_simple')->willReturn($this->logger->reveal());
    $this->messenger = $this->prophesize(MessengerInterface::class);

    $this->userProviderManager = new UserProviderManager(
      $this->compositeProvider->reveal(),
      $this->eventDispatcher->reveal(),
      $this->messenger->reveal(),
      $this->loggerFactory->reveal()
    );
  }

  /**
   * Tests the getFilteredUsers method with data.
   *
   * @covers ::getFilteredUsers
   */
  public function testGetFilteredUsersWithData() {
    $user1 = new User(1, 'George', 'Bluth', 'george.bluth@reqres.in', 'https://reqres.in/img/faces/1-image.jpg');
    $user2 = new User(2, 'Janet', 'Weaver', 'janet.weaver@reqres.in', 'https://reqres.in/img/faces/2-image.jpg');
    
    $users = [$user1, $user2];
    $filtered_users = [$user1];
    
    $this->compositeProvider->getUsers(1, 6)->willReturn($users);
    $this->compositeProvider->getTotalPages(6)->willReturn(2);
    $this->compositeProvider->getTotalUsers()->willReturn(12);

    $this->eventDispatcher->dispatch(
      Argument::that(function ($event) use ($users, $filtered_users) {
        if (!$event instanceof UserFilterEvent) {
          return FALSE;
        }
        
        // Simulate an event subscriber filtering the users
        $event->setUsers($filtered_users);
        return TRUE;
      }),
      UserFilterEvent::EVENT_NAME
    )->shouldBeCalled();

    $result = $this->userProviderManager->getFilteredUsers(1, 6);
    
    $expected = [
      'data' => $filtered_users,
      'page' => 1,
      'per_page' => 6,
      'total' => 12,
      'total_pages' => 2,
    ];
    
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests the getFilteredUsers method with no data.
   *
   * @covers ::getFilteredUsers
   */
  public function testGetFilteredUsersWithNoData() {
    $users = [];
    
    $this->compositeProvider->getUsers(1, 6)->willReturn($users);
    $this->compositeProvider->getTotalPages(6)->willReturn(0);
    $this->compositeProvider->getTotalUsers()->willReturn(0);
    
    $this->eventDispatcher->dispatch(Argument::cetera())->shouldNotBeCalled();

    $result = $this->userProviderManager->getFilteredUsers(1, 6);
    
    $expected = [
      'data' => [],
      'page' => 1,
      'per_page' => 6,
      'total' => 0,
      'total_pages' => 0,
    ];
    
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests the getFilteredUsers method with error.
   *
   * @covers ::getFilteredUsers
   */
  public function testGetFilteredUsersWithError() {
    $this->compositeProvider->getUsers(1, 6)->willThrow(new \Exception('Test error'));
    
    $this->logger->error('Error in getFilteredUsers: @message', Argument::any())->shouldBeCalled();
    $this->messenger->addError('Error fetching users. Please try again later.')->shouldBeCalled();

    $result = $this->userProviderManager->getFilteredUsers(1, 6);
    
    $expected = [
      'data' => [],
      'page' => 1,
      'per_page' => 6,
      'total' => 0,
      'total_pages' => 0,
    ];
    
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests the getTotalPages method.
   *
   * @covers ::getTotalPages
   */
  public function testGetTotalPages() {
    $this->compositeProvider->getTotalPages(6)->willReturn(2);
    $result = $this->userProviderManager->getTotalPages(6);
    $this->assertEquals(2, $result);
  }

  /**
   * Tests the getTotalUsers method.
   *
   * @covers ::getTotalUsers
   */
  public function testGetTotalUsers() {
    $this->compositeProvider->getTotalUsers()->willReturn(12);
    $result = $this->userProviderManager->getTotalUsers();
    $this->assertEquals(12, $result);
  }

}
