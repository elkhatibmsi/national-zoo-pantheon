<?php

namespace Drupal\smithsonian_appliance\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\smithsonian_appliance\SearchResults\SearchQuery;
use Drupal\smithsonian_appliance\Service\SearchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\smithsonian_appliance\Form\SearchForm;
use Drupal\smithsonian_appliance\SearchResults\OneBox;

/**
 * Class SearchView.
 *
 * @package Drupal\smithsonian_appliance\Controller
 */
class SearchViewController extends ControllerBase {

  /**
   * Search service.
   *
   * @var \Drupal\smithsonian_appliance\Service\SearchInterface
   */
  protected $search;

  /**
   * Constructs a new SearchViewController object.
   *
   * @param \Drupal\smithsonian_appliance\Service\SearchInterface $search
   *   Search service.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   Form builder.
   */
  public function __construct(SearchInterface $search, FormBuilderInterface $formBuilder) {
    $this->formBuilder = $formBuilder;
    $this->search = $search;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('smithsonian_appliance.search'),
      $container->get('form_builder')
    );
  }

  /**
   * Builds search page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current request.
   * @param string $search_query
   *   Search query.
   * @param null|string $result_sort
   *   Sort order.
   *
   * @return array
   *   Render array
   */
  public function get(Request $request, $search_query = '', $result_sort = NULL) {
    $search_query = urldecode($search_query);
    

    $form = $this->formBuilder->getForm(SearchForm::class, $search_query);

    if ($search_query !== '' && !$request->request->has('form_id')) {
      // @todo Language filter.
      $response = $this->search->search(new SearchQuery($search_query, $result_sort === 'date' ? SearchQuery::ORDER_DATE : NULL, $request->query->get('page')));

      return [
        '#theme' => 'smithsonian_appliance_search_results',
        '#results' => $response,
        '#form' => $form,
        '#cache' => [
          'tags' => ['config:smithsonian_appliance.settings'],
        ],
      ];
    }

    return $form;
  }

}
