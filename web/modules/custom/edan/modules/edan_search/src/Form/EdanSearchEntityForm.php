<?php

namespace Drupal\edan_search\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Xss;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\path_alias\Entity\PathAlias;
use Drupal\path_alias\PathAliasStorage;


/**
 * Class EdanSearchEntityForm.
 */
class EdanSearchEntityForm extends EntityForm {

	/**
	 * The EDAN Config Repo
	 *
	 * @var \Drupal\edan_search\EdanSearchManager
	 */
	protected $manager;


	/**
	 * The request context.
	 *
	 * @var \Drupal\Core\Routing\RequestContext
	 */
	protected $requestContext;


	/**
	 * The EDAN Form Helper
	 *
	 * @var \Drupal\edan\EdanFormHelper
	 */
	private $formHelper;

  /**
   * The alias cleaner.
   *
   * @var \Drupal\pathauto\AliasCleanerInterface
   */
  protected $aliasCleaner;

  /**
   * The path alias storage helper.
   *
   * @var Drupal\path_alias\Entity\PathAlias
   */
  protected $pathAlias;



	/**
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container) {
		$instance = parent::create($container);
		//$instance->configuration = $container->get('config.factory');
		$instance->requestContext =  $container->get('router.request_context');
//		$instance->edanConnectorService = $container->get('edan.connector');
		$instance->formHelper = $container->get('edan.form_helper');
		$instance->manager = $container->get('edan_search.manager');
		$instance->pathAlias = \Drupal::entityTypeManager()->getStorage('path_alias');

		return $instance;
	}
	private function setFormHelper() {
		$this->formHelper = \Drupal::service('edan.form_helper');
	}
	/**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
		if (!$this->formHelper) $this->setFormHelper();
	  // Change page title for the duplicate operation.
	  if ($this->operation == 'duplicate') {
		  $form['#title'] = $this->t('<em>Duplicate edan search</em>: @label', ['@label' => $this->entity->label()]);
		  $this->entity = $this->entity->createDuplicate();
	  }

	  // Change page title for the edit operation.
	  elseif ($this->operation == 'edit') {
		  $form['#title'] = $this->t('<em>Edit edan search</em>: @label', ['@label' => $this->entity->label()]);
	  }
	  else {
	  	$form['#title'] = $this->t('<em>Add edan search</em>');
	  }
	  $edan_search = $this->entity;
//	  dump($edan_search);

	  $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $edan_search->label(),
      '#description' => $this->t("Label for the Edan search entity."),
      '#required' => TRUE,
	    '#weight' => 0
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $edan_search->id(),
      '#machine_name' => [
        'exists' => '\Drupal\edan_search\Entity\EdanSearch::load',
      ],
	    '#weight' => 0,
	    '#disabled' => !$edan_search->isNew(),
    ];
    if ($edan_search->get('description')) {
      $form['description'] = [
        '#markup' => $this->t($edan_search->get('description'))
      ];
    }
    $form['summary'] = [
      '#type' => 'text_format',
      '#title' => t('Search Description'),
      '#default_value' => $edan_search->get('summary'),
      '#description' => t('Description will always appear on the search block or page. Text uses the edan_html filter.'),
      '#format' => 'edan_html'
    ];
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => t('Active'),
      '#default_value' => $edan_search->status(),
      '#description' => t('Only admins are allowed to view the rendered custom search page.  ')
    ];
	  $form['custom_search'] = [
		  '#type' => 'vertical_tabs',
		  '#default_tab' => 'edit-edan-settings',
	  ];

		$this->_buildEdanSettings($form, $form_state, $edan_search->get('edan_settings'));
	  $this->_buildRenderSettings($form, $form_state, $edan_search->get('render_settings'));
    $this->_buildFieldSettings($form, $form_state, $edan_search->get('fields'));
    $this->_buildFacetSettings($form, $form_state, $edan_search->get('facets'));
//    dump($form);
	  $form['#attached']['library'][] = 'edan/edan_form';
    $form = parent::form($form, $form_state);

    return $form;
  }

	/**
	 * Helper method for buildForm(). Builds search page related fields.
	 * @param array $form
	 */
	private function _buildEdanSettings(array &$form, FormStateInterface $form_state, $configuration) {
		$edan_records = $this->manager->entityLoadMultiple('edan_record');
		$content_types = array_keys($edan_records);
		$settings = $this->manager->configLoad('', 'edan_record.settings');
		$form['edan_settings'] = [
			'#type' => 'details',
			'#title' => $this->t('Search Details'),
			'#group' => 'custom_search',
			'#tree' => TRUE
		];

		$endpoints = $this->getEndpoints();
		if (count($endpoints) > 1) {
      $form['edan_settings']['endpoint'] = [
        '#type' => 'radios',
        '#options' => $this->getEndpoints(),
        '#default_value' => isset($configuration['endpoint']) ? $configuration['endpoint'] : '/metadata/%s/metadata/search.htm',
        '#title' => $this->t('EDAN Endpoint'),
        '#required' => TRUE,
	      '#disabled' => $this->entity->id() == 'ead_collection' || $this->entity->id() == 'ogmt_results'
      ];
    }

		else {
		  $form['edan_settings']['endpoint'] = [
		    '#type' => 'value',
        '#value' => '/metadata/%s/metadata/search.htm'
      ];
    }

		$form['edan_settings']['default_q'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Search Term'),
			'#default_value' => isset($configuration['default_q']) ? $configuration['default_q'] : '',
			'#access' => $this->entity->id() !== 'ead_collection' && $this->entity->id() !== 'ogmt_results'
		];

		$form['edan_settings']['record_types'] = [
			'#type' => 'checkboxes',
			'#title' => $this->t('Allowed Record Types'),
			'#options' => $this->manager->getEdanConnector()->getTypes(),
			'#attributes' => ['class' => ['column-wrapper']],
			'#default_value' => isset($configuration['record_types']) ? $configuration['record_types'] : $content_types,
			'#access' => $this->entity->id() !== 'ead_collection' && $this->entity->id() !== 'ogmt_results'
		];

		$form['edan_settings']['default_to_local_units'] = [
			'#type' => 'checkbox',
			'#title' => $this->t('Default search to local units'),
			'#default_value' => isset($configuration['default_to_local_units']) ? $configuration['default_to_local_units'] : $settings['limit_to_local'],
			'#description' => $this->t('Check the box to default searches to the local units.  You can also then specify the local units.'),
			'#access' => $this->entity->id() !== 'ead_collection' && $this->entity->id() !== 'ogmt_results'
		];

		$form['edan_settings']['local_units'] = [
			'#type' => 'checkboxes',
			'#title' => $this->t('Local Units'),
			'#options' => $this->formHelper->getUnitCodes(),
			'#default_value' => isset($configuration['local_units']) ? $configuration['local_units'] : $settings['unit_codes'],
			'#description' => $this->t('This value will override the default to local settings and show results from across the institution. Set the default unit codes on the <a href="@url">EDAN Search unit code page</a>.', ['@url' => Url::fromRoute('edan_record.settings_form')->toString()]),
			'#attributes' => ['class' => ['column-wrapper']],
			'#access' => $this->entity->id() !== 'ead_collection' && $this->entity->id() !== 'ogmt_results',
			'#states' => [
				'visible' => [
					':input[name="edan_settings[default_to_local_units]"]' => ['checked' => TRUE],
				],
			],
		];
	  $form['edan_settings']['fq_params'] = [
		  '#type' => 'textarea',
		  '#title' => $this->t('EDAN Query Parameters (fq)'),
		  '#description' => $this->t('Enter each parameter on one line (separate with line breaks).'),
		  '#default_value' => isset($configuration['fq_params']) ? $this->formHelper->generateTextAreaValues($configuration['fq_params']) : NULL,
		  '#cols' => 60,
		  '#resizable' => 'vertical',
      '#disabled' => $this->entity->id() == 'ead_collection' || $this->entity->id() == 'ogmt_results'
	  ];

		$form['edan_settings']['sort'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Sort'),
			'#default_value' => isset($configuration['sort']) ? $configuration['sort'] : NULL,
			'#required' => FALSE,
			'#description' => 'Only certain fields can be sorted. Multiple sorts should be comma separated.',
		];

		$form['edan_settings']['bq'] = [
			'#type' => 'textarea',
			'#title' => $this->t('Boost Query(bq)'),
			'#default_value' => isset($configuration['bq']) ? $this->formHelper->generateTextAreaValues($configuration['bq']) : '',
			'#required' => FALSE,
			'#description' => 'BQ parameter specifies an additional, optional, query clause that will be added to the user\'s main query to influence the score.  You can boost certain things using this field. Typical content weighting is around 1.4. '
				. '<br />To boost objectgroups to the top of search results for example, you could enter: type:objectgroup^4'
				. '<br />To boost events above others, and objectgroups above events, you could do this: type:objectgroup^6,type:event^4',
      '#access' => $this->entity->id() !== 'ogmt_results'
		];

		$form['edan_settings']['bf'] = [
			'#type' => 'textarea',
			'#title' => $this->t('Boost Function (bf)'),
			'#default_value' => isset($configuration['bf']) ? $this->formHelper->generateTextAreaValues($configuration['bf']) : '',
			'#required' => FALSE,
			'#description' => 'parameter specifies functions (with optional boosts) that will be used to construct FunctionQueries which will be added to the user\'s main query as optional clauses that will influence the score '
		];

		$form['edan_settings']['default_to_online_media'] = [
			'#type' => 'checkbox',
			'#title' => $this->t('Only return results containing online media'),
			'#default_value' => isset($configuration['default_to_online_media']) ? $configuration['default_to_online_media'] : FALSE,
			'#description' => $this->t('Check the box to default searches to results that contain online media (images or other).'),
			'#required' => FALSE,
			'#access' => $this->entity->id() !== 'ead_collection' || $this->entity->id() !== 'ogmt_results'
		];

    $form['edan_settings']['other_params'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Other Parameters'),
      '#default_value' => isset($configuration['other_params']) ? $this->formHelper->generateTextAreaValues($configuration['other_params'], TRUE) : '',
      '#required' => FALSE,
      '#description' => 'Define other parameters that are addressed by the fields above. Enter one value per line, in the format parameter|value.'
    ];
	}

