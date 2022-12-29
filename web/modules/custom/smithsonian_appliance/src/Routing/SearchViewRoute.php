<?php

namespace Drupal\smithsonian_appliance\Routing;

use Symfony\Component\Routing\Route;

/**
 * Class DynamicRoute.
 */
class SearchViewRoute {

  const ROUTE_NAME = 'smithsonian_appliance.search_view';

  /**
   * Get route dynamically from system settings.
   */
  public function getRoute() {
    $gsaDisplaySettings = \Drupal::configFactory()->get('smithsonian_appliance.settings')->get('display_settings');

    $drupalPath = $gsaDisplaySettings['drupal_path'];
    $title = $gsaDisplaySettings['search_title'];

    if (NULL === $drupalPath) {
      return NULL;
    }

    // . '/{$searchQuery}/{resultSort}'.
    $routes[self::ROUTE_NAME] = new Route(
      '/' . $drupalPath . '/{search_query}/{result_sort}',
      [
        '_title' => $title,
        '_controller' => '\Drupal\smithsonian_appliance\Controller\SearchViewController::get',
        'search_query' => '',
        'result_sort' => '',
      ],
      [
        '_permission' => 'access smithsonian appliance content',
      ]
    );

    return $routes;

  }

}
