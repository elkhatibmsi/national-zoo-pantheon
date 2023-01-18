<?php

namespace Drupal\edan_search\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\edan_search\EdanSearchManagerInterface;
use Drupal\edan_search\Form\EdanSearchForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'EDAN Search form' block.
 *
 * @Block(
 *   id = "edan_search_form_block",
 *   admin_label = @Translation("EDAN Search form"),
 *   category = @Translation("EDAN")
 * )
 */
class EdanSearchBlockForm extends BlockBase implements ContainerFactoryPluginInterface {

	/**
	 * The form builder.
	 *
	 * @var \Drupal\Core\Form\FormBuilderInterface
	 */
	protected $formBuilder;

	/**
	 * The search page repository.
	 *
	 * @var \Drupal\edan_search\EdanSearchManagerInterface
	 */
	protected $edanSearchManager;

	/**
	 * Constructs a new SearchLocalTask.
	 *
	 * @param array $configuration
	 *   A configuration array containing information about the plugin instance.
	 * @param string $plugin_id
	 *   The plugin ID for the plugin instance.
	 * @param mixed $plugin_definition
	 *   The plugin implementation definition.
	 * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
	 *   The form builder.
	 * @param \Drupal\edan_search\EdanSearchManagerInterface
	 *   The search page repository.
	 */
	public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder, EdanSearchManagerInterface $edanSearchManager) {
		parent::__construct($configuration, $plugin_id, $plugin_definition);
		$this->formBuilder = $form_builder;
		$this->edanSearchManager = $edanSearchManager;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
		return new static($configuration, $plugin_id, $plugin_definition,
			$container->get('form_builder'),
			$container->get('edan_search.manager')
		);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function blockAccess(AccountInterface $account) {
		return AccessResult::allowedIfHasPermission($account, 'view edan search form entity');
	}

	/**
	 * {@inheritdoc}
	 */
	public function build() {
		$search = $this->configuration['edan_search'] ?? NULL;
		$search = $this->edanSearchManager->entityLoad($search, 'edan_search');
		if ($this->configuration['display_extra'] == FALSE) {
      $renderSettings = $search->getRenderSettings();
      $renderSettings['set_online_media'] = FALSE;
      $renderSettings['search_form_open_access'] = FALSE;
      $search->set('render_settings', $renderSettings);
    }

		return $this->formBuilder->getForm(EdanSearchForm::class, $search, TRUE);
	}

	/**
	 * {@inheritdoc}
	 */
	public function defaultConfiguration() {
		return [
			'edan_search' => '',
      'display_extra' => FALSE
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function blockForm($form, FormStateInterface $form_state) {
		// The configuration for this block is which search page to connect the
		// form to. Options are all configured/active search pages.
		$options = [];
		$searches = $this->edanSearchManager->entityLoadMultiple('edan_search');
		foreach ($searches as  $entity_id => $entity) {
			$options[$entity_id] = $entity->label();
		}

		$form['edan_search'] = [
			'#type' => 'select',
			'#title' => $this->t('EDAN Search'),
			'#description' => $this->t('The EDAN search that the form should be directed to.'),
			'#default_value' => $this->configuration['edan_search'],
			'#options' => $options,
			'#required' => TRUE
		];
    $form['display_extra'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display Extra'),
      '#default_value' => $this->configuration['display_extra'],
      '#description' => $this->t('If this box is not checked, then the EdanSearch\'s Open Access and Media checkbox settings will be ignored.')
    ];
		return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function blockSubmit($form, FormStateInterface $form_state) {
		$this->configuration['edan_search'] = $form_state->getValue('edan_search');
    $this->configuration['display_extra'] = $form_state->getValue('display_extra');

	}

}
