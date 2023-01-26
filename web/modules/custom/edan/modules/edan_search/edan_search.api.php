<?php

/**
 * @file
 * Hooks and API provided by the Slick module.
 */

/**
 * @addtogroup hooks_edan_search
 * @{
 * Whenever your module invokes a hook you should document the use-case, and
 * parameters for that hook in a {MODULE_NAME}.api.php file. The standard is to
 * create a docblock where the first line is a summary starting with an
 * imperative verb. Followed by a more detailed explanation of when the hook
 * is triggered and documentation for any parameters.
 *
 * The body of the function should contain a functional example.
 *
 * The contents of this file are never loaded, or executed, it is purely for
 * documentation purposes.
 *
 * @link https://www.drupal.org/docs/develop/coding-standards/api-documentation-and-comment-standards#hooks
 * Read the standards for documenting hooks. @endlink
 *
 * Examples:
 * @see file.api.php
 * @see node.api.php
 */

/**
 * Allow other modules to add EDAN endpoints to the custom search form.
 *
 * @see \Drupal\edan_search\Form\EdanSearchEntityForm::getEndpoints()
 * @see config/install/edan_search.optionset.default.yml
 * @ingroup edan_search_api
 */
function hook_edan_endpoints_alter(&$endpoints) {
  // Add getContentRows to list of available endpoints.
	$endpoints['/content/%s/content/getContentRows.htm'] = t('Content Rows');
}

/**
 * Allow other modules to alter search results.
 *
 * @see \Drupal\edan_search\EdanSearchManager::processRows()
 * $rows['fields'] is what is used in the edan_search theme template
 * @ingroup edan_search_api
 */
function hook_edan_search_results_alter(&$rows, \Drupal\edan_search\Entity\EdanSearch $edanSearch) {

}

/**
 * Allow other modules to alter facets after it has been processed.
 *
 * @see \Drupal\edan_search\EdanSearchManager::renderSearch()
 * @
 * @ingroup edan_search_api
 */
function hook_edan_search_facets_alter(&$facets, \Drupal\edan_search\EdanSearchManager $edanSearchManager) {

}

/**
 * Allow other modules to alter active facets after it has been processed.
 *
 * @see \Drupal\edan_search\EdanSearchManager::renderSearch()
 * @
 * @ingroup edan_search_api
 */
function hook_edan_search_facets_active_alter(&$activeFacets, \Drupal\edan_search\EdanSearchManager $edanSearchManager) {

}


/**
 * @} End of "addtogroup hooks".
 */
