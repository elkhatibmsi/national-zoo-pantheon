<?php

namespace Drupal\edan_search;

use Drupal\edan\EdanManagerBase;
use Drupal\edan_search\Entity\EdanSearch;
use Drupal\edan\SearchParams;
use Drupal\edan\EdanFacets;
use Drupal\edan_record\EdanRecordMedia;
use Drupal\edan_record\EdanRecordProcessInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EdanSearchManager.
 */
class EdanSearchManager extends EdanManagerBase implements EdanSearchManagerInterface {

  /**
   * Drupal\edan\SearchParams definition.
   *
   * @var \Drupal\edan\SearchParams
   */
  protected $edanSearchParams;

	/**
	 * Drupal\edan_record\EdanRecordProcessInterface definition.
	 *
	 * @var \Drupal\edan_record\EdanRecordProcessInterface
	 */
	protected $edanRecordProcess;

  protected $parameters = [];

  /**
   * @var Drupal\edan_record\EdanRecordMedia;
   */
  protected $edanMedia;

	/**
	 * Drupal\edan_search\Entity\EdanSearch object.
	 *
	 * @var \Drupal\edan_search\Entity\EdanSearchInterface
	 */
  protected $edanSearch;

  protected $pageParameters = [];
  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

	/**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->setServices($container->get('edan.search_params'), $container->get('edan_record.process'), $container->get('edan_record.media'), $container->get('theme.manager'));
	  return $instance;
  }
	/**
	 * Sets needed services for EdanSearch.
	 */
	public function setServices(SearchParams $searchParams, EdanRecordProcessInterface $edanRecordProcess, EdanRecordMedia $edanRecordMedia, ThemeManagerInterface $themeManager) {
		$this->edanSearchParams = $searchParams;
    $this->edanRecordProcess = $edanRecordProcess;
    $this->edanMedia = $edanRecordMedia;
    $this->themeManager = $themeManager;
  }

  public function getSearch() {
		if (!$this->edanSearch){
			$this->edanSearch = $this->entityLoad('default');
		}
	  $this->endPoint = $this->edanSearch->getSettings('edan_settings', 'endpoint');
	  return $this->edanSearch;
  }

  public function setSearch(EdanSearch $edanSearch) {
		$this->edanSearch = $edanSearch;
  }

	public function getParameters() {
		return $this->parameters;
	}
  public function buildParameters() {

//	  // create SearchParams to get page parameters
//	  $this->edanSearchParams = new SearchParams($rows);
    $args = $this->getPageParameters();
    $rows = $this->edanSearch->getSettings('render_settings', 'results_per_page');
    $page =  \Drupal::service('pager.parameters')->findPage();
    $args['start'] = $page * $rows;
    $args['rows'] = $rows;
    $this->edanSearchParams->setRows($args['rows']);
    unset($args['search_all']);
    $edan_settings = $this->edanSearch->getSettings('edan_settings');
    $facet_settings = $this->edanSearch->getSettings('facets');
    $args['fqs'] = $edan_settings['fq_params'] ? array_merge($edan_settings['fq_params'], $args['fqs'])   : $args['fqs'];
    if ($edan_settings['record_types']) {
      $args['fqs'][] = 'type:'. implode(' OR type:', $edan_settings['record_types']);
    }
    $args['q'] = $edan_settings['default_q'] ? ($args['q'] ? $args['q'] . ' + '. $edan_settings['default_q'] : $edan_settings['default_q']) : $args['q'];

    if ($edan_settings['default_to_local_units'] && isset($edan_settings['local_units']) && !$this->pageParameters['search_all']) {
      $args['fqs'][] = 'unit_code:"' . implode('" OR unit_code:"', $edan_settings['local_units']) .'"';
    }
    if ((isset($edan_settings['default_to_online_media']) && $edan_settings['default_to_online_media']) || isset($this->pageParameters['online_media'])) {
      $args['fqs'][] = 'online_visual_material:true OR p.objectgroup.feature.thumbnail:*';
    }
    if(isset($edan_settings['bq']) && !empty($edan_settings['bq'])) {
      $args['bq'] = $edan_settings['bq'];
    }
    if(isset($edan_settings['bf']) && !empty($edan_settings['bf'])) {
      $args['bf'] = $edan_settings['bf'];
    }
    if(isset($edan_settings['sort']) && $edan_settings['sort']) {
      $args['sort'] = isset($args['sort']) ? $args['sort'] : $edan_settings['sort'];
    }
    if (isset($edan_settings['other_params']) && is_array($edan_settings['other_params'])) {
    	foreach($edan_settings['other_params'] as $key => $param) {
    		$args[$key] = $param;
	    }
    }
    if ($facet_settings['facet_status'] == TRUE) {
      $args['facet'] = 'true';
      if (isset($facet_settings['facet_limit'])) $args['facet.limit'] = $facet_settings['facet_limit'];
      if (isset($facet_settings['facet_fields']) && !empty($facet_settings['facet_fields'])) $args['facet.field'] = is_array($facet_settings['facet_fields']) ? $facet_settings['facet_fields'] : [$facet_settings['facet_fields']];
      if (isset($facet_settings['facet_offset'])) $args['facet.offset'] = $facet_settings['facet_offset'];
      if (isset($facet_settings['facet_prefix'])) $args['facet.prefix'] = $facet_settings['facet_prefix'];
      if (isset($facet_settings['facet_sort']) && in_array($facet_settings['facet_sort'], ['index', 'count'])) $args['facet.sort'] = $facet_settings['facet_sort'];
    }

    $this->moduleHandler->alter('edan_search_parameters', $args, $this->edanSearch);
    $this->themeManager->alter('edan_search_parameters', $args, $this->edanSearch);

    $this->parameters = $args;
    return $args;
  }

