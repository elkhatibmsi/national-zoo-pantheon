<?php

namespace Drupal\edan\Connector;

use Drupal\Core\Controller\ControllerBase;
use Drupal\edan\EdanClient\EdanClient;
use Drupal\edan_search\Form\EdanSearchGlobalForm;
use Drupal\edan_search\QueryBuilder\SearchQuery;

/**
 * Class EdanConnectorService.
 */
class EdanConnectorService {

  protected $edanClient;
  protected $edanConfigs;

  /**
   * Constructs a new EdanConnectorService object.
   */
  public function __construct(EdanClient $edanClient) {
    $this->edanClient = $edanClient;
  }


  /**
   * @param $value
   * @param string $fieldKey Can either be id or url (defaults to url)
   * @param string $recordType
   * @param string $endPoint
   *
   * @return array Data retrieved from EDAN
   * @throws \InvalidArgumentException when fieldKey is not "id" or "url"
   * @throws \Exception
   */
  public function getRecord($value, $fieldKey = 'url', $recordType = NULL, $endPoint = '') {
    // url:edanmdm:nasm_A19240007015
    if ('url' !== $fieldKey && 'id' !== $fieldKey) {
      throw new \InvalidArgumentException('fieldKey must be "id" or "url"');
    }
    $endPoint = $endPoint == '' ? '/content/%s/content/getContent.htm' : $endPoint;

    // url => edanmdm:xvz
    $parameters = array(
      $fieldKey => $value,
    );
		if ($recordType) $parameters['type'] = $recordType;
    return $this->edanClient->processRequest($parameters, $endPoint);
  }

  public function getLinkedIdContent($linkedId, $type = 'page') {
    $items = [];
  	// Set the endpoint.
    $endPoint = '/content/%s/content/getContentRows.htm';
		$parameters =[
			'linkedId' => $linkedId,
			'type' => $type,
			'rows' => 30
			];
		if ($type === 'page') {
		  $parameters['sort'] = 'id';
		  $parameters['sortDir'] = 'asc';
    }
		$response =  $this->edanClient->processRequest($parameters, $endPoint);
		if ($response['data']) {
			//continue querying EDAN for all linked contents
			$count = $response['data']['recordCount'];
			$i = count($response['data']['rows']);
			$items = array_merge($items, $response['data']['rows']);
			while ($i < $count) {
				$parameters['start'] = $i;
				$response =  $this->edanClient->processRequest($parameters, $endPoint);
				if ($response['data']) {
					$items = array_merge($items, $response['data']['rows']);
					$i = count($items);
				}
			}
		}
		return $items;
  }

  /**
   * @param string $fieldName
   *   Facet field for which to return data.
   * @param int $limit
   *   Specify max number of values to return. One of the following values:
   *   - (-1): No limit, return all values.
   *   - (n > 0): Return n values.
   * @param string $endPoint
   *
   * @return array
   *   Array containg facet data for $fieldName.
   *   @code
   *   array(
   *     0 => array(
   *       0 => 3D_YT,
   *       1 => 310,
   *     ),
   *     1 => array(
   *       0 => AAA,
   *       1 => 444,
   *     ),
   *   );
   *   @endcode
   *
   * @throws \InvalidArgumentException
   */
  public function getFacetField($fieldName, $endPoint = '', $limit = -1, $offset = 0, $sort = 'count') {
    if (!$fieldName) {
      throw new \InvalidArgumentException('fieldName cannot be empty or null');
    }

    if ($limit < -1) {
      throw new \InvalidArgumentException('limit cannot be less than -1');
    }

    if ('' == $endPoint) {
      $endPoint = '/metadata/%s/metadata/search.htm';
    }
   // $endPoint = sprintf($endPoint, $this->edanConfigs->getVersion());

    $parameters = [
      'q' => "*:*",
      'rows' => 0,
      'facet' => 'true',
      'facet.field' => $fieldName,
      'facet.limit' => $limit,
	    'facet.offset' => $offset,
	    'facet.sort' => $sort
    ];

    $response = $this->edanClient->processRequest($parameters, $endPoint);

    if ($response['data'] && isset($response['data']['facets'][$fieldName])) {
      return $response['data']['facets'][$fieldName];
    }
    else {
      //@todo log error
      return [];
    }

  }

