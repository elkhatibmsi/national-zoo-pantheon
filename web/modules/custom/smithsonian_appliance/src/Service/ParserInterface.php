<?php

namespace Drupal\smithsonian_appliance\Service;

/**
 * Defines an interface for parsing GSA responses.
 */
interface ParserInterface {

  /**
   * Parses response from GSA.
   *
   * @param string $xml
   *   Response body.
   * @param bool $useCached
   *   FALSE to re-parse and bypass static-cache.
   *
   * @return \Drupal\smithsonian_appliance\SearchResults\ResultSet
   *   Search response.
   */
  public function parseResponse($xml, $useCached = TRUE);

}