  private function processRows(&$rows) {
		$edanRecords = $this->entityLoadMultiple('edan_record');
		$settings = [];
		$defaultSetting = $this->edanRecordProcess->getDefault();
		$override = $this->edanSearch->getSettings('fields', 'override_default');
		$fields =  $this->edanSearch->getSettings('fields', 'teaser_fields');
		$label =  $this->edanSearch->getSettings('fields', 'label_replacements');
		$mini_fields = $this->edanSearch->getSettings('fields', 'mini_fields');

		foreach($edanRecords as $id => $record) {
			$settings[$id] = $record->getRecordSettings(TRUE);
			if ($override) {
        $settings[$id]['fields'] = $fields;
        $settings[$id]['label_replacements'] = $label;
			}
      $settings[$id]['mini_fields'] = $mini_fields ?? [];
		}
		foreach($rows as $key => $row) {
			$setting = isset($settings[$row['type']]) ? $settings[$row['type']] : $defaultSetting;
			$this->edanRecordProcess->processData($row, $setting, FALSE);
			$row['rendered_image'] = $row['thumbnail'] ? $this->edanMedia->renderImage($row['thumbnail'], 'medium',['delta' => $key]) : [];
			$rows[$key] = $row;
		}
	  $this->moduleHandler->alter('edan_search_results', $rows, $this->edanSearch);
    $this->themeManager->alter('edan_search_results', $rows, $this->edanSearch);
  }

  public function executeSearch(EdanSearch $edanSearch) {
		$searchResults = [];
		$this->setSearch($edanSearch);
//    $this->pageParameters = $this->edanSearchParams->getParameters();
    if ($edanSearch->getSettings('render_settings', 'show_results_on_load') || !empty($this->pageParameters['fqs']) || !empty($this->pageParameters['q'])) {
      $this->buildParameters();
      $endpoint = $this->edanSearch->getSettings('edan_settings', 'endpoint');
      $results = $this->edanConnector->runParamQuery($this->parameters, $endpoint);
      if ($results['data'] && $results['data']['recordCount'] > 0) {
        $this->processRows($results['data']['rows']);
        $searchResults = $results['data'];
      }
      $searchResults+= [
        'rows' => [],
        'rowCount' => 0,
        'recordCount' => 0
      ];
    }
	  return $searchResults;
  }

  public function getPageParameters() {
	  if (empty($this->pageParameters)) {
      if (!empty($this->edanSearch)) {
        $rows = $this->edanSearch->getSettings('render_settings', 'results_per_page');
        $this->edanSearchParams->setRows($rows);
      }
	    $this->pageParameters = $this->edanSearchParams->getParameters();
	  }
	  return $this->pageParameters;
		//return $this->pageParameters ? $this->pageParameters : $this->edanSearchParams->getParameters();
  }

  public function renderSearch(EdanSearch $edanSearch, $searchResults = []) {
		$renderSettings = $edanSearch->getRenderSettings();
    $render = [
      '#query_string' => $this->pageParameters['q'],
      '#record_count' => $searchResults['recordCount'],
      '#rows' =>  $searchResults['rows'],
			'#executed' => $renderSettings['show_results_on_load'] || !empty($this->pageParameters['fqs']) || !empty($this->pageParameters['q']),
			'#attached' => [
				'library' => [
					'edan_search/edan_search_library'
				]
			],
			'#info' => $edanSearch->get('info'),
      '#facets' => [],
      '#active_facets' => []
		];

		$render['#search_form'] = $renderSettings['display_search_form'] ? \Drupal::service('form_builder')->getForm(\Drupal\edan_search\Form\EdanSearchForm::class, $edanSearch) : NULL;
		if ($searchResults) {
			// add facet and active facets
			if ($edanSearch->getSettings('facets', 'facet_status')) {
				$facet_labels = $edanSearch->getSettings('facets', 'facet_labels');
				$facet_labels = $facet_labels ? $facet_labels : [];
				$facetProcessor = new EdanFacets($this->pageParameters, $facet_labels, $edanSearch->getSettings('facets', 'facets_hide'));
				if (isset($searchResults['facets']) && !empty($searchResults['facets'])) {
					$facets = $facetProcessor->process_facets($searchResults['facets']);
          $this->moduleHandler->alter('edan_search_facets', $facets, $this);
          $this->themeManager->alter('edan_search_facets', $facets, $this);

          $render['#facets'] = [
						'#theme' => 'edan_search_facets',
						'#facets' => $facets,
            '#edan_search_id' => $edanSearch->id(),
						'#attached' => [
							'library' => [
								'edan/plugin'
							]
						],
						'#info' => $edanSearch->get('info')
					];
				}

				$activeFacets = $facetProcessor->get_activeFacets();
        $this->moduleHandler->alter('edan_search_facets_active', $activeFacets, $this);
        $this->themeManager->alter('edan_search_facets_active', $activeFacets, $this);

        $render['#active_facets'] = $activeFacets ? [
          '#theme' => 'edan_search_facets_active',
          '#facets' => $activeFacets,
          '#edan_search_id' => $edanSearch->id(),
          '#message' => $edanSearch->getSettings('facets', 'facet_remove_message') ? t($edanSearch->getSettings('facets', 'facet_remove_message')) : t('Remove facet(s)'),
          '#info' => $edanSearch->get('info')
        ] : NULL;
			}

			if ($renderSettings['pager'] && $searchResults['recordCount'] > $renderSettings['results_per_page']) {
				//pager_default_initialize($searchResults['recordCount'], $renderSettings['results_per_page']);
        \Drupal::service('pager.manager')->createPager($searchResults['recordCount'], $renderSettings['results_per_page']);
				$render['#pager'] = [
					'#type' => 'pager'
				];
			}
		}


	  return $render;
  }



}
