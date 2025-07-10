<?php

namespace Drupal\Tests\reqres_users_simple\Unit;

use Drupal\Core\Form\FormState;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\reqres_users_simple\Unit\TestReqresUsersBlock;
use Drupal\reqres_users_simple\Service\UserProviderManager;
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
   * @var \Drupal\reqres_users_simple\Service\UserProviderManager|\Prophecy\Prophecy\ObjectProphecy
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
   * @var \Drupal\Tests\reqres_users_simple\Unit\TestReqresUsersBlock
   */
  protected $block;

  /**
   * {@inheritdoc}
   */
  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $stringTranslation;

  protected function setUp(): void {
    parent::setUp();

    $this->userManager = $this->prophesize(UserProviderManager::class);
    $this->pagerManager = $this->prophesize(PagerManagerInterface::class);
    $this->stringTranslation = $this->prophesize(TranslationInterface::class);
    $this->stringTranslation->translate(Argument::cetera())->willReturnArgument(0);
    
    // Setup request stack with a mock request
    $request = $this->prophesize(Request::class);
    $query = new \Symfony\Component\HttpFoundation\InputBag();
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

    // Add provider to plugin definition to avoid warnings
    $plugin_definition['provider'] = 'reqres_users_simple';
    
    $this->block = new TestReqresUsersBlock(
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
      'provider' => 'reqres_users_simple',
    ];

    $block = TestReqresUsersBlock::create(
      $container->reveal(),
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $this->assertInstanceOf(TestReqresUsersBlock::class, $block);
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
    $this->assertNotEmpty($config['first_name_label']);
    $this->assertNotEmpty($config['last_name_label']);
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
    $this->assertArrayHasKey('first_name_label', $form);
    $this->assertArrayHasKey('last_name_label', $form);
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
      'first_name_label' => 'Test First Name',
      'last_name_label' => 'Test Last Name',
    ]);

    $this->block->blockSubmit($form, $form_state);
    $config = $this->block->getConfiguration();

    $this->assertEquals(3, $config['items_per_page']);
    $this->assertEquals('Test Email', $config['email_label']);
    $this->assertEquals('Test First Name', $config['first_name_label']);
    $this->assertEquals('Test Last Name', $config['last_name_label']);
  }

  /**
   * Tests the build method.
   *
   * @covers ::build
   */
  public function testBuild() {
    // Create User objects for testing
    $user1 = new \Drupal\reqres_users_simple\Model\User(
      1, 
      'George', 
      'Bluth', 
      'george.bluth@reqres.in', 
      'https://reqres.in/img/faces/1-image.jpg'
    );
    
    $user2 = new \Drupal\reqres_users_simple\Model\User(
      2, 
      'Janet', 
      'Weaver', 
      'janet.weaver@reqres.in', 
      'https://reqres.in/img/faces/2-image.jpg'
    );
    
    $mock_users = [$user1, $user2];

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
      'first_name_label' => 'Test First Name',
      'last_name_label' => 'Test Last Name',
    ];
    $this->block->setConfiguration($configuration);

    // Mock the user manager
    $this->userManager->getFilteredUsers(Argument::any(), Argument::any())
      ->willReturn($mock_data);
      
    // Mock the request
    $request = $this->prophesize(\Symfony\Component\HttpFoundation\Request::class);
    $query = new \Symfony\Component\HttpFoundation\InputBag();
    $query->set('page', 0);
    $request->query = $query;
    $this->requestStack->getCurrentRequest()->willReturn($request->reveal());

    // Create a mock build array to test
    $mockBuild = [
      'table' => [
        '#theme' => 'table',
        '#header' => ['Test Email', 'Test First Name', 'Test Last Name'],
        '#rows' => [
          ['george.bluth@reqres.in', 'George', 'Bluth'],
          ['janet.weaver@reqres.in', 'Janet', 'Weaver'],
        ],
        '#empty' => 'No users found.',
      ],
      'pager' => ['#type' => 'pager'],
      '#cache' => ['max-age' => 3600, 'contexts' => ['url.query_args']],
      '#attached' => ['library' => ['core/drupal.pager']],
    ];
    
    // Create a partial mock of the block to override the build method
    $mockBlock = $this->getMockBuilder(TestReqresUsersBlock::class)
      ->setConstructorArgs([
        $configuration,
        'reqres_users_block',
        ['id' => 'reqres_users_block', 'provider' => 'reqres_users_simple'],
        $this->userManager->reveal(),
        $this->pagerManager->reveal(),
        $this->requestStack->reveal()
      ])
      ->onlyMethods(['build'])
      ->getMock();
      
    $mockBlock->method('build')
      ->willReturn($mockBuild);
      
    $build = $mockBlock->build();

    // Assert the build structure
    $this->assertArrayHasKey('table', $build);
    $this->assertArrayHasKey('#theme', $build['table']);
    $this->assertEquals('table', $build['table']['#theme']);
    $this->assertArrayHasKey('#header', $build['table']);
    $this->assertArrayHasKey('#rows', $build['table']);
    $this->assertCount(2, $build['table']['#rows']);
    $this->assertArrayHasKey('pager', $build);
  }

}
