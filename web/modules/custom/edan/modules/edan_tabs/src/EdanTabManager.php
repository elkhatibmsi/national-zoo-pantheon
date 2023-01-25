<?php

namespace Drupal\edan_tabs;

use Drupal\edan\EdanManagerBase;
use Drupal\edan_search\EdanSearchManagerInterface;
use Drupal\edan_tabs\Entity\EdanTab;
use Drupal\Core\Url;
use Drupal\Core\Link;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EdanTabsManager.
 */
class EdanTabManager extends EdanManagerBase implements EdanTabManagerInterface {
  /**
   * @var \Drupal\edan_search\EdanSearchManagerInterface
   */
  protected $edanSearchManager;

  /**
   * @var array of edan_q and edan_fq
   */
  protected $pageParams = [];

  /**
   * @var array of search result totals
   */
  protected $searchTotals = [];

  protected $total = 0;

  /**
   * Sets edanSearchManager service.
   */
  public function setEdanSearchManager(EdanSearchManagerInterface $edanSearchManager) {
    $this->edanSearchManager = $edanSearchManager;
  }

  /**
   * @param \Drupal\edan_tabs\Entity\EdanTab $edanTab
   * @param \Drupal\edan_search\Entity\EdanSearch $edan_search
   * @return array of tabs
   */
  public function renderTabs(EdanTab $edanTab, $edan_search = NULL) {
    $route_id = 'edan_tabs.view_'. $edanTab->id();
    $searches = $edanTab->getSearches();

    $options = ['query' => $this->pageParams];
    $options += $edan_search ? ['attributes' => ['class' => ['btn']]] : ['attributes' => ['class' => ['is-active', 'btn']]];
    if ($edanTab->hasLanding()) {
      $first = ['search' => 'landing', 'label' => $edanTab->get('landing_label')];
      $label = $first['label'];
    }
    else {
      $first = array_shift($searches);
      $label = $first['label'] .' <span>('. number_format($this->searchTotals[$first['search']]) .')</span>';
    }
    $tabs[$first['search']] = ['#type' => 'link', '#title' => ['#markup' => $label], '#url' => Url::fromRoute($route_id , [], $options)];

    foreach($searches as $delta => $search) {
      $options = ['query' => $this->pageParams];
      $options += $edan_search  && $search['search'] === $edan_search->id() ? ['attributes' => ['class' => ['is-active', 'btn']]] : ['attributes' => ['class' => ['btn']]];
      $tabs[$search['search']] = ['#type' => 'link', '#title' => ['#markup' => $search['label'] .' <span>('. number_format($this->searchTotals[$search['search']]) .')</span>'], '#url' => Url::fromRoute($route_id .'_tab', ['edan_search' => $search['search']], $options )];
    }
    return $tabs;
  }