	private function _buildRenderSettings(array &$form, FormStateInterface $form_state, $configuration) {
		$form['render_settings'] = [
			'#type' => 'details',
			'#title' => 'Render Settings',
			'#group' => 'custom_search',
			'#tree' => TRUE
		];

		$form['render_settings']['show_results_on_load'] = [
			'#type' => 'checkbox',
			'#title' => $this->t('Display results'),
			'#default_value' => isset($configuration['show_results_on_load']) ? $configuration['show_results_on_load'] : TRUE
		];

		$form['render_settings']['display_mode'] = [
			'#type' => 'radios',
			'#title' => $this->t('Display Mode'),
			'#options' => [
				'grid' => 'Grid',
				'list' => 'List',
        'masonry' => 'Masonry Grid'
			],
			'#default_value' => isset($configuration['display_mode']) ? $configuration['display_mode'] : 'grid',
			'#description' => $this->t('How should search results should be displayed'),
		];

		$form['render_settings']['results_per_page'] = [
			'#type' => 'number',
			'#title' => $this->t('Results Per Page'),
			'#default_value' => isset($configuration['results_per_page']) ? $configuration['results_per_page'] : 12,
			'#description' => $this->t('Enter a number between 1 and 100.'),
			'#required' => TRUE,
			'#min' => 1,
			'#max' => 100
		];
		$form['render_settings']['results_count_message'] = [
			'#type' => 'checkbox',
			'#title' => $this->t('Display Count Message'),
			'#default_value' => isset($configuration['results_count_message']) ? $configuration['results_count_message'] : FALSE
		];
		$form['render_settings']['results_empty_message'] = [
			'#type' => 'textfield',
			'#title' => $this->t('"No Results" Message'),
			'#default_value' => isset($configuration['results_empty_message']) ? $configuration['results_empty_message'] : 'Your search found no results.',
			'#description' => $this->t('The message that\'s show when a search returns no results.'),
			'#required' => TRUE,
		];

    $form['render_settings']['pager'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display Pager'),
      '#default_value' => isset($configuration['pager']) ? $configuration['pager'] : TRUE
    ];

		$form['render_settings']['display_search_form'] = [
			'#type' => 'checkbox',
			'#title' => $this->t('Display search form'),
			'#default_value' => isset($configuration['display_search_form']) ? $configuration['display_search_form'] : TRUE
		];

		$form['render_settings']['search_form_open_access'] = [
			'#type' => 'checkbox',
			'#title' => $this->t('Display Open Access checkbox'),
			'#default_value' => isset($configuration['search_form_open_access']) ? $configuration['search_form_open_access'] : TRUE,
			'#states' => [
				'visible' => [
					':input[name="render_settings[display_search_form]"]' => ['checked' => TRUE],
				],
			],
		];
		$form['render_settings']['set_online_media'] = [
			'#type' => 'checkbox',
			'#title' => $this->t('Allow users to set online media checkbox.'),
			'#default_value' => isset($configuration['set_online_media']) ? $configuration['set_online_media'] : FALSE,
			'#description' => $this->t('Check the box if you want users to see the checkbox to limit searches to results that contain online media .'),
			'#required' => FALSE,
			'#states' => [
				'visible' => [
					':input[name="render_settings[display_search_form]"]' => ['checked' => TRUE],
				],
			],
		];

		$form['render_settings']['online_media_checkbox_text'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Online Media Checkbox Text'),
			'#default_value' => isset($configuration['online_media_checkbox_text']) ? $configuration['online_media_checkbox_text'] : FALSE,
			'#description' => $this->t('The text to display next to the contains online media checkbox, on the front-end. Defaults to: "Only return results with online media"'),
			'#required' => FALSE,
			'#states' => [
				'visible' => [
					':input[name="render_settings[set_online_media]"]' => ['checked' => TRUE],
				],
			],
		];

		$form['render_settings']['toggle_tabs'] = [
			'#type' => 'checkbox',
			'#title' => $this->t('Results Tabs'),
			'#default_value' => isset($configuration['toggle_tabs']) ? $configuration['toggle_tabs'] : NULL,
			'#description' => $this->t('Do you want to show search tabs on your results page allowing visitors to toggle between tabs- your unit, SI-wide results and/or collections results?'),
			'#states' => [
				'visible' => [
					':input[name="edan_settings[default_to_local_units]"]' => ['checked' => TRUE],
				],
			],
			'#access' => $this->entity->id() !== 'ead_collection' && $this->entity->id() !== 'ogmt_results'
		];

		$form['render_settings']['tab_order'] = [
			'#type' => 'checkbox',
			'#title' => $this->t('Show Museum Collections tab before All Smithsonian Collections tab'),
			'#default_value' => isset($configuration['tab_order']) ? $configuration['tab_order'] : NULL,
			'#description' => $this->t('If you show search tabs on the results page, by default All Smithsonian Collections results will be shown in the first tab and Museum Collections in the second tab. Check this box to reverse the order.'),
			'#access' => $this->entity->id() !== 'ead_collection' && $this->entity->id() !== 'ogmt_results',
			'#states' => [
				'visible' => [
					':input[name="render_settings[toggle_tabs]"]' => ['checked' => TRUE],
				],
			],
		];

		$form['render_settings']['local_tab'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Title for local tab'),
			'#default_value' => isset($configuration['local_tab']) ? $configuration['local_tab'] : '',
			'#description' => $this->t('The tab title for the local unit tab'),
			'#access' => $this->entity->id() !== 'ead_collection' && $this->entity->id() !== 'ogmt_results',
			'#states' => [
				'visible' => [
					':input[name="render_settings[toggle_tabs]"]' => ['checked' => TRUE],
				],
			],
		];

		$form['render_settings']['create_page'] = [
			'#type' => 'checkbox',
			'#title' => $this->t('Create page for search'),
			'#default_value' => isset($configuration['path']) && !empty($configuration['path']) ? TRUE : FALSE,
			'#access' => $this->entity->id() !== 'ead_collection' && $this->entity->id() !== 'ogmt_results'
		];

		$form['render_settings']['results_page'] = [
		  '#type' => 'container',
			'#access' => $this->entity->id() !== 'ead_collection' && $this->entity->id() !== 'ogmt_results',
      '#states' => [
        'visible' => [
          ':input[name="render_settings[create_page]"]' => ['checked' => TRUE],
        ],
      ],
    ];

		$form['render_settings']['results_page']['path'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Search page path'),
			'#default_value' => $configuration['path'] ?? '',
			'#size' => 65,
			'#maxlength' => 1280,
			'#field_prefix' => $this->requestContext->getCompleteBaseUrl(),

		];

    $form['render_settings']['results_page']['pid'] = [
      '#type' => 'value',
      '#value' => isset($configuration['pid']) ? $configuration['pid'] : NULL
    ];
//		$form['render_settings']['json_page'] = [
//			'#type' => 'checkbox',
//			'#title' => $this->t('Return the page as JSON'),
//			'#default_value' => isset($configuration['json_page']) ? $configuration['json_page'] : FALSE,
//			'#states' => [
//				'visible' => [
//					':input[name="render_settings[create_page]"]' => ['checked' => TRUE],
//				],
//			],
//		];
	}

