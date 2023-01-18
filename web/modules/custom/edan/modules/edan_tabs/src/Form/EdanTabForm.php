<?php

namespace Drupal\edan_tabs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\edan_tabs\Entity\EdanTab;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a search form for site wide search.
 *
 * Search plugins can define method searchFormAlter() to alter the form. If they
 * have additional or substitute fields, they will need to override the form
 * submit, making sure to redirect with a GET parameter of 'keys' included, to
 * trigger the search being processed by the controller, and adding in any
 * additional query parameters they need to execute search.
 *
 * @internal
 */
class EdanTabForm extends FormBase {
  /**
   * Drupal\edan\SearchParams definition.
   *
   * @var \Drupal\edan\SearchParams
   */
  protected $searchParams;

  protected $edanTab;

  protected $block = FALSE;

  /**
   * Constructs a FeaturesEditForm object.
   *
   * Drupal\edan\SearchParams definition.
   *
   * @var \Drupal\edan\SearchParams
   */
  public function __construct($searchParams) {
    $this->searchParams = $searchParams;

  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('edan.search_params')
    );
  }

  public function setInfo($edanTab, $block = FALSE) {
    $this->edanTab = $edanTab;
    $this->block = $block;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->block ? 'edan_tab_form__'. $this->edanTab->id() .'__block' : 'edan_tab_form__'. $this->edanTab->id();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $display_extra = TRUE) {
    $tabId = $this->edanTab->id();
    $form_state->set('edan_tab_id', $tabId);
    $dq = $this->searchParams->getParameters();
    $reset = FALSE;

    $form = array(
		  '#attributes' => array(
			  'class' => array(
				  'edan-search-form',
          'edan-search-tabs-form',
          $display_extra ? 'display-title' : 'hidden-title'
        ),
			  'autocomplete' => 'off',
		  ),
      '#id' => $this->getFormId()
	  );

	  $url = $this->block ? Url::fromRoute('edan_tabs.view_'. $tabId) : Url::fromRoute('<current>') ;
    $form['url'] = ['#type' => 'value', '#value' => $url];
    $form['search_wrapper'] = array(
      '#prefix' => '<div class="edan-search-form-inner">',
      '#suffix' => '</div>'
    );
    $form['search_wrapper']['edan_q'] = array(
		  '#type' => 'textfield',
		  '#default_value' => $dq['q'] ?? '',
		  '#title' => $this->t('Search'),
		  '#title_display' => $display_extra ? 'before' : 'invisible',
		  '#prefix' => '<div class="edan-search-term">',
		  '#suffix' => '</div>'
	  );
    $reset = strlen($dq['q']) > 0;
    $form['search_wrapper']['edan_fq'] = array(
		  '#prefix' => '<div class="edan-fq-form-wrapper">',
		  '#suffix' => '</div>',
		  '#tree' => TRUE
	  );
	  if ($display_extra) {
      $form['search_wrapper']['edan_fq']['media_usage'] = array(
			  '#type' => 'checkbox',
			  '#title' => t('Open Access Media'),
			  '#weight' => 42,
			  '#default_value' => isset($dq['fqs']) && (in_array('media_usage:"CC0"', $dq['fqs']) ||  in_array('media_usage:CC0', $dq['fqs'])),
			  '#return_value' => 'media_usage:CC0',
		  );
	  }
    $form['fqs'] = ['#type' => 'value', '#value' => $dq['fqs']];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];
    $form['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#access' => $reset
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Redirect to the search page with keywords in the GET parameters.
    // Plugins with additional search parameters will need to provide their
    // own form submit handler to replace this, so they can put their values
    // into the GET as well. If so, make sure to put 'keys' into the GET
    // parameters so that the search results generation is triggered.
//    $query = $this->entity->getPlugin()->buildSearchUrlQuery($form_state);
//    $route = 'search.view_' . $form_state->get('search_page_id');
//    $form_state->setRedirect(
//      $form,
//      [],
//      ['query' => $query]
//    );
    $parents = $form_state->getTriggeringElement()['#array_parents'];
    $button_key = array_pop($parents);
    if ($button_key === 'reset') {
      $url = $form_state->getValue('url');
    }
    else {
      $values = $form_state->getValues();
      $fqs = $values['fqs'] ?? [];
      $args = $new_args = [];
      foreach($fqs as $fq) {
        $args['edan_fq'][$fq] = $fq;
      }
      $keys = ['edan_q', 'edan_fq'];
      $new_args = [];
      foreach($keys as $key) {
        $value = $this->searchParams::getRouteParam($key, NULL, $values);
        if ($value) {
          $new_args[$key] = $value;
        }
      }

//    if (strlen($values['edan_q']) > 0) {
//      $args['edan_q'] =
//    }
      if (isset($new_args['edan_q'])) {
        $args['edan_q'] = $new_args['edan_q'];
      }
      if (isset($new_args['edan_fq'])) {
        $args['edan_fq'] = $args['edan_fq'] ?? [];
        $args['edan_fq'] = array_values(array_merge($args['edan_fq'], $new_args['edan_fq'] ));
      }

      $url = $values['url'];
      if ($args) {
        $url->setOption('query', $args);
      }
    }

    $form_state->setRedirectUrl($url);
//    dump($form_state->getValues());
//    $form_state->setRebuild();

  }

}
