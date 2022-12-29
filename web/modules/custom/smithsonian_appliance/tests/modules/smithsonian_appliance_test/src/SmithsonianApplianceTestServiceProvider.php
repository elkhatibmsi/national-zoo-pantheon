<?php

namespace Drupal\smithsonian_appliance_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Defines a class for modifying the search service in tests.
 */
class SmithsonianApplianceTestServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('smithsonian_appliance.search')->setClass(TestSearch::class);
  }

}
