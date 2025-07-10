<?php

namespace Drupal\reqres_users_simple\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\reqres_users_simple\Service\UserProviderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for demonstrating multiple API integration.
 */
class MultiApiDemoController extends ControllerBase {

  /**
   * The user provider manager.
   *
   * @var \Drupal\reqres_users_simple\Service\UserProviderManager
   */
  protected $userProviderManager;

  /**
   * Constructs a new MultiApiDemoController.
   *
   * @param \Drupal\reqres_users_simple\Service\UserProviderManager $user_provider_manager
   *   The user provider manager.
   */
  public function __construct(UserProviderManager $user_provider_manager) {
    $this->userProviderManager = $user_provider_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('reqres_users_simple.user_provider_manager')
    );
  }

  /**
   * Displays users from multiple APIs.
   *
   * @return array
   *   Render array for the page.
   */
  public function content() {
    $page = $this->getRequest()->query->get('page', 1);
    $per_page = $this->getRequest()->query->get('per_page', 6);
    
    $result = $this->userProviderManager->getFilteredUsers($page, $per_page);
    
    $build = [
      '#theme' => 'item_list',
      '#title' => $this->t('Users from Multiple APIs (Page @page of @total_pages)', [
        '@page' => $result['page'],
        '@total_pages' => $result['total_pages'],
      ]),
      '#items' => [],
      '#cache' => [
        'max-age' => 3600,
        'contexts' => ['url.query_args'],
      ],
    ];
    
    foreach ($result['data'] as $user) {
      $build['#items'][] = [
        '#markup' => $this->t('@first_name @last_name (@email)', [
          '@first_name' => $user['first_name'],
          '@last_name' => $user['last_name'],
          '@email' => $user['email'],
        ]),
      ];
    }
    
    // Add pager links
    $build['pager'] = [
      '#type' => 'pager_links',
      '#quantity' => 5,
      '#route_name' => '<current>',
      '#pages' => $result['total_pages'],
      '#current_page' => $page - 1,
      '#attached' => [
        'library' => ['core/drupal.pager'],
      ],
    ];
    
    return $build;
  }

}
