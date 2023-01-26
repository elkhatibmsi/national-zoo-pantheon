<?php

namespace Drupal\edan_tabs\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides dynamic routes for edan record type.
 */
class EdanTabRoutes implements ContainerInjectionInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new search route subscriber.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The search page Manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route;
   *   An array of route objects.
   */
  public function routes() {
    $routes = [];

    $edan_tabs = $this->entityTypeManager->getStorage('edan_tab')->loadMultiple();
		foreach($edan_tabs as $entity_id => $entity) {
			$path = $entity->getPath();
      $route_id = "edan_tabs.view_$entity_id";

      $routes[$route_id] = new Route(
        $path,
        [
          '_controller' => 'Drupal\edan_tabs\Controller\EdanTabController::view',
          '_title' => $entity->label(),
          'entity' => $entity_id,
        ],
        [
          '_permission' => 'access content',
        ],
        [
          'parameters' => [
            'entity' => [
              'type' => 'entity:edan_tab',
            ],
          ],
        ]
      );
      $routes[$route_id . '_tab'] = new Route(
        $path .'/{edan_search}',
        [
          '_controller' => 'Drupal\edan_tabs\Controller\EdanTabController::view',
          '_title' => $entity->label(),
          'entity' => $entity_id,
        ],
        [
          '_permission' => 'access content',
        ],
        [
          'parameters' => [
            'entity' => [
              'type' => 'entity:edan_tab',
            ],
            'edan_search' => [
              'type' => 'entity:edan_search'
            ]
          ],
        ]
      );
//      foreach($entity->getSearches() as $delta => $search) {
//        $search_path = $entity->hasLanding() ? $path .'/'. $search['search'] : ($delta === 0 ? $path : $path .'/'. $search['search']);
//
//      }

		}

		return $routes;
	}

}
