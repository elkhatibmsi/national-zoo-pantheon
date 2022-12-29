<?php

namespace Drupal\Tests\smithsonian_appliance\Functional;

use Drupal\simpletest\BlockCreationTrait;
use Drupal\simpletest\UserCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Defines a base class for Smithsonian Appliance tests.
 */
abstract class SmithsonianApplianceFunctionalTestBase extends BrowserTestBase {

  use UserCreationTrait;
  use BlockCreationTrait;

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'smithsonian_appliance',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->createUser([
      'administer smithsonian appliance',
      'access smithsonian appliance content',
    ]);
    // Let anonymous users access search results.
    $role = Role::load(RoleInterface::ANONYMOUS_ID);
    $role->grantPermission('access smithsonian appliance content')->save();
  }

}
