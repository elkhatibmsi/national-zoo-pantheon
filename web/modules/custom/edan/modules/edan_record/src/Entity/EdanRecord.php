<?php

namespace Drupal\edan_record\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Edan record entity.
 *
 * @ConfigEntityType(
 *   id = "edan_record",
 *   label = @Translation("Edan record"),
 *   handlers = {
 *     "access" = "Drupal\edan_record\Entity\EdanRecordAccessControlHandler",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\edan_record\EdanRecordListBuilder",
 *     "form" = {
 *       "add" = "Drupal\edan_record\Form\EdanRecordForm",
 *       "edit" = "Drupal\edan_record\Form\EdanRecordForm",
 *       "delete" = "Drupal\edan_record\Form\EdanRecordDeleteForm",
 *       "duplicate" = "Drupal\edan_record\Form\EdanRecordForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\edan_record\Routing\EdanRecordHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "type",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "path",
 *     "path_key",
 *     "summary_fields",
 *     "record_fields",
 *     "hidden_fields",
 *     "teaser_fields",
 *     "label_replacements"
 *   },
 *   links = {
 *     "add-form" = "/admin/config/edan/edan-record/add",
 *     "edit-form" = "/admin/config/edan/edan-record/{edan_record}/edit",
 *     "delete-form" = "/admin/config/edan/edan-record/{edan_record}/delete",
 *     "duplicate-form" =  "/admin/config/edan/edan-record/{edan_record}/duplicate",
 *     "collection" = "/admin/config/edan/edan-record"
 *   }
 * )
 */
class EdanRecord extends ConfigEntityBase implements EdanRecordInterface {

	/**
	 * The EDAN record ID.
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * The EDAN record label.
	 *
	 * @var string
	 */
	protected $label;

	/**
	 * The Edan record path.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * The Edan record label.
	 *
	 * @var string
	 */
	protected $path_key;

	/**
	 * The Edan record label.
	 *
	 * @var array
	 */
	protected $summary_fields = [];

	/**
	 * The Edan record label.
	 *
	 * @var array
	 */
	protected $record_fields = [];

	/**
	 * The Edan record label.
	 *
	 * @var array
	 */
	protected $hidden_fields = [];

	/**
	 * The Edan record label.
	 *
	 * @var array
	 */
	protected $teaser_fields = [];

	/**
	 * The Edan record label.
	 *
	 * @var array
	 */
	protected $label_replacements = [];


	/**
	 * {@inheritdoc}
	 */
	public function postSave(EntityStorageInterface $storage, $update = TRUE) {
		parent::postSave($storage, $update);
		$this->routeBuilder()->rebuild();
	}

	/**
	 * Wraps the route builder.
	 *
	 * @return \Drupal\Core\Routing\RouteBuilderInterface
	 *   An object for state storage.
	 */
	protected function routeBuilder() {
		return \Drupal::service('router.builder');
	}

	public function getManager() {
		return \Drupal::service('edan_record.manager');
	}

	public function getRecordSettings($teaser = FALSE) {
		return [
			'summary_fields' => $this->summary_fields,
			'fields' => $teaser ? $this->teaser_fields : $this->record_fields,
			'hidden_fields' => $this->hidden_fields,
			'label_replacements' => $this->label_replacements,
			'path_key' => $this->path_key,
			'path' => $this->path
		];
	}

	public function getTeaserFields() {
		return $this->teaser_fields;
	}

	public function getLabelReplacements() {
		return $this->label_replacements;
	}

	public function setTeaserFields($setting = []) {
	  if ($setting) {
      $this->teaser_fields = $setting;
    }
  }

  public function setLabelReplacements($setting = []) {
    if ($setting) {
      $this->label_replacements = $setting;
    }
  }
}
