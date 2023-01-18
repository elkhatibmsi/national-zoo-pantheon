<?php
namespace Drupal\edan\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\edan\EdanClient\EdanClient;
use Drupal\edan\SearchParams;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for running EDAN connection test from Admin UI.
 */
class TestConnectionForm extends FormBase {

  protected $edanClient;
  protected $searchParams;

  public function __construct(EdanClient $edanClient, SearchParams $searchParams) {
    $this->edanClient = $edanClient;
    $this->edanClient->setDebug(TRUE);
    $this->searchParams = $searchParams;
  }

  public static function create(ContainerInterface $container) {
    $edanClient = $container->get('edan.client');
    $searchParams = $container->get('edan.search_params');
    return new static($edanClient, $searchParams);
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edan_test_connection';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $searchKeyword = $form_state->getValue('search');
    $responseData = $form_state->get('response_data');  // not a submitted value
    $form['api'] = [
      '#type' => 'details',
      '#title' => $this->t('EDAN API settings'),
      '#open' => FALSE,
    ];

    $form['api']['edan_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' =>$form_state->getValue('edan_url') ?? $this->edanClient->getBaseUri(),
      '#placeholder' => 'https://',
      '#maxlength' => 255,
      '#size' => 60,
    ];

    $form['api']['edan_app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App ID'),
      '#default_value' => $form_state->getValue('edan_app_id'),
      '#maxlength' => 60,
      '#size' => 30,
    ];

    $form['api']['edan_auth_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Authentication token'),
      '#default_value' => $form_state->getValue('edan_auth_token'),
      '#maxlength' => 255,
      '#size' => 60,
    ];


    $form['description'] = [
      '#markup' => '<p>' . t('Enter a search keyword and run test to get results from EDAN.') . '</p>',
    ];
    $endpoints = [
      'metadata/%s/metadata/search.htm',
      'metadata/%s/metadata/getObjectLists.htm',
      'content/%s/content/getContentRows.htm',
      'content/%s/content/getContent.htm',
      'metadata/%s/collections/getFacets.htm',
      'content/%s/admincontent/search.htm',
      'content/%s/admincontent/getContentRows.htm',
      'content/%s/admincontent/getContent.htm',

    ];

    $endpoints = array_combine($endpoints, $endpoints);

    //$link = Link::fromTextAndUrl('documentation', Url::fromUri('https://edandoc.si.edu/apidocs'));
    array_unshift($endpoints, 'N/A');

    $form['endpoint'] = array(
      '#type' => 'select',
      '#title' => t('Endpoint'),
      '#default_value' => $form_state->getValue('endpoint') ?? 0,
      '#description' => $this->t('Visit the <a href="https://edandoc.si.edu/apidocs" target="_blank">documentation</a> to learn more about the endpoints. You must be logged onto the SI network to view the page'),
      '#options' => $endpoints
    );
    $form['endpoint_text'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Alt Endpoint'),
      '#default_value' => $form_state->getValue('endpoint_text') ?? '',
      '#description' => t("Enter endpoint if it's not listed above. Include the version in the endpoint url."),
      '#required' => FALSE,
      '#states' => [
        // The state being affected is "invisible".
        'visible' => [
          // Drupal will only apply this state when the element that satisfies
          // the selector select[name="endpoint"] is0.
          'select[name="endpoint"]' => array('value' => 0),
        ],
      ],
    );
    $form['search'] = [
      '#type' => 'search',
      '#title' => $this->t('Search Keyword'),
      '#description' => '',
      '#default_value' => ($searchKeyword) ? $searchKeyword : '',
      '#maxlength' => 255,
      '#size' => 60,
    ];

    $form['params'] = [
      '#type' => 'textarea',
      '#title' => t('Parameters'),
      '#size' => 50,
      '#default_value' => $form_state->getValue('params') ?? '',
      '#rows' => 4,
      '#description' => t("Enter each parameter on one line. Each parameter should have name=value, such as objectGroupId=dpt-1451339401559-1451343216472-0"),
      '#required' => FALSE,
    ];
    $form['facet'] = array(
      '#type' => 'checkbox',
      '#title' => t('Return Facets'),
      '#default_value' => $form_state->getValue('facet') ?? FALSE,
    );
    $form['run'] = [
      '#type' => 'submit',
      '#value' => t('Run query'),
//      '#submit' => ['::runTest'],
    ];