  /**
   * @param $query
   * @param string $endPoint
   *
   * @return array
   */
  public function runQuery($query, $endPoint = '') {
    if ('' == $endPoint) {
      $endPoint = '/metadata/%s/metadata/search.htm';
    }

    //$endPoint = sprintf($endPoint, $this->edanConfigs->getVersion());
    $parameters = array(
      'q' => $query,
    );

    return $this->edanClient->processRequest($parameters, $endPoint);
  }

  /**
   * @param $parameters
   * @param string $endPoint
   *
   * @return array
   */
  public function runParamQuery(array $parameters, $endPoint = '') {
    if ('' == $endPoint) {
      $endPoint = '/metadata/%s/metadata/search.htm';
    }

    return $this->edanClient->processRequest($parameters, $endPoint);
  }

	public function getTypes() {
		$types = [];
		$exludes = [
			'3d_article',
			'3d_model',
			'3d_snapshot',
			'3d_tour',
			'edanead',
//			'edanlists',
			'file',
			'filetoken',
			'fulltext',
			'image',
			'notype',
			'unit',
			'objectlists'
		];
		// retrieve cache
		$cache = \Drupal::cache()->get('edan_types');

		if ($cache) { // return cached data
			$types = $cache->data;
		}
		else { // fetch data and cache it
			// fetch unit codes from edan
			$responseValues = $this->getFacetField('type', '', -1, 0, 'index');
	    if ($responseValues) {
			  // filter out invalid unit codes
			  foreach ($responseValues as $type) {
				  // avoid empty and null unit codes
				  if ($type[0] && $type[0] !== 'null' && !in_array($type[0], $exludes)) {
					  $types[] = $type[0];
				  }
			  }
        $types['edanlists'] = 'edanlists';
			  // convert to an associative array
			  // (e.g. [one, two] => [one => one, two => two])
			  $types = array_combine($types, $types);
			  // set cache
			  $expire = time() + (7 * 24 * 60 * 60);
			  \Drupal::cache()->set('edan_types', $types, $expire);
		  }
		}
		return $types;
	}

	/**
	 * Helper function to map location and si-unit facets to
	 * user-friendly name
	 * @param string $type
	 * @param bool $reset
	 * @return array
	 */
	public function get_museums($type = 'location', $reset = FALSE) {
		// retrieve cache
		$cache = \Drupal::cache()->get('edan_search_'. $type);
		if (!$cache || $reset) {
			$items = $units = $unit_codes = $list = $locations = [];
			$args = [
				'start' => 0,
				'rows' => 100,
				'fqs' => ['type:' . $type],
			];
			if ($type == 'location') {
				$args['sort'] = 'p.location.title_shortname asc';
			}
//			dump($args);
			$results = $this->runParamQuery($args);
//			dump($results);
			if ($results['data'] && $results['data']['recordCount'] > 0) {
				if ($type == 'location') {
					foreach ($results['data']['rows'] as $doc) {
						$units[$doc['id']] = array(
							'id' => $doc['id'],
							'title' => $doc['content']['title_shortname'],
							'count' => 0,
							'doc' => $doc
						);
						if (!empty($doc['content']['parent_unit_ID'])) {
							$locations[$doc['id']] = $doc['content']['parent_unit_ID'];
						}
						$items = array(
							'docs' => $units,
							'location_map' => $locations
						);
					}
				}
				else {
					foreach ($results['data']['rows'] as $doc) {
						$title = $doc['content']['short_title'];
						$units[$title] = array(
							'id' => $doc['id'],
							'title' => $title,
							'count' => 0,
							'units' => $doc['content']['unit_codes'],
							'doc' => $doc
						);
						foreach ($doc['content']['unit_codes'] as $code) {
							$unit_codes[$code] = $doc['id'];
						}
						$new_args = $args;
						$new_args['fq'] = array(
							'p.location.parent_unit_id:' . $doc['id']
						);
						$new_result = $this->runParamQuery($new_args);
						if ($new_result['data']) {
							foreach ($new_result['data']['rows'] as $_location) {
								$units[$doc['content']['short_title']]['locations'][$_location['id']] = $_location['id'];
								$locations[$_location['id']] = $doc['id'];
							}
						}

					}
					ksort($units);
					foreach ($units as $item) {
						$list[$item['id']] = $item;
					}
					$items = array(
						'docs' => $list,
						'unit_codes' => $unit_codes,
						'location_map' => $locations
					);
				}
				$expire = time() + (7 * 24 * 60 * 60);
				\Drupal::cache()->set('edan_search_' . $type, $items, $expire);

			}
		}
		else {
			$items = $cache->data;
		}
		return $items;

	}


}

