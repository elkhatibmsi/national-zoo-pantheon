<?php

namespace Drupal\nzp_layouts\Plugin\Layout;

use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Credit goes to npinos at https://github.com/npinos/drupal8-layouts.
 */
class NzpOneColLayouts extends NzpWrapperBase {

  /**
   * {@inheritdoc}
   */
  protected function getContainerClasses() {
    return [
      'container' => 'container',
      'container-fuid' => 'full-width',
    ];
  }

  /**
   *
   */
  protected function getRegionClasses() {
    return [];
  }

  protected function getRegionBackgroundColor() {
    return [];
  }

  /**
   *
   */
  protected function getHtmlElements() {
    return [
      'div' => 'Div',
      'section' => 'Section',
      'aside' => 'Aside',
    ];
  }

  protected function getBackgroundColor() {
    return [
      'bg-none'=> 'None', 
      'bg-white'=> 'White',     
      'knockout bg-oxford-blue' => 'Oxford Blue',
      'knockout bg-sap-green' => 'Sap Green',
      'knockout bg-blue-sapphire' => 'Blue Saphire', 
      'p-4 bg-sizzling-red' => 'Sizzling Red', 
      'p-4 bg-cyber-yellow' => 'Cyber Yellow',
    ];
  }


}
