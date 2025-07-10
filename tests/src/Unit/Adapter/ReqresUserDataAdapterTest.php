<?php

namespace Drupal\Tests\reqres_users_simple\Unit\Adapter;

use Drupal\reqres_users_simple\Adapter\ReqresUserDataAdapter;
use Drupal\reqres_users_simple\Model\User;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the ReqresUserDataAdapter.
 *
 * @group reqres_users_simple
 * @coversDefaultClass \Drupal\reqres_users_simple\Adapter\ReqresUserDataAdapter
 */
class ReqresUserDataAdapterTest extends UnitTestCase {

  /**
   * The Reqres user data adapter.
   *
   * @var \Drupal\reqres_users_simple\Adapter\ReqresUserDataAdapter
   */
  protected $adapter;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adapter = new ReqresUserDataAdapter();
  }

  /**
   * Tests the convertToUser method.
   *
   * @covers ::convertToUser
   */
  public function testConvertToUser() {
    $data = [
      'id' => 1,
      'email' => 'george.bluth@reqres.in',
      'first_name' => 'George',
      'last_name' => 'Bluth',
      'avatar' => 'https://reqres.in/img/faces/1-image.jpg',
    ];

    $user = $this->adapter->convertToUser($data);

    $this->assertInstanceOf(User::class, $user);
    $this->assertEquals(1, $user->getId());
    $this->assertEquals('George', $user->getFirstName());
    $this->assertEquals('Bluth', $user->getLastName());
    $this->assertEquals('george.bluth@reqres.in', $user->getEmail());
    $this->assertEquals('https://reqres.in/img/faces/1-image.jpg', $user->getAvatar());
  }

  /**
   * Tests the convertToUser method with missing data.
   *
   * @covers ::convertToUser
   */
  public function testConvertToUserWithMissingData() {
    $data = [
      'id' => 1,
      'email' => 'george.bluth@reqres.in',
      // Missing first_name
      'last_name' => 'Bluth',
      // Missing avatar
    ];

    $user = $this->adapter->convertToUser($data);

    $this->assertInstanceOf(User::class, $user);
    $this->assertEquals(1, $user->getId());
    $this->assertEquals('', $user->getFirstName());
    $this->assertEquals('Bluth', $user->getLastName());
    $this->assertEquals('george.bluth@reqres.in', $user->getEmail());
    $this->assertEquals('', $user->getAvatar());
  }

}