//    dump($responseData);
    if ($responseData) {
      //$moduleHandler = \Drupal::service('module_handler');
	    dump($responseData);
//      if ($moduleHandler->moduleExists('devel')){
//       dump($responseData);
//      }
//      else {
//        $form['response'] = [
//          '#markup' => ($responseData) ? '<pre>' . print_r($responseData, true) . '</pre>' : '',
//        ];
//      }

    }


    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('endpoint') === "0" && empty($form_state->getValue('endpoint_text'))) {
      $form_state->setErrorByName('endpoint_text', $this->t('You must enter an alternate endpoint or pick one from the list.'));
    }
    if ($form_state->getValue('edan_app_id') && empty($form_state->getValue('edan_auth_token'))) {
      $form_state->setErrorByName('edan_auth_token', $this->t('You must enter an authentication token.'));
    }
    if ($form_state->getValue('edan_auth_token') && empty($form_state->getValue('edan_app_id'))) {
      $form_state->setErrorByName('edan_app_id', $this->t('You must enter an app id.'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //drupal_set_message(t('TestConnectionForm::submit handler'));
    $endpoint = $form_state->getValue('endpoint_text') ? $form_state->getValue('endpoint_text') : $form_state->getValue('endpoint');
    $parameters = [];
    if ($form_state->getValue('params')) {
    	$parameters = $this->convertStringParams($form_state->getValue('params'));
    }
    if ($form_state->getValue('facet')) {
      $this->searchParams->setParam('facet', 'true',$parameters);
     // $parameters['facet'] = 'true';
    }
    if ($form_state->getValue('search')) {
      $parameters['q'] = $form_state->getValue('search');
    }
    if ($form_state->getValue('edan_url')) {
      $this->edanClient->setBaseUri($form_state->getValue('edan_url'));
    }
    if ($form_state->getValue('edan_app_id') && $form_state->getValue('edan_auth_token')) {
      $this->edanClient->setAuthentication($form_state->getValue('edan_app_id'), $form_state->getValue('edan_auth_token'));
    }
    $results = $this->edanClient->fetchEdanResponse($parameters, $endpoint);

    $form_state->set('response_data', $results);
    $form_state->setRebuild();

    $this->messenger()->addMessage('EDAN test submitted');
  }

	protected function convertStringParams($string) {
		$args = [];
		$search_params_array = explode("\r\n", $string);
		foreach ($search_params_array as $p) {
			$param_data = explode('=', $p);
			if (count($param_data) == 2) {
				$k = $param_data[0];
				$v = $param_data[1];
				if ($k == 'q') {
					$args[$k] = $v;
				}
				if ($k == 'fqs') {
					// replace outer brackets and quotes with regex
					$re = '/\[\"(.*?)\"\]/m';
					preg_match($re, $v, $matches);
					if (!empty($matches)) {
						$fqs = $matches[1];
						// split the fqs
						// first we have to encode the escaped quotes:
						$fqs = str_replace('\"', '____', $fqs);
						// $fqs_array = explode('","', $fqs);
						$re = '/\"\s?,\s?\"/m';
						$fqs_array = preg_split($re, $fqs);
						foreach ($fqs_array as $fq) {
							// re-encode escaped quotes
							$args['fqs'][] = str_replace('____', '"', $fq);
						}
					}

					//$fqs = substr($v, 2, strlen($v) - 4);

				}
				else {
					$args[$k][] = $v;
				}
			}
		}
		return $args;
	}
}

