<?php

namespace Drupal\edan_search\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Edan search entity entities.
 */
interface EdanSearchInterface extends ConfigEntityInterface {

	/**
	 * Returns the EDAN search configurations by property.
	 *
	 * @param string $setting
	 *   The name of specific setting: edan_settings, render_settings, $fields .
	 *
	 * @return mixed|array|null
	 *   Available options by $property, all, or NULL.
	 */
  public function getSettings($setting, $property = NULL);

}
