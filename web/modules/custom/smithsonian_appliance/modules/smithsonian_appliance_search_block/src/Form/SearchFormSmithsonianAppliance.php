<?php

namespace Drupal\smithsonian_appliance_search_block\Form;

use Drupal\smithsonian_appliance\Routing\SearchViewRoute;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the search form.
 *
 * @package Drupal\smithsonian_appliance_search_block\Form
 */
class SearchFormSmithsonianAppliance extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smithsonian_appliance_search_header';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $query = '') {
    $prompt = $this->t('Search Smithsian Appliance');

    // Basic search.
    $form['basic'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['search-box__form-wrapper']
      ],
    ];

    $form['basic']['search_keys'] = [
      '#type' => 'textfield',
      '#default_value' => $query,
      '#theme_wrappers' => [],
      '#attributes' => [
        'title' => $prompt,
        'placeholder' => $prompt,
        'maxlength' => 128,
        'class' => ['search-box__text-input']
      ],
      '#title' => $prompt,
      '#title_display' => 'invisible',
    ];

    // @todo: sort.

    $form['basic']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Search'),
      '#attributes' => [
        'class' => ['search-box__button']
      ]
    ];

    // Use core search CSS in addition to this module's css
    // (keep it general in case core search is enabled).
    $form['#attributes']['class'][] = 'search-form';
    $form['#attributes']['class'][] = 'search-smithsonian-appliance-search-form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $searchQuery = urlencode($form_state->getValue('search_keys'));

    $form_state->setRedirect(SearchViewRoute::ROUTE_NAME, [
      'search_query' => $searchQuery,
    ]);
  }

}
