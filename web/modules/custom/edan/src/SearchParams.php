<?php

namespace Drupal\edan;

use Drupal\Component\Utility\Xss;

/**
 * Class SearchParams.
 */
class SearchParams {
  private $rows;
  private $params;

  /**
   * Constructs a new SearchParams object.
   */
  public function __construct($rows = 10) {
  	$this->rows = $rows;
  }


  public function getParameters() {
  	if (empty($this->params)) {
  		$this->getPageParameters();
	  }
    return $this->params;
  }

  public function setRows($rows) {
    $this->rows = $rows;
  }

	/**
	 * Converts an EDAN args array to a Drupal-compatibile URL string. Note that this does not
	 * use Drupal's l/url functions due to EDAN's handling of arrays.
	 */
	public static function args_to_uri($args = array()) {
		$str = '';

		if (isset($args['q']) && $args['q'] !== '') {
			$str .= '&edan_q=' . $args['q'];
		}

		if (isset($args['fqs'])) {
			foreach ($args['fqs'] as $fq) {
				if (strpos($fq, ':') !== FALSE) {
					list($name, $value) = explode(':', $fq, 2);
					$_fq = $name . ':' . urlencode($value);
					$str .= '&edan_fq[]=' . $_fq;
				}
			}
		}

		if (isset($args['search_all']) && $args['search_all'] != FALSE) {
			$str .= '&search_all=1';
		}
		return trim($str, '&');
	}
  /**
   * Gets the url parameters
   * @return array
   */
  protected function getPageParameters() {
    $args = [];
    $args['q'] = static::getRouteParam('edan_q'); //  isset($_GET['edan_q']) ? $_GET['edan_q'] : $default;
    //if ($this->getRouteParam('sort',[], NULL)) $args['sort'] = $this->getRouteParam('sort', [], NULL);
    $args['sort'] = static::getRouteParam('sort');
    $page =  \Drupal::service('pager.parameters')->findPage() ;
    $args['start'] = $page * $this->rows; // $_GET['page'] *

    // FQs
    $fqs = [];

    // New handling: php native arrays: edan_fq[]=1&edan_fq[]=2
    $get_edan_fq = static::getRouteParam('edan_fq', []);

    if (isset($get_edan_fq)) {
      if (!is_array($get_edan_fq)) {
        $get_edan_fq = array($get_edan_fq);
      }
      foreach ($get_edan_fq as $fq) {
        $fqs[] = $fq;
      }
    }
		$args['search_all'] = static::getRouteParam('search_all',FALSE);
    $args['fqs'] = $fqs;

    $args['rows'] = static::getRouteParam('rows', $this->rows);
    $this->params = $args;
  }

  public function setParam($name, $value, &$parameters) {
	  if (is_array($value)) {
	  	$items = [];
	  	foreach ($value as $item) {
	  		$items[] = Xss::filter($item, []);
		  }
		  $parameters[$name] = $items;
	  }
	  else {
		  $parameters[$name] = Xss::filter($value, []);
	  }
  }

  public static function getRouteParam($name, $default = NULL, $params = []) {
    $params = empty($params) ?  \Drupal::request()->query->all() : $params;
    $urlParam = $params[$name] ?? NULL;
    if ($urlParam) {
      if (is_array($urlParam)) {
        $arr = [];
        foreach ($urlParam  as $key => $value) {
          if ($value) {
            $newvalue = Xss::filter($value, array());

            // fqs are weird; we can't encode ampersands
            if ($name == 'edan_fq') {
              $newvalue = str_replace('&amp;', '&', $newvalue);
            }

            $newvalue = str_replace('&#39;', '\'', $newvalue);
            $newvalue = str_replace('&#62;', '', $newvalue);
            $newvalue = str_replace('&#60;', '', $newvalue);
            $newvalue = str_replace('--', '', $newvalue);
            $newvalue = urldecode($newvalue);
            $arr[$newvalue] = $newvalue;
          }

        }
        return $arr;
      }
      else {
        // 20160707 removed FILTER_FLAG_ENCODE_HIGH because it breaks diacritical characters.
        //$newvalue = filter_input(INPUT_GET, $urlParam, FILTER_SANITIZE_SPECIAL_CHARS); //, FILTER_FLAG_ENCODE_HIGH);
        $newvalue = str_replace('&#39;', '\'', $urlParam);
        $newvalue = str_replace('&#62;', '', $newvalue);
        $newvalue = str_replace('&#60;', '', $newvalue);
        $newvalue = str_replace('--', '', $newvalue);
        $newvalue = str_replace('%27s', '', $newvalue);
        $newvalue = str_replace('&#39;s', '', $newvalue);
        $newvalue = urldecode($newvalue);
        return Xss::filter($newvalue, array());
      }
    }
    else {
      return $default;
    }
  }
}
