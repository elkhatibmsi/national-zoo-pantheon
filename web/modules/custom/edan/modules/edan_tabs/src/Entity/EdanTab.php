<?php

namespace Drupal\edan_tabs\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Path\AliasStorage;

/**
 * Defines the Edan tab entity.
 *
 * @ConfigEntityType(
 *   id = "edan_tab",
 *   label = @Translation("Edan tab"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\edan_tabs\EdanTabListBuilder",
 *     "form" = {
 *       "add" = "Drupal\edan_tabs\Form\EdanTabEntityForm",
 *       "edit" = "Drupal\edan_tabs\Form\EdanTabEntityForm",
 *       "delete" = "Drupal\edan_tabs\Form\EdanTabEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\edan_tabs\Routing\EdanTabHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "tabs",
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
 *     "landing",
 *     "landing_label",
 *     "searches"
 *   },
 *   links = {
 *     "add-form" = "/admin/config/edan/edan_tab/add",
 *     "edit-form" = "/admin/config/edan/edan_tab/{edan_tab}/edit",
 *     "delete-form" = "/admin/config/edan/edan_tab/{edan_tab}/delete",
 *     "collection" = "/admin/config/edan/edan_tab"
 *   }
 * )
 */
class EdanTab extends ConfigEntityBase {

  /**
   * The Edan search tab ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Edan search tab label.
   *
   * @var string
   */
  protected $label;
  /**
   * The path alias pid.
   *
   *
   * @var string
   */
  protected $path;

  /**
   * Indicates if there is a landing page with aggregates for the
   * @var boolean
   */
  protected $landing;
  /**
   * Label for landing tab.
   *
   *
   * @var string
   */
  protected $landing_label;

  /**
   * The configuration of the searches.
   *
   * @var array
   */
  protected $searches = [];

  public function hasLanding() {
    return $this->landing;
  }

  public function getFirstSearch() {
    return current($this->searches);
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->path;
  }

  public function getSearches() {
    return $this->searches;
//    if ($this->landing) {
//      return $this->searches;
//    }
//    else {
//      $searches = $this->searches;
//      array_shift($searches);
//      return $searches;
//    }
  }

}
