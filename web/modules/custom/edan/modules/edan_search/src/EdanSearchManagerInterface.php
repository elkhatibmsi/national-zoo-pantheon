<?php

namespace Drupal\edan_search;

use Drupal\edan_search\Entity\EdanSearch;

/**
 * Interface EdanSearchManagerInterface.
 */
interface EdanSearchManagerInterface {

	/**
	 * @param \Drupal\edan_search\Entity\EdanSearch $edanSearch
	 * @return array processed edan search results
	 */
	public function executeSearch(EdanSearch $edanSearch);

	/**
	 * Returns a renderable array of edan search results.
	 *
	 * @param \Drupal\edan_search\Entity\EdanSearch $edanSearch
	 * @param array $searchResults
	 *   An associative array containing:
	 *   - rows: An array of processed edan records.
	 *   - facets: An array of facets from EDAN.
	 *   - optionset: The cached optionset object to avoid multiple invocations.
	 *   - settings: An array of key:value pairs of HTML/layout related settings.
	 *   - thumb: An associative array of slick thumbnail following the same
	 *     structure as the main display: $build['thumb']['items'], etc.
	 *
	 * @return array
	 *   The renderable array of both main and thumbnail slick instances.
	 */
	public function renderSearch(EdanSearch $edanSearch, $searchResults = []);

}
