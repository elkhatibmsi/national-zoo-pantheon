<?php

namespace Drupal\edan_record;

/**
 * Interface EdanRecordMediaInterface.
 */
interface EdanRecordMediaInterface {

  /**
   * @param $record
   * @return array
   *   The renderable array of theme_blazy(), or view builder, else empty array.
   */
  public function buildMedia($record);

}
