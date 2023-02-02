<?php

namespace Drupal\nzp_layouts\Plugin\Layout;

use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Credit goes to npinos at https://github.com/npinos/drupal8-layouts.
 * NZP Banner custom form elements
 */
class NzpBanner extends NzpWrapperBase {

  /**
   * {@inheritdoc}
   */
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


  /**
   * {@inheritdoc}
   */
  protected function getRegionClasses() {
    return [];
  }

    /**
   * {@inheritdoc}
   */
  protected function getRegionBackgroundColor() {
    return [];
  }

    /**
   * {@inheritdoc}
   */
  protected function getHtmlElements() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getContainerClasses() {
    return [
      'container' => 'container',
      'container-fuid' => 'full-width',
    ];
  }

}
