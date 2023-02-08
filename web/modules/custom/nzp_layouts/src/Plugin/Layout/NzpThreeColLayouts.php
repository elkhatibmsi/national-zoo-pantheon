<?php

namespace Drupal\nzp_layouts\Plugin\Layout;

/**
 * Configurable twocol layout plugin class.
 *
 * @internal
 *   Plugin classes are internal.
 */
class NzpThreeColLayouts extends NzpWrapperBase {

  /**
   * {@inheritdoc}
   */
  protected function getContainerClasses() {
    return [
      'container mx-auto' => 'container',
      'container-fuid' => 'full-width',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getRegionClasses() {
    {
      return [
        'col-span-1' => 'col-span-1',
        'col-span-2' =>  'col-span-2',
        'col-span-3' =>  'col-span-3',
        'col-span-4' =>  'col-span-4',
        'col-span-5' =>  'col-span-5',
        'col-span-6' =>  'col-span-6',
        'col-span-7' =>  'col-span-7',
        'col-span-8' =>  'col-span-8',
        'col-span-9' =>  'col-span-9',
        'col-span-10' =>  'col-span-10',
        'col-span-11' =>  'col-span-11',
        'col-span-12' =>  'col-span-12',
  
       
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getHtmlElements() {
    return [
      'div' => 'Div',
      'section' => 'Section',
      'aside' => 'Aside',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getRegionBackgroundColor() {
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
