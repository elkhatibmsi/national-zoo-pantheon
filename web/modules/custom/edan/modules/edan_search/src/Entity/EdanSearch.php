<?php

namespace Drupal\edan_search\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Edan search entity entity.
 *
 * @ConfigEntityType(
 *   id = "edan_search",
 *   label = @Translation("EDAN search entity"),
 *   label_collection = @Translation("EDAN Searches"),
 *   label_singular = @Translation("EDAN search"),
 *   label_plural = @Translation("EDAN searches"),
 *   label_count = @PluralTranslation(
 *     singular = "@count EDAN search",
 *     plural = "@count EDAN searches",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\edan_search\Entity\EdanSearchAccessControlHandler",
 *     "list_builder" = "Drupal\edan_search\EdanSearchEntityListBuilder",
 *     "view_builder" = "Drupal\edan_search\Entity\EdanSearchEntityViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\edan_search\Form\EdanSearchEntityForm",
 *       "edit" = "Drupal\edan_search\Form\EdanSearchEntityForm",
 *       "delete" = "Drupal\edan_search\Form\EdanSearchEntityDeleteForm",
 *       "duplicate" = "Drupal\edan_search\Form\EdanSearchEntityForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\edan_search\EdanSearchHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer edan search form entity",*
 *   config_prefix = "optionset",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "status",
 *     "description",
 *     "summary",
 *     "edan_settings",
 *     "render_settings",
 *     "fields",
 *     "facets"
 *   },
 *   links = {
 *     "canonical" = "/edan/search/{edan_search}",
 *     "add-form" = "/admin/config/edan/edan-search/add",
 *     "edit-form" = "/admin/config/edan/edan-search/{edan_search}/edit",
 *     "duplicate-form" =  "/admin/config/edan/edan-search/{edan_search}/duplicate",
 *     "delete-form" = "/admin/config/edan/edan-search/{edan_search}/delete",
 *     "collection" = "/admin/config/edan/edan-search"
 *   }
 * )
 */
class EdanSearch extends ConfigEntityBase implements EdanSearchInterface {

  /**
   * The EDAN search entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The EDAN search entity label.
   *
   * @var string
   */
  protected $label;

	/**
	 * The EDAN search entity description for admin page.
	 *
	 * @var string
	 */
	protected $description;

  /**
   * The EDAN search entity description for block/page.
   *
   * @var string
   */
  protected $summary;

  /**
	 * The edan settings for the EDAN search entity.
	 *
	 * @var array
	 */
	protected $edan_settings = [];
  /**
   * The render settings for the EDAN search entity.
   *
   * @var array
   */
  protected $render_settings = [];
  /**
   * The field override settings for the EDAN search entity.
   *
   * @var array
   */
  protected $fields = [];
  /**
   * The facet settings for the EDAN search entity.
   *
   * @var array
   */
  protected $facets = [];
	/**
	 * @var array $info to set for theme suggestions
	 */
  protected  $info = [];

  public function setRows($row) {
    $this->render_settings['results_per_page'] = $row;
	}

	public function setSettings($setting, $properties = []) {
		$this->$setting = $properties;
	}


	public function getTitle() {
		return $this->label;
	}

	public function getEdanSettings() {
	  return $this->edan_settings;
  }

  public function getRenderSettings() {
    return $this->render_settings;
  }

  public function getFieldOverrides() {
    return $this->fields;
  }

  public function getFacetSettings() {
    return $this->facets;
  }
	/**
	 * Wraps the edan search manager.
	 *
	 * @return \Drupal\edan_search\EdanSearchManager
	 *   A EdanSearchManager object.
	 */
	public function getEdanSearchManager() {
		return \Drupal::service('edan_search.manager');
	}

  /**
   * {@inheritdoc}
   */
  public function getSettings($setting, $property = NULL) {
    $configuration = $this->$setting;
    return $property ? (isset($configuration[$property]) ? $configuration[$property] : NULL) : $configuration;
  }
}
