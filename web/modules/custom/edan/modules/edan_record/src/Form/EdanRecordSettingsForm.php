<?php

namespace Drupal\edan_record\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Url;

/**
 * Configure EDAN API settings for this site.
 */
class EdanRecordSettingsForm extends ConfigFormBase {
  protected $edanFormHelper;
  protected $moduleHandler;

	/**
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container) {
		$instance = parent::create($container);
    $instance->edanFormHelper = $container->get('edan.form_helper');
    $instance->moduleHandler = $container->get('module_handler');
		return $instance;
	}
	/**
	 * {@inheritdoc}
	 */
	public function getFormId() {
		return 'edan_record_settings';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getEditableConfigNames() {
		return ['edan_record.settings'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildForm(array $form, FormStateInterface $form_state, array $args = NULL) {
		$config = $this->config('edan_record.settings');
		$viewer = $config->get('viewer');
//    drupal_set_installed_schema_version('edan_record', '8104');
//    dump(drupal_get_installed_schema_version('edan_record'));
    $edanConnect = \Drupal::service('edan.connector');
    $contentTypes = $edanConnect->getTypes();
    $form['record'] = [
			'#type' => 'vertical_tabs',
			'#default_tab' => 'edit-access',
		];
    $form['access'] = [
      '#type' => 'details',
      '#title' => $this->t('Access Settings'),
      '#group' => 'record',
      '#tree' => TRUE,
      '#description' => $this->t('Record page and search.'),
    ];

    $form['access']['limit_to_local'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Limit records to local units'),
      '#default_value' => $config->get('limit_to_local'),
    ];
    $form['access']['unit_codes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Default Unit Codes'),
      '#description' => $this->t('Select the default unit codes.  This value can be overridden by the search form.'),
      '#options' => $this->edanFormHelper->getUnitCodes(),
      '#default_value' => $config->get('unit_codes'),
      '#attributes' => ['class' => ['column-wrapper']]
    ];
		$form['media'] = [
			'#type' => 'details',
			'#title' => $this->t('Image Settings'),
			'#group' => 'record',
			'#tree' => TRUE,
			'#description' => $this->t('Record page and search.'),
		];
		$form['media']['thumbnail'] = array(
			'#type' => 'number',
			'#title' => t('Thumbnail'),
			'#default_value' => $config->get('thumbnail'),
			'#description' => t("Thumbnails are used for slideshow navigation.  Image constrain is set to max_h for thumbnails"),
			'#required' => TRUE,
			'#field_suffix' => 'px'
		);
		$form['media']['medium'] = array(
			'#type' => 'number',
			'#title' => t('Search Results'),
			'#default_value' => $config->get('medium'),
			'#description' => t("Set the image size for images when appearing as part of the search results."),
			'#required' => TRUE,
			'#field_suffix' => 'px'
		);
		$form['media']['large'] = array(
			'#type' => 'number',
			'#title' => t('Object Page'),
			'#default_value' => $config->get('large'),
			'#description' => t("Set the image size for the object page."),
			'#required' => TRUE,
			'#field_suffix' => 'px'
		);

		$form['media']['constrain'] = array(
			'#type' => 'select',
			'#title' => t('Constrain Image To'),
			'#options' => array(
				'max_h' => t('Height'),
				'max_w' => t('Width'),
				'max' => t('Both')
			),
			'#default_value' => $config->get('constrain'),
			'#description' => t("Determine to apply the sizes above to the height, width or both."),
			'#required' => TRUE
		);
		$form['media']['sliderSource'] = array(
			'#title' => t('Media Renderer'),
			'#type' => 'radios',
			'#description' => t('Define whether the module or theme will handle the rendering of the object media.'),
			'#options' => array(
				'module' => t('Module'),
				'theme' => t('Theme'),
			),
			'#default_value' => $config->get('sliderSource'),
			'#required' => TRUE,
		);
		$form['media']['lazyload'] = array(
			'#type' => 'checkbox',
			'#title' => t('Lazy load images'),
			'#default_value' => $config->get('lazyload'),
      '#states' => [
        'visible' => [
          ':input[name="media[sliderSource]"]' => ['value' => 'module'],
        ],
      ],

    );

		$form['viewer'] = array(
			'#type' => 'details',
			'#title' => $this->t('Viewer Settings'),
			'#tree' => TRUE,
			'#group' => 'record',
			'#description' => $this->t('Record page and search.'),
		);

		$form['viewer']['mode'] = array(
			'#title' => $this->t('Viewer mode'),
			'#type' => 'radios',
			'#options' => array(
				'page' => $this->t('Open in new page'),
				'embed' => $this->t('Embedded on record page'),
			),
			'#default_value' => $viewer['mode'],
			'#description' => $this->t('Define if the viewer should be embedded or opened in a new page.  Embedded mode is performance intensive'),
			'#required' => TRUE,
		);

		$form['viewer']['features'] = array(
			'#title' => $this->t('Features'),
			'#type' => 'checkboxes',
			'#options' => array(
				'showRotationControl' => $this->t('Enable image rotation'),
				'showNavigator' => $this->t('Enable image navigator'),
			),
			'#default_value' => $viewer['features'],
			'#description' => $this->t('Check the features you want to enable.'),
		);
		$form['viewer']['navigatorPosition'] = array(
			'#title' => $this->t('Navigator Position'),
			'#type' => 'radios',
			'#options' => array(
				'TOP_LEFT' => $this->t('Top Left'),
				'TOP_RIGHT' => $this->t('Top Right'),
				'BOTTOM_LEFT' => $this->t('Bottom Left'),
				'BOTTOM_RIGHT' => $this->t('Bottom Right'),
			),
			'#default_value' => $viewer['navigatorPosition'],
			'#description' => $this->t('Position of the image navigator.'),
			'#states' => array(
				'visible' => array(
					':input[name="viewer[features][showNavigator]"]' => ['checked' => TRUE],
				),
			),
		);

    $form['template'] = array(
      '#type' => 'details',
      '#title' => $this->t('Template Settings'),
      '#tree' => TRUE,
      '#group' => 'record',
    );
    $form['template']['media_records'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Media Record Types'),
      '#description' => $this->t('Identify record types where the media should have the open access data and viewed in the image viewer.'),
      '#options' => $contentTypes,
      '#attributes' => ['class' => ['column-wrapper']],
      '#default_value' => $config->get('media_records') ?? [],
    ];
    $form['template']['alt_template'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Alternate Record Template'),
      '#description' => $this->t('Identify non-collection record types that should use an alternate template than the default record template. These record types will not have open access data and the image is not linked to the image viewer'),
      '#options' => $contentTypes,
      '#attributes' => ['class' => ['column-wrapper']],
      '#default_value' => $config->get('alt_template') ?? [],
    ];
    $form['#attached']['library'][] = 'edan/edan_form';
		return parent::buildForm($form, $form_state);
	}

	/**
	 * {@inheritdoc}
	 */
	public function validateForm(array &$form, FormStateInterface $form_state) {
		parent::validateForm($form, $form_state);
		$values = $form_state->getValues();
		if ($values['media']['sliderSource'] === 'module' && !\Drupal::service('module_handler')->moduleExists('slick')) {
			$form_state->setErrorByName(
				'media][sliderSource',
				$this->t(
					"The slick module must be enabled for the module to handle slideshow"
				)
			);
		}
		if ($values['media']['lazyload'] && !\Drupal::service('module_handler')->moduleExists('blazy')) {
			$form_state->setErrorByName(
				'media][lazyload',
				$this->t(
					"The blazy module must be enabled to lazyload the image"
				)
			);
		}
		$unit_codes = array_filter($values['access']['unit_codes']);
		if ($values['access']['limit_to_local'] && empty($unit_codes)) {
      $form_state->setErrorByName(
        'access][unit_codes',
        $this->t(
          "You must set unit codes if you want to limit content to local units."
        )
      );
    }
	}

	public function submitForm(array &$form, FormStateInterface $form_state) {
		$entity = $this->configFactory->getEditable('edan_record.settings');
		$features = [];
		$values = $form_state->getValues();
		$viewer = $values['viewer'];
		foreach ($viewer['features'] as $key => $value) {
			if ($value) {
				$features[$key] = $value;
			}
		}
		$viewer['features'] = $features;
		$entity->set('viewer', $viewer);
		foreach ($values['media'] as $i => $item) {
			$entity->set($i, $item);
		}
    foreach ($values['access'] as $i => $item) {
      $item = $i === 'unit_codes' ? array_filter($item) : $item;
      $entity->set($i, $item);
    }
    $alt_template = array_filter($values['template']['alt_template']);
    $entity->set('alt_template', $alt_template);
		$entity->save();
		// create edan slick optionset if it doesn't exist
		if ($values['media']['sliderSource'] === 'module' && \Drupal::service('module_handler')->moduleExists('slick')) {
			$config_install_path =  \Drupal::service('extension.list.module')
          ->getPath('edan_record') . '/' . InstallStorage::CONFIG_OPTIONAL_DIRECTORY;
			if (is_dir($config_install_path)) {
				// scan directory for record config entities.
				$settings_config_files = \Drupal::service('file_system')->scanDirectory($config_install_path, '/^slick\.optionset\.edan_nav\.yml$/i');
				$entityManager = \Drupal::service('entity_type.manager');
				if (isset($settings_config_files) && is_array($settings_config_files)) {
					foreach ($settings_config_files as $settings_config_file) {
						$settings_config_file_content = file_get_contents(DRUPAL_ROOT . '/' . $settings_config_file->uri);
						$settings_config_file_data = (array) Yaml::parse($settings_config_file_content);
						$entity = $entityManager ->getStorage('slick')->load($settings_config_file_data['id']);
						if (!$entity) {
							$config_factory = \Drupal::configFactory()->getEditable($settings_config_file->name);
							$config_factory->setData($settings_config_file_data)->save(TRUE);
							if (\Drupal::service('module_handler')->moduleExists('slick_ui')) {
								$this->messenger()->addStatus($this->t('Added the edan slick optionset. Visit the <a href="@link">slick UI</a>', ['@link' => Url::fromRoute('entity.slick.collection')->toString()]));
							}
							else {
								$this->messenger()->addStatus($this->t('Added the edan slick optionset.'));
							}
						}
					}
				}
			}
		}

//		$form_state->setValue('testValue', $items);
	//	$form_state->setRebuild();

//		$this->configFactory->getEditable('edan_record.settings')
//			->set('thumbnail', $form_state->getValue('slick_css'))
//			->set('module_css', $form_state->getValue('module_css'))
//			->set('disable_old_skins', $form_state->getValue('disable_old_skins'))
//			->save();
		parent::submitForm($form, $form_state);

	}


}
