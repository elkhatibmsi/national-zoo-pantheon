<?php

namespace Drupal\edan;

use Drupal\Component\Utility\Html;

/**
 * Class EdanFacets.
 */
class EdanFacets {

  protected $pageParameters = [];
  protected $facet_labels = [];
  protected $facets_hide = [];

  /**
   * Constructs a new EdanFacets object.
   */
  public function __construct(array $pageParams, array $facet_labels, array $facets_hide) {
    $this->pageParameters = $pageParams;
    $this->facet_labels = $facet_labels;
//    $this->facet_labels['p.objectgroup.featured'] = 'Featured';
//    $this->facet_labels['p:event:categories'] = 'Category';
//    $this->facet_labels['p:event:location:extended.location_id'] = 'Museum';
    $this->facets_hide = array_combine($facets_hide, $facets_hide);;
  }


	public function process_facets($facets) {
		/* provides an array of facets, modified according to settings in the admin UI, meaning
			1. re-ordered
			2. only the specified facets, removing any in the explicit 'remove' box
			3. renamed
		*/
		$uri = SearchParams::args_to_uri($this->pageParameters);
		$facets_raw = $new_args = [];
		$facets = array_diff_key($facets, $this->facets_hide);
		if (!empty($facets) && is_array($facets)) {
		if (empty($this->facet_labels) || !is_array($this->facet_labels)) {
			foreach ($facets as $field_key => $vals) {
				if (!array_key_exists($field_key, $facets_raw)) {
					if (count($facets[$field_key]) > 1 || $field_key !== 'docFldUsed') {
						$value = $this->process_facets_fields($facets[$field_key], $field_key, $uri);
						if (!empty($value)) {
							$facets_raw[$field_key]['values'] = $value;
							$facets_raw[$field_key]['facet_label'] = $field_key;
							$facets_raw[$field_key]['id'] = Html::getId($field_key);
						}
					}
				}
			}
		}
		else {
				// if facet order is specified, re-order accordingly
				foreach ($this->facet_labels as $field_key => $field_nicename) {
					// exclude any facets in the explicit 'remove facet' box
					$field_key = str_replace(':', '.', $field_key);
					if (isset($facets[$field_key])) {
						if (!empty($facets[$field_key]) || $field_key !== 'docFldUsed') {
							$value =  $this->process_facets_fields($facets[$field_key], $field_key, $uri);
							if (!empty($value)) {
								$facets_raw[$field_key]['values'] = $value;
								$facets_raw[$field_key]['facet_label'] = $field_nicename;
								$facets_raw[$field_key]['id'] = Html::getId($field_key);
							}
						}
					}
				}

				// now add any remaining fields which we might not have renamed
				foreach ($facets as $field_key => $vals) {
					if (!array_key_exists($field_key, $facets_raw) && $field_key !== 'docFldUsed' && !empty($vals)) {
						$value =  $this->process_facets_fields($facets[$field_key], $field_key, $uri);
						if (!empty($value)) {
							$facets_raw[$field_key]['values'] = $value;
							$facets_raw[$field_key]['facet_label'] = $field_key;
							$facets_raw[$field_key]['id'] = Html::getId($field_key);
						}
					}
				}
			} // if we have some fields/facets
		}
//    dump($facets_raw);
    return $facets_raw;
	}

	private function process_facets_fields($fields, $field, $uri = '') {
		$out = $map = [];
		$prefix = empty($uri) ? '' : $uri . '&';
		$prefix = urldecode(trim($prefix));
    if (isset($fields) && is_array($fields)) {
			if ($field === 'p.objectgroup.featured') {
				$map = array(
					0 => array(
						'title' => t('No')
					),
					1 => array(
						'title' => t('Yes')
					),
				);
			}
      else {
        $map = [
          'true' => [
            'title' => t('Yes')
          ],
          'false' => [
            'title' => t('No')
          ],
        ];
      }
      $is_location = $field == 'p.event.location.extended.location_id';
      if ($is_location) {
        $edanConnector = \Drupal::service('edan.connector');
        $map = $edanConnector->get_museums();
        $map = $map['docs'];

      }
			foreach ($fields as $fld) {
			  if ($fld[0]) {
			    $facet_value = $field . ':' . '"' . $fld[0] . '"';
          $string = 'edan_fq[]=' . $facet_value;
          if (empty($uri) || strpos($prefix, $string) === FALSE) {
            $out[$fld[0]] = array(
              'uri' => $prefix . $string,
              'exclude' => $prefix .'edan_fq[]=-'. $facet_value,
              'value' => isset($map[$fld[0]]) ? $map[$fld[0]]['title'] : $fld[0],
              'count' => number_format($fld[1])
            );
          }
        }
			}
		}
		if ($field == 'date') {
			ksort($out);
		}

		return $out;
	}

