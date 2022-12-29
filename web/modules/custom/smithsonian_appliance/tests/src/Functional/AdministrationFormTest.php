<?php

namespace Drupal\Tests\smithsonian_appliance\Functional;

/**
 * Tests Smithsonian appliance administration form.
 *
 * @group smithsonian_appliance
 */
class AdministrationFormTest extends SmithsonianApplianceFunctionalTestBase {

  /**
   * Tests admin form.
   */
  public function testAdminForm() {
    $this->drupalGet('/admin/config/search/smithsonian_appliance/settings');
    $assert = $this->assertSession();
    // Check non-admins cannot access page.
    $assert->statusCodeEquals(403);

    // Now login.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/search/smithsonian_appliance/settings');
    $assert->statusCodeEquals(200);
    $this->submitForm([
      'hostname' => 'http://www.mygsa.net',
      'collection' => 'default_collection',
      'frontend' => 'default_frontend',
      'timeout' => 10,
      'autofilter' => '1',
      'query_inspection' => FALSE,
      'search_title' => $this->randomString(),
      'results_per_page' => 16,
    ], 'Save configuration');

    $config = $this->container->get('config.factory')->get('smithsonian_appliance.settings');
    $this->assertEquals('http://www.mygsa.net', $config->get('connection_info.hostname'));
    $this->assertEquals(16, $config->get('display_settings.results_per_page'));
  }

}