  public function renderPage(EdanTab $edanTab, $edanSearch = NULL) {
    $content = '';
    $searches = [];
    foreach($edanTab->getSearches() as $delta => $search) {
      if (!$edanSearch || $edanSearch->id() != $search['search']) {
        $searchEntity = $this->entityLoad($search['search'], 'edan_search');
        $searchId = $searchEntity->id();

        $render_settings = $searchEntity->getRenderSettings();
        $render_settings['display_search_form'] = FALSE;
        $render_settings['toggle_tabs'] = FALSE;
        $render_settings['results_count_message'] = FALSE;
        $render_settings['results_per_page'] = $search['resultCount'] ?? $render_settings['results_per_page'];
        if (isset($search['display_mode'])) {
          $render_settings['display_mode'] = $search['display_mode'];
        }
        $render_settings['pager'] = FALSE;

        $facet_settings = $searchEntity->getFacetSettings();
        $facet_settings['facet_status'] = FALSE;
        $searchEntity->setSettings('render_settings', $render_settings);
        $searchEntity->setSettings('facets', $facet_settings);
        $searchEntity->set('summary', '');
        $searchEntity->set('label', '');
        $searches[$searchId] = $searchEntity;
      }
    }
    // page query parameters
    foreach ($this->edanSearchManager->getPageParameters() as $id => $param) {
      if ($id == 'q' && $param != '') {
        $this->pageParams['edan_q'] = $param;
      }
      if ($id == 'fqs' && $param) {
        $this->pageParams['edan_fq'] = $param;
      }
    }
    if ($edanSearch) {
      // unset the edanSearch so we don't make a duplicate call to edan
      if (isset($searches[$edanSearch->id()])) {
        unset($searches[$edanSearch->id()]);
      }
      $edanSearch->set('label', '');

      $this->getResults($searches);

      $render_settings = $edanSearch->getRenderSettings();
      $render_settings['display_search_form'] = FALSE;
      $render_settings['toggle_tabs'] = FALSE;
      $render_settings['results_count_message'] = FALSE;
      $render_settings['pager'] = TRUE;
      $edanSearch->setSettings('render_settings', $render_settings);

      $view_builder = $this->entityTypeManager->getViewBuilder('edan_search');
      $content = $view_builder->view($edanSearch);
      $this->searchTotals[$edanSearch->id()] = $content['#record_count'];
      $this->total += $content['#record_count'];
    }
    elseif($edanTab->hasLanding()) {
      $content = $this->renderLanding($edanTab, $searches);
    }
    else {
      $defaultSearch = $edanTab->getFirstSearch();
      // unset the default/first search so we don't make a duplicate call to edan
      if (isset($searches[$defaultSearch['search']])) {
        unset($searches[$defaultSearch['search']]);
      }
      $defaultSearch = $this->entityLoad($defaultSearch['search'], 'edan_search');
      $render_settings['display_search_form'] = FALSE;
      $render_settings['toggle_tabs'] = FALSE;
      $render_settings['pager'] = TRUE;
      $render_settings['results_count_message'] = FALSE;
      $defaultSearch->setSettings('render_settings', $render_settings);

      $this->getResults($searches);

      array_unshift($searches, $defaultSearch);
      $view_builder = $this->entityTypeManager->getViewBuilder('edan_search');
      $content = $view_builder->view($defaultSearch);
      $this->searchTotals[$defaultSearch->id()] = $content['#record_count'];
      $this->total += $content['#record_count'];
    }
    $form_obj = \Drupal::service('class_resolver')->getInstanceFromDefinition('Drupal\edan_tabs\Form\EdanTabForm');
    $form_obj->setInfo($edanTab);
    $build = [
      '#theme' => 'edan_tabs_page',
      '#tabs' =>  $this->renderTabs($edanTab, $edanSearch),
      '#attached' => [
        'library' => [
          'edan_tabs/base'
        ]
      ],
      '#search_form' =>  \Drupal::formBuilder()->getForm($form_obj),
      '#searchTerm' => $this->pageParams['edan_q'] ?? NULL,
      '#total' => $this->total,
      '#content' => $content,
      '#edanTab' => $edanTab
    ];

    $this->moduleHandler->alter('edan_tabs_tabs', $build, $edanTab);
    return $build;
  }

  private function renderLanding($edanTab, $searches) {
    $columns = $results = [];
    $view_builder = $this->entityTypeManager->getViewBuilder('edan_search');
    $route_id = 'edan_tabs.view_'. $edanTab->id();
    $options = ['query' => $this->pageParams, 'attributes' => ['class' => ['read-more']]];

    foreach($edanTab->getSearches() as $search) {
      $edanSearch = $searches[$search['search']];
      $render = $view_builder->view($edanSearch);
      $this->searchTotals[$search['search']] = $render['#record_count'];
      $this->total += $render['#record_count'];
      if ($render['#record_count'] > 0) {
        $columns[$search['column']][$search['search']] = [
          'title' => $search['label'],
          'content' => $render,
          'link' => $render['#record_count'] > $search['resultCount'] ? Link::fromTextAndUrl('View More '. $search['label'] , Url::fromRoute($route_id .'_tab', ['edan_search' => $search['search']], $options )) : NULL
        ];
      }
    }
    return [
      '#theme' => 'edan_tabs_landing',
      '#edanTab' => $edanTab,
      '#columns' => $columns
    ];
  }

  private function getResults($searches) {
    foreach($searches as $searchId => $search) {
      $result = $this->edanSearchManager->executeSearch($search);
      $this->searchTotals[$searchId] = $result['rowCount'];
      $this->total += $result['rowCount'];
    }
  }

}
