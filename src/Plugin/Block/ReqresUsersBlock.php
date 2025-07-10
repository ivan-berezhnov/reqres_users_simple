<?php

namespace Drupal\reqres_users_simple\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\reqres_users_simple\Service\UserManager;
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
   * @var \Drupal\reqres_users_simple\Service\UserManager
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
    UserManager $user_manager,
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
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'items_per_page' => 6,
      'email_label' => $this->t('Email'),
      'forename_label' => $this->t('First Name'),
      'surname_label' => $this->t('Last Name'),
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
      '#title' => $this->t('Items per page'),
      '#default_value' => $config['items_per_page'],
      '#min' => 1,
      '#max' => 12,
      '#required' => TRUE,
    ];

    $form['email_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email field label'),
      '#default_value' => $config['email_label'],
      '#required' => TRUE,
    ];

    $form['forename_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Forename field label'),
      '#default_value' => $config['forename_label'],
      '#required' => TRUE,
    ];

    $form['surname_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Surname field label'),
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
    
    // Get current page from the request
    $page = $this->requestStack->getCurrentRequest()->query->getInt('page', 0) + 1;
    
    // Get users for the current page
    $result = $this->userManager->getFilteredUsers($page, $items_per_page);
    $users = $result['data'] ?? [];
    $total_pages = $result['total_pages'] ?? 0;
    
    // Initialize the pager
    $this->pagerManager->createPager($result['total'] ?? 0, $items_per_page);
    
    // Build the table
    $build = [
      '#theme' => 'table',
      '#header' => [
        $config['email_label'],
        $config['forename_label'],
        $config['surname_label'],
      ],
      '#rows' => [],
      '#empty' => $this->t('No users found.'),
      '#cache' => [
        'max-age' => 3600,
        'contexts' => ['url.query_args'],
      ],
    ];
    
    // Add users to the table
    foreach ($users as $user) {
      $build['#rows'][] = [
        $user['email'] ?? '',
        $user['first_name'] ?? '',
        $user['last_name'] ?? '',
      ];
    }
    
    // Add pager
    $build['pager'] = [
      '#type' => 'pager',
    ];
    
    return $build;
  }

}
