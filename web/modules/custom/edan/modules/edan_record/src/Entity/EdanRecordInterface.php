<?php

namespace Drupal\edan_record\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Edan record entities.
 */
interface EdanRecordInterface extends ConfigEntityInterface {

	public function getTeaserFields();

	public function getLabelReplacements();

	public function setTeaserFields($setting = []);

	public function setLabelReplacements($setting = []);

	public function getRecordSettings($teaser = FALSE);
}
