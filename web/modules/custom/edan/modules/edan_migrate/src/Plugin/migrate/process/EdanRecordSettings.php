<?php

namespace Drupal\edan_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * set limit_to_local and viewer/features.
 *
 * @MigrateProcessPlugin(
 *   id = "edan_record_settings"
 * )
 */
class EdanRecordSettings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($destination_property === 'limit_to_local') {
      return !empty($value);
    }
    else {
      $features = [];
      foreach($value as $key => $value) {
        if ($key === 'navigatorPosition') {
          continue;
        }
        elseif (strlen($value) > 1) {
          $features[$key] = $value;
        }
      }
      return $features;
    }
  }
}