	private function _buildFieldSettings(array &$form, FormStateInterface $form_state, $configuration) {
		$form['fields'] = [
			'#type' => 'details',
			'#title' => 'Fields and Labels',
			'#group' => 'custom_search',
			'#tree' => TRUE,
			'#access' => $this->entity->id() !== 'ead_collection'
		];
    $form['fields']['override_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override Teaser Settings'),
      '#default_value' => isset($configuration['override_default']) ? $configuration['override_default'] : false,
      '#description' => $this->t('Override the default record settings. Visit the <a href=":url" target="_blank">EDAN Records</a> page to edit the view the settings.', [':url' => Url::fromRoute('entity.edan_record.collection')->toString()])

    ];
    $form['fields']['teaser_fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Result Fields'),
      '#default_value' => isset($configuration['teaser_fields']) ? $this->formHelper->generateTextAreaValues($configuration['teaser_fields']) : '',
      '#description' => $this->t('Search results will display title and image.  Replace "." with ":", i.e. freetext.setName should be freetext:setName. List any fields that you want to display for the search results. Replace "." with ":", i.e. freetext.setName should be freetext:setName. Use * to show all fields.'),
      '#states' => [
        'visible' => [
          ':input[name="fields[override_default]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['fields']['label_replacements'] = [
			'#type' => 'textarea',
			'#title' => $this->t('Label Replacement'),
			'#default_value' => isset($configuration['label_replacements']) ? $this->formHelper->generateTextAreaValues($configuration['label_replacements'], TRUE) : '',
			'#description' => $this->t('Replace the labels shown with a different label. Replace "." with ":", i.e. freetext.setName should be freetext:setName. When making this list, do not list the "facet" name, but the "label." For example the metadata facet, physicalDescription, has a label "Physical Description" -- For this to appear on the object listing as "Phys. Descr." you enter the following line (without quotes) "Physical Description | Phys. Descr." -- notice the pipe "|" character between the label and desired replacement. Replacements are not case sensitive.'),
      '#states' => [
        'visible' => [
          ':input[name="fields[override_default]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['fields']['mini_fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Mini Fields'),
      '#default_value' => isset($configuration['mini_fields']) ? $this->formHelper->generateTextAreaValues($configuration['mini_fields']) : '',
      '#description' => $this->t('Fields listed here will be marked with the mini class. By default this will cause non-mini fields to be hidden and add a "expand" button to each record to show non-mini fields. Each field should be on its own line and a subset of the search fields. Replace "." with ":", i.e. freetext.setName should be freetext:setName. Leave blank for to not have mini fields.'),
    ];

	}

	/**
	 * Helper method for buildForm(). Builds facet related fields.
	 * @param array $form
	 */
  private function _buildFacetSettings(array &$form, FormStateInterface $form_state, $configuration) {
		$form['facets'] = [
			'#type' => 'details',
			'#title' => $this->t('Facets'),
      '#group' => 'custom_search',
      '#tree' => TRUE
		];

    $form['facets']['facet_status'] = [
    '#type' => 'checkbox',
      '#title' => $this->t('Include facets'),
      '#default_value' => isset($configuration['facet_status']) ? $configuration['facet_status'] : TRUE,
      '#description' => $this->t('Check this box to include facets on the search results.')

    ];

    $form['facets']['facet_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of facets returned'),
      '#default_value' => isset($configuration['facet_limit']) ? $configuration['facet_limit'] : NULL,
      '#description' => $this->t('Enter a number between 1 and 100 for the number of facet values returned.'),
      '#min' => 1,
      '#max' => 100,
      '#states' => [
        'visible' => [
          ':input[name="facets[facet_status]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $facet_header_text = isset($configuration['facet_header_text']) && strlen(trim($configuration['facet_header_text'])) > 0 ? $configuration['facet_header_text'] : 'Facets';
    $form['facets']['facet_header_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facets Title'),
      '#default_value' => $this->t($facet_header_text),
      '#description' => $this->t('Header text displayed in facets block.'),
      '#states' => [
        'visible' => [
          ':input[name="facets[facet_status]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $facet_remove_message = isset($configuration['facet_remove_message']) && strlen(trim($configuration['facet_remove_message'])) > 0 ? $configuration['facet_remove_message'] : 'Clear facet(s):';
    $form['facets']['facet_remove_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message to place above the active facets'),
      '#default_value' => $this->t($facet_remove_message),
      '#description' => $this->t('You can modify the message that gets displayed above the list of currently selected facets.'),
      '#states' => [
        'visible' => [
          ':input[name="facets[facet_status]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['facets']['facet_fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Facets Fields'),
      '#description' => $this->t('Defaults facets will be returned unless facets fields are set.  Enter one facet per line. Default facets are <br>
        culture, data_source, date, object_type, online_meida_type, online_visual_material, place, set_name, topic, type, metadata_usage, media_usage'),
      '#default_value' => isset($configuration['facet_fields']) ? $this->formHelper->generateTextAreaValues($configuration['facet_fields']) : '',
      '#cols' => 60,
      '#resizable' => 'vertical',
      '#states' => [
        'visible' => [
          ':input[name="facets[facet_status]"]' => ['checked' => TRUE],
        ],
      ],
	    '#access' => $this->entity->id() !== 'ogmt_results'
    ];
    $form['facets']['facet_labels'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Facet Labels'),
      '#default_value' => isset($configuration['facet_labels']) ? $this->formHelper->generateTextAreaValues($configuration['facet_labels'], TRUE) : '',
      '#description' => $this->t('Use this box to change the order of facets and replace facet names with different names.
      Use the facet name and the new name/label for the facet, separated by a pipe character. Enter one facet per line. Replace "." with ":", i.e. freetext.setName should be freetext:setName.
      For example to rename the facet name data_source enter "data_source | Data Source" without the quotes.
      Notice the pipe "|" character between the name and desired replacement. Replacements are case sensitive.
      By default, any facets not listed here will be shown at the end of the list. You can explicitly remove facets using the "Facets to Hide" box below.'),
      '#states' => [
        'visible' => [
          ':input[name="facets[facet_status]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['facets']['facets_hide'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Hidden Facets Fields'),
      '#description' => $this->t('Use this box to indicate any facets which should be hidden. Replace "." with ":", i.e. freetext.setName should be freetext:setName. ZEnter one facet per line, and enter only the facet name such as "data_source" without the quotes.'),
      '#default_value' => isset($configuration['facets_hide']) ? $this->formHelper->generateTextAreaValues($configuration['facets_hide']) : '',
      '#cols' => 60,
      '#resizable' => 'vertical',
      '#states' => [
        'visible' => [
          ':input[name="facets[facet_status]"]' => ['checked' => TRUE],
        ],
      ],
    ];

	}

	/**
	 * {@inheritdoc}
	 */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if ($values['render_settings']['create_page']) {
      if (empty($values['render_settings']['results_page']['path'])) {
        $form_state->setErrorByName(
          'render_settings][results_page][path',
          $this->t("You must enter a path for the search result page.")
        );

      }
      else {
        // Validate record page path.
        $path = trim($values['render_settings']['results_page']['path']);
        if ($path && $path[0] !== '/') {
          $form_state->setErrorByName(
            'render_settings][results_page][path',
            $this->t(
              "The path '%path' has to start with a slash.",
              ['%path' => $path]
            )
          );
        }
        $alias = $this->pathAlias->loadByProperties(['alias' => $path]);
        if ($alias) {
          if ($values['render_settings']['results_page']['pid'] && !isset($alias[$values['render_settings']['results_page']['pid']])) {
            $form_state->setErrorByName(
              'render_settings][results_page][path',
              $this->t(
                "The path '%path' is already used by another search.  Please use a unique path",
                ['%path' => $path]
              )
            );
          }
          elseif (empty($values['render_settings']['results_page']['pid'])) {
            $form_state->setErrorByName(
              'render_settings][results_page][path',
              $this->t(
                "The path3 '%path' is already used by another search.  Please use a unique path",
                ['%path' => $path]
              )
            );
          }
        }
      }
    }
    if ($values['render_settings']['display_mode'] === 'masonry' && $this->manager->getModuleHandler()->moduleExists('masonry') == FALSE) {
      $form_state->setErrorByName(
        'render_settings][display_mode',
        $this->t(
          'You must enable the <a href=":url">masonry module</a> to use this display mode.',
          [':url' => 'https://www.drupal.org/project/masonry'])
      );
    }
    $record_types = array_filter($values['edan_settings']['local_units']);
    if ($values['edan_settings']['default_to_local_units'] && empty($record_types)) {
      $form_state->setErrorByName(
        'edan_settings][local_units',
        $this->t("You must select unit codes.")
      );
    }
    parent::validateForm($form, $form_state);
  }


	public function submitForm(array &$form, FormStateInterface $form_state) {
	  parent::submitForm($form, $form_state);
		$formHelper = \Drupal::service('edan.form_helper');
    $search = $this->entity;
	  $setting_keys = ['edan_settings', 'render_settings', 'fields', 'facets'];
	  $values = $form_state->getValues();
	  $set_array_keys = [
			'teaser_fields',
		  'mini_fields',
			'facet_labels',

	  ];
	  $pid = NULL;
	  foreach($setting_keys as $key) {
      $settings = [];
      foreach($values[$key] as $id => $value) {
        if ($id === 'results_page') {
          if ($values['render_settings']['create_page']) {
            $source = '/edan/search/'. $values['id'];
            $path_alias = $this->pathAlias->create([
              'path' => $source,
              'alias' => $value['path'],
              'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
            ]);
            $path_alias->save();
            $settings['path'] = $value['path'];
            $settings['pid'] = $path_alias->id();
          }
          elseif ($value['pid']) {
            $path_alias = $this->pathAlias->load($value['pid']);
            $this->pathAlias->delete([$path_alias]);
            $settings['path'] = '';
            $settings['pid'] = NULL;
          }
          // $path
        }
        elseif (is_array($value)) {
          $settings[$id] = array_filter($value);
        }
        elseif ($form[$key][$id]['#type'] === 'textarea') {
          $settings[$id] = in_array($id, ['other_params','label_replacements','facet_labels']) ? $formHelper->extractTextAreaValues($value, TRUE) : (in_array($id, $set_array_keys) ? $formHelper->extractTextAreaValues($value, FALSE, TRUE) : $formHelper->extractTextAreaValues($value, FALSE, FALSE));
        }
        elseif ($id != 'create_page') {
          $settings[$id] = $value;
        }
      }
      if ($settings) {
        $this->entity->set($key, $settings);
      }
    }
	  $search->set('summary', trim($values['summary']['value']));
//    $search->set('label', trim($search->label()));
    // $edan_search->set('description', trim($edan_search->get('description')));

//    $status = $search->save();
//
//    switch ($status) {
//      case SAVED_NEW:
//        $this->messenger()->addMessage($this->t('Created the %label Edan search entity.', [
//          '%label' => $search->label(),
//        ]));
//        break;
//
//      default:
//        $this->messenger()->addMessage($this->t('Saved the %label Edan search entity.', [
//          '%label' => $search->label(),
//        ]));
//    }
//    $form_state->setRedirectUrl($this->entity->toUrl('collection'));

	}
	/**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $edan_search = $this->entity;
    // Prevent leading and trailing spaces in names.
    $edan_search->set('label', trim($edan_search->label()));
   // $edan_search->set('description', trim($edan_search->get('description')));

    $status = $edan_search->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Edan search entity.', [
          '%label' => $edan_search->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Edan search entity.', [
          '%label' => $edan_search->label(),
        ]));
    }
   // $form_state->setRebuild();

     $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
//  protected function actions(array $form, FormStateInterface $form_state) {
//    $element = parent::actions($form, $form_state);
//    $search = $this->entity;
//
//    $element['preview'] = [
//      '#type' => 'submit',
//      '#value' => t('Preview'),
//      '#weight' => 20,
//      '#submit' => ['::submitForm', '::preview'],
//    ];
//
//
//    return $element;
//  }



  /**
   * Form submission handler for the 'preview' action.
   *
   * @param $form
   *   An associative array containing the structure of the form.
   * @param $form_state
   *   The current state of the form.
   */
//  public function preview(array $form, FormStateInterface $form_state) {
//
//  }

  protected function getEndpoints() {
  	$endpoints = [
		  '/metadata/%s/metadata/search.htm' => 'metadata/search.htm',
		  '/metadata/%s/metadata/getObjectLists.htm' => 'metadata/getObjectLists.htm',
	  ];

	  $this->manager->getModuleHandler()->alter('edan_endpoints', $endpoints);

	  return $endpoints;

  }



}
