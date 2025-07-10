<?php

namespace Drupal\Tests\reqres_users_simple\Unit\Provider;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\reqres_users_simple\Model\User;
use Drupal\reqres_users_simple\Provider\CompositeUserProvider;
use Drupal\reqres_users_simple\Provider\UserProviderInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Tests the CompositeUserProvider service.
 *
 * @group reqres_users_simple
 * @coversDefaultClass \Drupal\reqres_users_simple\Provider\CompositeUserProvider
 */
class CompositeUserProviderTest extends UnitTestCase {

  /**
   * The first user provider.
   *
   * @var \Drupal\reqres_users_simple\Provider\UserProviderInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $provider1;

  /**
   * The second user provider.
   *
   * @var \Drupal\reqres_users_simple\Provider\UserProviderInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $provider2;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $loggerFactory;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $logger;

  /**
   * The composite user provider.
   *
   * @var \Drupal\reqres_users_simple\Provider\CompositeUserProvider
   */
  protected $compositeProvider;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->provider1 = $this->prophesize(UserProviderInterface::class);
    $this->provider1->getProviderName()->willReturn('provider1');
    $this->provider1->getName()->willReturn('provider1');
    
    $this->provider2 = $this->prophesize(UserProviderInterface::class);
    $this->provider2->getProviderName()->willReturn('provider2');
    $this->provider2->getName()->willReturn('provider2');
    
    $this->logger = $this->prophesize(LoggerChannelInterface::class);
    $this->loggerFactory = $this->prophesize(LoggerChannelFactoryInterface::class);
    $this->loggerFactory->get('reqres_users_simple')->willReturn($this->logger->reveal());
    
    $this->compositeProvider = new CompositeUserProvider($this->loggerFactory->reveal());
    $this->compositeProvider->addProvider($this->provider1->reveal());
    $this->compositeProvider->addProvider($this->provider2->reveal());
  }

  /**
   * Tests the getUsers method.
   *
   * @covers ::getUsers
   * @covers ::addProvider
   */
  public function testGetUsers() {
    $user1 = new User(1, 'George', 'Bluth', 'george.bluth@reqres.in', 'https://reqres.in/img/faces/1-image.jpg');
    $user2 = new User(2, 'Janet', 'Weaver', 'janet.weaver@reqres.in', 'https://reqres.in/img/faces/2-image.jpg');
    $user3 = new User(3, 'Emma', 'Wong', 'emma.wong@reqres.in', 'https://reqres.in/img/faces/3-image.jpg');
    $user4 = new User(4, 'Eve', 'Holt', 'eve.holt@reqres.in', 'https://reqres.in/img/faces/4-image.jpg');
    
    $this->provider1->getUsers(1, 3, Argument::any())->willReturn([$user1, $user2]);
    $this->provider2->getUsers(1, 3, Argument::any())->willReturn([$user3, $user4]);
    
    // Mock logger to avoid errors
    $this->logger->error(Argument::cetera())->shouldNotBeCalled();
    
    $result = $this->compositeProvider->getUsers(1, 3);
    $this->assertCount(4, $result);
    
    // Check that all users are in the result
    $ids = array_map(function($user) { return $user->getId(); }, $result);
    $this->assertContains(1, $ids);
    $this->assertContains(2, $ids);
    $this->assertContains(3, $ids);
    $this->assertContains(4, $ids);
  }

  /**
   * Tests the getTotalPages method.
   *
   * @covers ::getTotalPages
   */
  public function testGetTotalPages() {
    $this->provider1->getTotalPages(6, Argument::any())->willReturn(2);
    $this->provider2->getTotalPages(6, Argument::any())->willReturn(3);
    
    $result = $this->compositeProvider->getTotalPages(6);
    $this->assertEquals(3, $result);
  }

  /**
   * Tests the getTotalUsers method.
   *
   * @covers ::getTotalUsers
   */
  public function testGetTotalUsers() {
    $this->provider1->getTotalUsers(Argument::any())->willReturn(12);
    $this->provider2->getTotalUsers(Argument::any())->willReturn(18);
    
    $result = $this->compositeProvider->getTotalUsers();
    $this->assertEquals(30, $result);
  }

  /**
   * Tests the getProviderName method.
   *
   * @covers ::getProviderName
   */
  public function testGetProviderName() {
    $result = $this->compositeProvider->getProviderName();
    $this->assertEquals('composite', $result);
  }

}
