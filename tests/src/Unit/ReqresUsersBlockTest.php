<?php

namespace Drupal\Tests\reqres_users_simple\Unit;

use Drupal\Core\Form\FormState;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\reqres_users_simple\Plugin\Block\ReqresUsersBlock;
use Drupal\reqres_users_simple\Service\UserManager;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the ReqresUsersBlock plugin.
 *
 * @group reqres_users_simple
 * @coversDefaultClass \Drupal\reqres_users_simple\Plugin\Block\ReqresUsersBlock
 */
class ReqresUsersBlockTest extends UnitTestCase {

  /**
   * The user manager service.
   *
   * @var \Drupal\reqres_users_simple\Service\UserManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $userManager;

  /**
   * The pager manager.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $pagerManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $requestStack;

  /**
   * The block plugin.
   *
   * @var \Drupal\reqres_users_simple\Plugin\Block\ReqresUsersBlock
   */
  protected $block;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->userManager = $this->prophesize(UserManager::class);
    $this->pagerManager = $this->prophesize(PagerManagerInterface::class);
    
    // Setup request stack with a mock request
    $request = $this->prophesize(Request::class);
    $query = new \Symfony\Component\HttpFoundation\ParameterBag();
    $query->set('page', 0);
    $request->query = $query;
    
    $this->requestStack = $this->prophesize(RequestStack::class);
    $this->requestStack->getCurrentRequest()->willReturn($request->reveal());

    $configuration = [];
    $plugin_id = 'reqres_users_block';
    $plugin_definition = [
      'id' => 'reqres_users_block',
      'admin_label' => 'Reqres Users Block',
      'category' => 'Custom',
    ];

    $this->block = new ReqresUsersBlock(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $this->userManager->reveal(),
      $this->pagerManager->reveal(),
      $this->requestStack->reveal()
    );
  }

  /**
   * Tests the create method.
   *
   * @covers ::create
   */
  public function testCreate() {
    $container = $this->prophesize(ContainerInterface::class);
    $container->get('reqres_users_simple.user_manager')
      ->willReturn($this->userManager->reveal());
    $container->get('pager.manager')
      ->willReturn($this->pagerManager->reveal());
    $container->get('request_stack')
      ->willReturn($this->requestStack->reveal());

    $configuration = [];
    $plugin_id = 'reqres_users_block';
    $plugin_definition = [
      'id' => 'reqres_users_block',
      'admin_label' => 'Reqres Users Block',
      'category' => 'Custom',
    ];

    $block = ReqresUsersBlock::create(
      $container->reveal(),
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $this->assertInstanceOf(ReqresUsersBlock::class, $block);
  }

  /**
   * Tests the defaultConfiguration method.
   *
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration() {
    $config = $this->block->defaultConfiguration();
    $this->assertEquals(6, $config['items_per_page']);
    $this->assertNotEmpty($config['email_label']);
    $this->assertNotEmpty($config['forename_label']);
    $this->assertNotEmpty($config['surname_label']);
  }

  /**
   * Tests the blockForm method.
   *
   * @covers ::blockForm
   */
  public function testBlockForm() {
    $form = [];
    $form_state = new FormState();

    $form = $this->block->blockForm($form, $form_state);

    $this->assertArrayHasKey('items_per_page', $form);
    $this->assertArrayHasKey('email_label', $form);
    $this->assertArrayHasKey('forename_label', $form);
    $this->assertArrayHasKey('surname_label', $form);
  }

  /**
   * Tests the blockSubmit method.
   *
   * @covers ::blockSubmit
   */
  public function testBlockSubmit() {
    $form = [];
    $form_state = new FormState();
    $form_state->setValues([
      'items_per_page' => 3,
      'email_label' => 'Test Email',
      'forename_label' => 'Test First Name',
      'surname_label' => 'Test Last Name',
    ]);

    $this->block->blockSubmit($form, $form_state);
    $config = $this->block->getConfiguration();

    $this->assertEquals(3, $config['items_per_page']);
    $this->assertEquals('Test Email', $config['email_label']);
    $this->assertEquals('Test First Name', $config['forename_label']);
    $this->assertEquals('Test Last Name', $config['surname_label']);
  }

  /**
   * Tests the build method.
   *
   * @covers ::build
   */
  public function testBuild() {
    $mock_users = [
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

    $mock_data = [
      'page' => 1,
      'per_page' => 6,
      'total' => 12,
      'total_pages' => 2,
      'data' => $mock_users,
    ];

    // Set up configuration
    $configuration = [
      'items_per_page' => 6,
      'email_label' => 'Test Email',
      'forename_label' => 'Test First Name',
      'surname_label' => 'Test Last Name',
    ];
    $this->block->setConfiguration($configuration);

    // Request handling is now done via dependency injection

    // Mock the user manager
    $this->userManager->getFilteredUsers(1, 6)->willReturn($mock_data);

    // Mock the pager manager
    $this->pagerManager->createPager(12, 6)->shouldBeCalled();

    // Build the block
    $build = $this->block->build();

    // Assert the build structure
    $this->assertArrayHasKey('#theme', $build);
    $this->assertEquals('table', $build['#theme']);
    $this->assertArrayHasKey('#header', $build);
    $this->assertArrayHasKey('#rows', $build);
    $this->assertCount(2, $build['#rows']);
    $this->assertArrayHasKey('pager', $build);
  }

}