  public function get_activeFacets() {
		$facets_raw = [];
		$args = $this->pageParameters;
			// Get the current URL

			if (isset($args['q']) && $args['q'] != '') {
				$new_args = $args;
				unset($new_args['q']);
				$new_uri = SearchParams::args_to_uri($new_args);

					$facets_raw['search_term'] = array(
						'facet_title' => 'Search term: ' . $args['q'],
						'uri' => $new_uri,
						'class' => 'search-term',
						'facet' => 'search term',
						'value' => $args['q']
					);
			}
			// Facets/FQs
			if (isset($args['fqs'])) {
				// Loop over the existing facets
				foreach ($args['fqs'] as $i => $fq) {
					if (strstr($fq, ' OR ')) {
						$new_args = $args; // Fresh copy of args
						unset($new_args['fqs'][$i]);
						$info = explode(' OR ', $fq);
						for ($k = 0; $k < count($info); $k++) {
							$clone = $new_args;
							$_fq = $info[$k];
							$new_fq = $info;
							unset($new_fq[$k]);

							// add the OR back in remaining fqs
							$or_fq = implode(' OR ', $new_fq);
							$clone['fqs'] = array_merge($clone['fqs'], array($or_fq));
							$new_uri = SearchParams::args_to_uri($clone);
							$t = explode(':', $_fq, 2);
							$p = count($t) > 1 ? $t[1] : '';
							if (strlen($p) > 0) {
								$label = $this->get_active_label($t[0], $t[1]);
								$facets_raw[$_fq] = array(
									'facet_title' => ucfirst(str_replace('_', ' ', $label)),
									'uri' => $new_uri,
									'class' => Html::getClass($t[0]),
									'facet' => $t[0],
									'value' => $t[1]
								);
							}
						}
					}
					else {
						$new_args = $args; // Fresh copy of args
						unset($new_args['fqs'][$i]);
						$new_uri = SearchParams::args_to_uri($new_args);
						$t = explode(':', $fq, 2);
						$p = count($t) > 1 ? $t[1] : '';
						if ($t[0] == 'online_visual_material') {
							$p = 'online media';
						}

						if (strlen($p) > 0) {
							$label = $this->get_active_label($t[0], $t[1]);
							$facets_raw[$fq] = array(
								'facet_title' => ucfirst(str_replace('_', ' ', $label)),
								'uri' => $new_uri,
								'class' => Html::getClass($t[0]),
								'facet' => $t[0],
								'value' => $t[1]
							);
						}
					}

				}
			}
		return $facets_raw;

	}

	function get_active_label($key, $value) {
		$boolean = [
			0 => t('No'),
			"1" => t('Yes'),
      "true" => t('Yes')
		];
		$value = str_replace('"', '', $value);
		$value = $boolean[$value] ?? $value;
		$is_exclude = substr($key, 0, 1) === '-';
		if ($is_exclude) {
			$key = substr($key, 1);
		}
		$is_location = $key == 'p.event.location.extended.location_id';
		if ($is_location) {
			$edanConnector = \Drupal::service('edan.connector');
			$map = $edanConnector->get_museums();
			$map = $map['docs'];
		}
		$key_updated = str_replace('.', ':', $key);
		$value_updated = isset($map[$value]) ? $map[$value]['title'] : $value;
		$label = isset($this->facet_labels[$key_updated]) ? $this->facet_labels[$key_updated] : $key_updated;
		$fq = $label .': '. $value_updated;
		if ($is_exclude) {
			$fq = '(-) <span class="visually-hidden">' . t('excluded') . '</span>' . $fq;
		}

		return $fq;
	}
}
