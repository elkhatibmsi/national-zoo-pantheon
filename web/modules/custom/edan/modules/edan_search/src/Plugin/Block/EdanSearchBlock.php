<?php

namespace Drupal\edan_search\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\edan_search\EdanSearchManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'EDAN Search' block.
 *
 * @Block(
 *   id = "edan_search_block",
 *   admin_label = @Translation("EDAN Search Block"),
 *   category = @Translation("EDAN")
 * )
 */
class EdanSearchBlock extends BlockBase implements ContainerFactoryPluginInterface {

	/**
	 * The search page repository.
	 *
	 * @var \Drupal\edan_search\EdanSearchManagerInterface
	 */
	protected $edanSearchManager;

	/**
	 * Constructs edan search block.
	 *
	 * @param array $configuration
	 *   A configuration array containing information about the plugin instance.
	 * @param string $plugin_id
	 *   The plugin ID for the plugin instance.
	 * @param mixed $plugin_definition
	 *   The plugin implementation definition.
	 * @param \Drupal\edan_search\EdanSearchManagerInterface
	 *   The edan search mananger.
	 */
	public function __construct(array $configuration, $plugin_id, $plugin_definition, EdanSearchManagerInterface $edanSearchManager) {
		parent::__construct($configuration, $plugin_id, $plugin_definition);
		$this->edanSearchManager = $edanSearchManager;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
		return new static($configuration, $plugin_id, $plugin_definition,
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
		$search = $this->edanSearchManager->entityLoad($this->configuration['edan_search'], 'edan_search');

	//	$view_builder = \Drupal::entityManager()->getViewBuilder('edan_search');
    if ($search) {
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('edan_search');

      return $view_builder->view($search);
    }
    return '';
	}

	/**
	 * {@inheritdoc}
	 */
	public function defaultConfiguration() {
		return [
			'edan_search' => '',
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

		return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function blockSubmit($form, FormStateInterface $form_state) {
		$this->configuration['edan_search'] = $form_state->getValue('edan_search');
	}

}
