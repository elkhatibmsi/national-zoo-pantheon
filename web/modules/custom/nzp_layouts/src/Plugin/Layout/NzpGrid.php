<?php

namespace Drupal\nzp_layouts\Plugin\Layout;

use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Credit goes to npinos at https://github.com/npinos/drupal8-layouts.
 */
class NzpGrid extends NzpWrapperBase {

  /**
   * {@inheritdoc}
   */
  protected function getContainerClasses() {
    return [

      'layout-one-col' => '1 Column',
      'layout-two-cols' => '2 Columns',
      'layout-three-cols' => '3 Columns',
      'layout-four-cols' => '4 Columns',

    ];
  }

  /**
   *
   */
  protected function getRegionClasses() {
    return [];
  }

  /**
   *
   */
  protected function getHtmlElements() {
    return [];
  }

    /**
   *
   */
  protected function getBackgroundColor() {
    return [];
  }
    /**
   *
   */
  protected function getRegionBackgroundColor() {
    return [];
  }


}
