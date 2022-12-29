<?php

namespace Drupal\smithsonian_appliance\Service;

use Drupal\smithsonian_appliance\SearchResults\SearchQuery;

/**
 * Defines an interface for performing a GSA search.
 */
interface SearchInterface {

  /**
   * Performs search.
   *
   * @param \Drupal\smithsonian_appliance\SearchResults\SearchQuery $query
   *   Search query.
   *
   * @return \Drupal\smithsonian_appliance\SearchResults\ResultSet
   *   Search result set.
   */
  public function search(SearchQuery $query);

}
