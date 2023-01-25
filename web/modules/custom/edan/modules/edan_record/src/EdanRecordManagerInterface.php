<?php

namespace Drupal\edan_record;

/**
 * Interface EdanRecordManagerInterface.
 */
interface EdanRecordManagerInterface {

	// Load single edan record.
	/**
	 * @param $edan_id
	 * @param $path_key
	 * @param $type
	 * @param array $settings
	 *   - if the edan record should be passed to the EdanRecordProcess service
	 * @param array $search
	 *   - use search endpoint if search parameters are given
	 * @return array edan_record
	 */
	public function getRecord($edan_id, $path_key = '[edan:url]', $type = NULL, $settings = [], $search = [],  $reset = FALSE);

	/**
	 * @param $doc
	 * @return array of related edan record
	 */
	public function getRelated(&$doc);

	/**
	 * Returns a renderable array of edan record.
	 *
	 * @param array $doc
	 * @return array
	 *   The renderable array based on view_mode.
	 */
	public function renderRecord($doc, $view_mode = 'full', $embed = FALSE);

}
