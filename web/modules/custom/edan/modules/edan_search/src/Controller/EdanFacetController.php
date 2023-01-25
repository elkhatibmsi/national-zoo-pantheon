<?php
namespace Drupal\edan_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\edan_search\EdanSearchManagerInterface;
use Drupal\edan_search\Entity\EdanSearchInterface;
use Drupal\edan\EdanFacets;
use Drupal\Component\Utility\Xss;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;


class EdanFacetController extends ControllerBase {


  /**
   * The edanFacet object.
   *
   * @var \Drupal\edan\EdanFacets
   */
  private $edanFacets;

  /**
   * The EDAN Search Manager Service
   *
   * @var \Drupal\edan_search\EdanSearchManagerInterface
   */
  private $edanSearchManager;

  private $args = [];


  private $show_all = FALSE;

  /**
   * Drupal\edan\Connector\EdanConnectorService definition.
   *
   * @var \Drupal\edan\Connector\EdanConnectorService
   */
  protected $edanConnector;

  /**
   *
   * @var \Drupal\edan_search\Entity\EdanSearchInterface
   */
  protected $edanSearch;

	/**
	 * Constructs a new edan facet controller.
	 *
	 * @param \Drupal\edan_search\EdanSearchManagerInterface $edanSearchManager
	 *   The edan record manager.
	 */

	public function __construct(EdanSearchManagerInterface $edanSearchManager) {
		$this->edanSearchManager = $edanSearchManager;
	}

	public static function create(ContainerInterface $container) {
		return new static(
			$container->get('edan_search.manager')
    );
	}



	public function view(EdanSearchInterface $edan_search, Request $request) {
    $params = $request->query->all();
    $this->show_all = $params['show_all'] ?? FALSE;
    $edan_search->setRows(0);
    $facetSettings = $edan_search->getFacetSettings();
    $facetSettings['facet_status'] = TRUE;
    $this->edanSearch = $edan_search;
    $page = $params['page'] ?? 0;
    if (isset($params['facet_field'])) $facetSettings['facet_fields'] = Xss::filter($params['facet_field'], []);
    if (isset($params['facet_sort'])) $facetSettings['facet_sort'] = $params['facet_sort'];
    if (isset($params['facet_prefix'])) $facetSettings['facet.prefix'] =  Xss::filter($params['facet_prefix'], []);

    $facetSettings['facet_offset'] = $page === 1 ? $facetSettings['facet_limit'] : $page * 100;
    $facetSettings['facet_limit'] = 100;

    $edan_search->setSettings('facets', $facetSettings);
    $this->edanSearchManager->setSearch($edan_search);
    $args = $this->edanSearchManager->buildParameters();
    if (isset($args['bq'])) unset($args['bq']);
    $this->args = $args;
    $this->edanConnector = $this->edanSearchManager->getEdanConnector();
    $this->edanSearch = $edan_search;
    $facets = $this->getFacets($page);

    if ($facets) {
      $facetProcessor = new EdanFacets($this->edanSearchManager->getPageParameters(),  $facetSettings['facet_labels'],  $facetSettings['facets_hide']);
      return new JsonResponse($facetProcessor->process_facets($facets));
    }
    else {
      return new JsonResponse($facets);

    }

//    return [
//      '#markup' => '<p>' . $this->t('Simple page: The quick brown fox jumps over the lazy dog.') . '</p>',
//    ];
	}

	private function getFacets($page, &$items = []) {
	  $this->args['facet.offset'] = $this->args['facet.limit'] * $page;
    $results = $this->edanConnector->runParamQuery($this->args, $this->edanSearch->getSettings('edan_settings', 'endpoint'));
    $continue = FALSE;
    if ($results['data'] && $results['data']['recordCount'] > 0) {
      foreach($results['data']['facets'] as $key => $facet_values) {
        $items[$key] = isset($items[$key]) ? array_merge($items[$key], $facet_values) : $facet_values;
        $continue = $continue === TRUE ? $continue : $this->show_all !=FALSE && count($facet_values) === $this->args['facet.limit'] && $page < 10;
      }
    }
    if ($continue) {
      $page++;
      $this->getFacets($page, $items);
    }
	  return $items;
  }

}
