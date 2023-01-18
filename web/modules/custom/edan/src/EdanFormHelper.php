<?php

namespace Drupal\edan;

/**
 * Class EdanFormHelper.
 */
class EdanFormHelper {

	/**
	 * Generates a string representation of an array of textarea values.
	 *
	 * This string format is suitable for edition in a textarea.
	 *
	 * @param array $values
	 *   An array of values, where array keys are values and array values are
	 *   labels.
	 * @param boolean $pair
	 *   A boolean flag indicating whether the $values contain key/value pairs.
	 *
	 * @return string
	 *   The string representation of the $values array:
	 *    - Values are separated by a carriage return.
	 *    - Each value is in the format "value|label" or "value".
	 *
	 */
	public function generateTextAreaValues($values, $pair = FALSE) {
		if (!$values) {
			return '';
		}
		$lines = [];
		foreach ($values as $key => $value) {
			if ($pair) {
				$lines[] = "$key|$value";
			}
			else {
				$lines[] = $value;
			}
		}
		return $lines ? implode("\n", $lines) :'';
	}

	/**
	 * Extracts values array from the textarea element.
	 *
	 * @param string $string
	 *   The raw string to extract values from.
	 * @param boolean $pair
	 *   A boolean flag indicating whether the $values contain key/value pairs.
	 *
	 * @return array|null
	 *   The array of extracted key/value pairs, or NULL if the string is invalid.
	 *
	 * @see \Drupal\options\Plugin\Field\FieldType\ListItemBase::extractAllowedValues()
	 */
	public function extractTextAreaValues($string, $pair = FALSE, $array_key = TRUE) {
	  if (!$string) {
	    return [];
    }
		$values = [];

		$list = explode("\n", $string);
		$list = array_map('trim', $list);
		$list = array_filter($list, 'strlen');

		foreach ($list as $position => $text) {
			// Check for an explicit key.
			$matches = [];

			// see if we can use line item as single value
			if (!$pair) {
				if ($array_key) {
					$values[$text] = $text;
				}
				else {
					$values[] = $text;
				}
			}
			// split line item
			elseif ($pair && preg_match('/(.*)\|(.*)/', $text, $matches)) {
				// Trim key and value to avoid unwanted spaces issues.
				$values[trim($matches[1])] = trim($matches[2]);
			}
		}

		return $values;
	}

	public function constructHiddenArray($values) {
		$hide_array = array();
		foreach ($values as $field) {
			$field_name = $field;
			$val = $field;
			if (strstr($field, '|')) {
				$field_array = explode('|', $field_name);
				$field_name = $field_array[0];
				$val = $field_array[1];
			}
			$hide_array[$field_name][$val] = $val;
		}
		return $hide_array;
	}

	public function constructHiddenText($values = NULL) {
		$text_array = [];
		if (empty($values)) return '';
		foreach($values as $field => $items) {
			foreach($items as $item) {
				$text_array[] = $field === $item ? $field : $field .'|'. $item;
			}
		}
		return implode("\n", $text_array);
	}

	public function getUnitCodes() {
		$unitCodes = [];
		// retrieve cache
		$cache = \Drupal::cache()->get('edan_unit_codes');

		if ($cache) { // return cached data
			$unitCodes = $cache->data;
		}
		else { // fetch data and cache it
			// fetch unit codes from edan
			$responseValues = \Drupal::service('edan.connector')->getFacetField('unit_code','', -1, 0, 'index');
			if ($responseValues) {
				// filter out invalid unit codes
				foreach($responseValues as $unit) {
					// avoid empty and null unit codes
					if ($unit[0] && $unit[0] !== 'null') {
						$unitCodes[] = $unit[0];
					}
				}

				// convert to an associative array
				// (e.g. [one, two] => [one => one, two => two])
				$unitCodes = array_combine($unitCodes, $unitCodes);
				// set cache
				$expire = time() + (7 * 24 * 60 * 60);
				\Drupal::cache()->set('edan_unit_codes', $unitCodes, $expire);
			}
		}

		return $unitCodes;
	}

}
