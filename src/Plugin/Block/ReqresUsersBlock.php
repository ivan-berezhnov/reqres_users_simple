<?php

namespace Drupal\reqres_users_simple\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\reqres_users_simple\Service\UserProviderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a block that displays users from the Reqres API.
 *
 * @Block(
 *   id = "reqres_users_block",
 *   admin_label = @Translation("Reqres Users Block"),
 *   category = @Translation("Custom")
 * )
 */
class ReqresUsersBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    UserProviderManager $user_manager,
    PagerManagerInterface $pager_manager,
    RequestStack $request_stack,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->userManager = $user_manager;
    $this->pagerManager = $pager_manager;
    $this->requestStack = $request_stack;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('reqres_users_simple.user_provider_manager'),
      $container->get('pager.manager'),
      $container->get('request_stack'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'items_per_page' => 6,
      'email_label' => $this->t('Email'),
      'forename_label' => $this->t('First Name'),
      'surname_label' => $this->t('Last Name'),
      'cache_max_age' => 3600,
      'endpoint_url' => 'https://reqres.in/api',
      'timeout' => 30,
      'connect_timeout' => 10,
      'rate_limit' => 60,
      'rate_period' => 60,
      'rate_remaining' => 60,
      'cache_duration' => 3600,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
    $module_config = $this->configFactory->get('reqres_users_simple.settings');
    
    // API Settings
    $form['api'] = [
      '#type' => 'details',
      '#title' => $this->t('API Settings'),
      '#description' => $this->t('Configure connection parameters for the Reqres API service.'),
      '#open' => TRUE,
    ];
    
    $form['api']['endpoint_url'] = [
      '#type' => 'url',
      '#title' => $this->t('API Endpoint URL'),
      '#description' => $this->t('The base URL for the Reqres API. Default is https://reqres.in/api. This URL will be used for all API requests.'),
      '#default_value' => $config['endpoint_url'],
      '#required' => TRUE,
    ];
    
    $form['api']['timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Request Timeout'),
      '#description' => $this->t('Maximum number of seconds to wait for a response from the API. Default: 30.'),
      '#default_value' => $config['timeout'],
      '#min' => 1,
      '#max' => 120,
      '#required' => TRUE,
    ];
    
    $form['api']['connect_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Connection Timeout'),
      '#description' => $this->t('Maximum number of seconds to wait while trying to connect to the API. Default: 10.'),
      '#default_value' => $config['connect_timeout'],
      '#min' => 1,
      '#max' => 60,
      '#required' => TRUE,
    ];
    
    // Rate Limit Settings
    $form['api']['rate_limit_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Rate Limiting'),
      '#description' => $this->t('Configure rate limiting parameters to avoid API throttling.'),
      '#open' => FALSE,
    ];
    
    $form['api']['rate_limit_settings']['rate_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Rate Limit'),
      '#description' => $this->t('Maximum number of requests allowed within the rate period. Default: 60.'),
      '#default_value' => $config['rate_limit'],
      '#min' => 1,
      '#required' => TRUE,
    ];
    
    $form['api']['rate_limit_settings']['rate_period'] = [
      '#type' => 'number',
      '#title' => $this->t('Rate Period (seconds)'),
      '#description' => $this->t('Time period in seconds for the rate limit. Default: 60 (1 minute).'),
      '#default_value' => $config['rate_period'],
      '#min' => 1,
      '#required' => TRUE,
    ];
    
    $form['api']['rate_limit_settings']['rate_remaining'] = [
      '#type' => 'number',
      '#title' => $this->t('Initial Remaining Requests'),
      '#description' => $this->t('Initial number of remaining requests. Default: same as Rate Limit.'),
      '#default_value' => $config['rate_remaining'],
      '#min' => 0,
      '#required' => TRUE,
    ];
    
    // Cache Settings
    $form['cache'] = [
      '#type' => 'details',
      '#title' => $this->t('Cache Settings'),
      '#description' => $this->t('Configure API response caching.'),
      '#open' => TRUE,
    ];
    
    $form['cache']['cache_duration'] = [
      '#type' => 'number',
      '#title' => $this->t('API Cache Duration (seconds)'),
      '#description' => $this->t('How long to cache API responses. 0 = no caching, 3600 = 1 hour. Recommended: 300-3600 seconds.'),
      '#default_value' => $config['cache_duration'],
      '#min' => 0,
      '#required' => TRUE,
    ];
    
    // Pagination Settings
    $form['pagination'] = [
      '#type' => 'details',
      '#title' => $this->t('Pagination Settings'),
      '#description' => $this->t('Configure pagination for the users table.'),
      '#open' => TRUE,
    ];
    
    $form['pagination']['items_per_page'] = [
      '#type' => 'number',
      '#title' => $this->t('Items per page'),
      '#description' => $this->t('Number of users to display per page. Recommended: 3-12.'),
      '#default_value' => $config['items_per_page'],
      '#min' => 1,
      '#max' => 20,
      '#required' => TRUE,
    ];
    
    // Display Settings
    $form['display'] = [
      '#type' => 'details',
      '#title' => $this->t('Display Settings'),
      '#description' => $this->t('Configure how user data is displayed in the block and how long the rendered block is cached.'),
      '#open' => TRUE,
    ];
    
    $form['display']['email_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email field label'),
      '#description' => $this->t('Label for the email column in the users table. Default: "Email".'),
      '#default_value' => $config['email_label'],
      '#required' => TRUE,
    ];
    
    $form['display']['forename_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Forename field label'),
      '#description' => $this->t('Label for the first name column in the users table. Default: "First Name".'),
      '#default_value' => $config['forename_label'],
      '#required' => TRUE,
    ];
    
    $form['display']['surname_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Surname field label'),
      '#description' => $this->t('Label for the last name column in the users table. Default: "Last Name".'),
      '#default_value' => $config['surname_label'],
      '#required' => TRUE,
    ];
    
    $form['display']['cache_max_age'] = [
      '#type' => 'number',
      '#title' => $this->t('Block cache max age (seconds)'),
      '#description' => $this->t('Cache lifetime for this block\'s rendered output. This is separate from the API cache duration. 0 = no caching, 3600 = 1 hour. Recommended: 300-3600 seconds.'),
      '#default_value' => $config['cache_max_age'],
      '#min' => 0,
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
    
    // Save all settings to block configuration
    $this->configuration['items_per_page'] = $values['pagination']['items_per_page'];
    $this->configuration['email_label'] = $values['display']['email_label'];
    $this->configuration['forename_label'] = $values['display']['forename_label'];
    $this->configuration['surname_label'] = $values['display']['surname_label'];
    $this->configuration['cache_max_age'] = $values['display']['cache_max_age'];
    $this->configuration['endpoint_url'] = $values['api']['endpoint_url'];
    $this->configuration['timeout'] = $values['api']['timeout'];
    $this->configuration['connect_timeout'] = $values['api']['connect_timeout'];
    $this->configuration['rate_limit'] = $values['api']['rate_limit_settings']['rate_limit'];
    $this->configuration['rate_period'] = $values['api']['rate_limit_settings']['rate_period'];
    $this->configuration['rate_remaining'] = $values['api']['rate_limit_settings']['rate_remaining'];
    $this->configuration['cache_duration'] = $values['cache']['cache_duration'];
    
    // Clear caches to apply changes
    drupal_flush_all_caches();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $items_per_page = $config['items_per_page'];

    $current_page = $this->requestStack->getCurrentRequest()->query->getInt('page', 0);
    $api_page = $current_page + 1; // API uses 1-based page indexing
    
    // Extract API settings from block configuration
    $api_settings = [
      'endpoint_url' => $config['endpoint_url'],
      'timeout' => $config['timeout'],
      'connect_timeout' => $config['connect_timeout'],
      'rate_limit' => $config['rate_limit'],
      'rate_period' => $config['rate_period'],
      'rate_remaining' => $config['rate_remaining'],
      'cache_duration' => $config['cache_duration'],
    ];

    // Get users for the current page
    $result = $this->userManager->getFilteredUsers($api_page, $items_per_page, $api_settings);
    $users = $result['data'] ?? [];
    $total_users = $result['total'] ?? 0;
    $total_pages = $result['total_pages'] ?? 0;

    // Initialize the pager with the total number of users
  
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
      '#empty' => $this->t('No users found.'),
    ];
  
    // Add cache metadata
    $build['#cache'] = [
      'max-age' => $config['cache_max_age'],
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
