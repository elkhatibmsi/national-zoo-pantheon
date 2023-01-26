<?php

namespace Drupal\edan_record;

/**
 * Provides an interface for processing Edan record entities.
 */
interface EdanRecordProcessInterface{

  // normalize edan record data.
	public function constructData(&$doc);

	// process data based on record entity configuration

	/**
	 * @param $doc
	 * @param $settings
	 *  array of settings for record [summary_fields, fields, hidden, label_replacements
	 * @return array processed doc
	 */
	public function processData(&$doc, $settings, $teaser = FALSE);

	public function getMedia(&$doc);

	public static function record_array_flatten($array, $parent = '');


}
