<?php

namespace Drupal\edan;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Component\Utility\Html;
use Drupal\edan\Connector\EdanConnectorService;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class EdanManagerBase {

	/**
	 * The entity type manager service.
	 *
	 * @var \Drupal\Core\Entity\EntityTypeManagerInterface
	 */
	protected $entityTypeManager;

	/**
	 * The module handler service.
	 *
	 * @var \Drupal\Core\Extension\ModuleHandlerInterface
	 */
	protected $moduleHandler;

	/**
	 * The config factory.
	 *
	 * @var \Drupal\Core\Config\ConfigFactoryInterface
	 */
	protected $configFactory;

	/**
	 * Drupal\edan\Connector\EdanConnectorService definition.
	 *
	 * @var \Drupal\edan\Connector\EdanConnectorService
	 */
	protected $edanConnector;


	/**
	 * Constructs a EdanManager object.
	 */
	public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, EdanConnectorService $edan_connector) {
		$this->entityTypeManager = $entity_type_manager;
		$this->moduleHandler     = $module_handler;
		$this->configFactory     = $config_factory;
		$this->edanConnector = $edan_connector;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container) {
		 return new static(
			$container->get('entity_type.manager'),
			$container->get('module_handler'),
			$container->get('config.factory'),
			$container->get('edan.connector')
		);
	}

	/**
	 * Returns the entity type manager.
	 */
	public function getEntityTypeManager() {
		return $this->entityTypeManager;
	}

	/**
	 * Returns the module handler.
	 */
	public function getModuleHandler() {
		return $this->moduleHandler;
	}

	/**
	 * Returns the config factory.
	 */
	public function getConfigFactory() {
		return $this->configFactory;
	}

  /**
   * Returns the EdanConnector factory.
   */
  public function getEdanConnector() {
    return $this->edanConnector;
  }

	/**
	 * Returns any config, or keyed by the $setting_name.
	 */
	public function configLoad($setting_name = '', $settings = 'edan.settings') {
		$config  = $this->configFactory->get($settings);
		$configs = $config->get();
		unset($configs['_core']);
		return empty($setting_name) ? $configs : $config->get($setting_name);
	}

  /**
   * sets
   */
  public function get($property_name) {
    return isset($this->{$property_name}) ? $this->{$property_name} : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value) {
    $this->{$property_name} = $value;

    return $this;
  }
	/**
	 * Returns a shortcut for loading a config entity: edan_record, edan_search, etc.
	 */
	public function entityLoad($id, $entity_type = 'edan_record') {
		return $this->entityTypeManager->getStorage($entity_type)->load($id);
	}

	/**
	 * Returns a shortcut for loading multiple configuration entities.
	 */
	public function entityLoadMultiple($entity_type = 'edan_record', $ids = NULL) {
		return $this->entityTypeManager->getStorage($entity_type)->loadMultiple($ids);
	}

	/**
	 * Returns a shortcut for loading multiple configuration entities.
	 */
	public function getActiveEntities($entity_type = 'edan_search') {
		$ids = $this->entityTypeManager->getStorage($entity_type)->getQuery()
			->condition('status', TRUE)
			->execute();

		return $ids ? $this->entityLoadMultiple($entity_type, $ids) : [];
	}

  /**
   * Returns the trusted HTML ID of a single instance.
   */
  public static function getHtmlId($string = 'edan-search', $id = '') {
    $id = empty($id) ? $string . '-' . Html::getUniqueId : $string .'-'. $id;
    return Html::getId($id);
  }

}
