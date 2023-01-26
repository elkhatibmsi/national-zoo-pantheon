<?php

namespace Drupal\edan;

use Drupal\Component\Serialization\Json;

/**
 * Class EdanIDS.
 */
class EdanIDS {

  /**
   * Get image metadata base on idsId.
   *
   * @param int $amount
   *
   * @return array
   */
  public static function fetchMedia($edanId = NULL, $idsId = NULL) {
    //$response = $this->idsClient->get('ids/iiif/'. $idsId .'/info.json');
    $response = NULL;
    if ($edanId) {
      $response = file_get_contents('https://ids.si.edu/ids/manifest/'. $edanId);
    }
    elseif ($idsId) {
      $response = file_get_contents('https://ids.si.edu/ids/iiif/'. $idsId .'/info.json');
    }

    return $response ? Json::decode($response) : NULL;
  }
}
