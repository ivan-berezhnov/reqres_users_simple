<?php

namespace Drupal\Tests\reqres_users_simple\Unit;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\reqres_users_simple\Service\UserProviderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Test version of ReqresUsersBlock without t() function calls.
 */
class TestReqresUsersBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The user manager service.
   *
   * @var \Drupal\reqres_users_simple\Service\UserProviderManager
   */
  protected $userManager;

  /**
   * The pager manager.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    UserProviderManager $user_manager,
    PagerManagerInterface $pager_manager,
    RequestStack $request_stack
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->userManager = $user_manager;
    $this->pagerManager = $pager_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('reqres_users_simple.user_manager'),
      $container->get('pager.manager'),
      $container->get('request_stack')
    );
  }

  /**
   * Override t() to avoid container initialization.
   */
  public function t($string, array $args = [], array $options = []) {
    return $string;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'items_per_page' => 6,
      'email_label' => 'Email',
      'forename_label' => 'First Name',
      'surname_label' => 'Last Name',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['items_per_page'] = [
      '#type' => 'number',
      '#title' => 'Items per page',
      '#default_value' => $config['items_per_page'],
      '#min' => 1,
      '#max' => 250,
      '#required' => TRUE,
    ];

    $form['email_label'] = [
      '#type' => 'textfield',
      '#title' => 'Email field label',
      '#default_value' => $config['email_label'],
      '#required' => TRUE,
    ];

    $form['forename_label'] = [
      '#type' => 'textfield',
      '#title' => 'Forename field label',
      '#default_value' => $config['forename_label'],
      '#required' => TRUE,
    ];

    $form['surname_label'] = [
      '#type' => 'textfield',
      '#title' => 'Surname field label',
      '#default_value' => $config['surname_label'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['items_per_page'] = $values['items_per_page'];
    $this->configuration['email_label'] = $values['email_label'];
    $this->configuration['forename_label'] = $values['forename_label'];
    $this->configuration['surname_label'] = $values['surname_label'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $items_per_page = $config['items_per_page'];

    $current_page = $this->requestStack->getCurrentRequest()->query->getInt('page', 0);
    $api_page = $current_page + 1; // API uses 1-based page indexing

    // Get users for the current page
    $result = $this->userManager->getFilteredUsers($api_page, $items_per_page);
    $users = $result['data'] ?? [];
    $total_users = $result['total'] ?? 0;
    $total_pages = $result['total_pages'] ?? 0;

    // Create pager with the correct total
    $this->pagerManager->createPager($total_users, $items_per_page);

    // Build the render array
    $build = [];
  
    // Add the table
    $build['table'] = [
      '#theme' => 'table',
      '#header' => [
        $config['email_label'],
        $config['forename_label'],
        $config['surname_label'],
      ],
      '#rows' => [],
      '#empty' => 'No users found.',
    ];
  
    // Add cache metadata
    $build['#cache'] = [
      'max-age' => 3600,
      'contexts' => ['url.query_args'],
    ];
  
    // Attach pager library
    $build['#attached']['library'][] = 'core/drupal.pager';

    // Add users to the table
    foreach ($users as $user) {
      // Check if we're dealing with a UserInterface object or an array
      if (is_object($user) && method_exists($user, 'getEmail')) {
        $build['table']['#rows'][] = [
          $user->getEmail(),
          $user->getFirstName(),
          $user->getLastName(),
        ];
      } else {
        // Fallback for array format
        $build['table']['#rows'][] = [
          $user['email'] ?? '',
          $user['first_name'] ?? '',
          $user['last_name'] ?? '',
        ];
      }
    }

    // Add pager
    $build['pager'] = [
      '#type' => 'pager',
      '#tags' => [],
    ];

    return $build;
  }
}
