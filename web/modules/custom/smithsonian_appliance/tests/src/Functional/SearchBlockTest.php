<?php

namespace Drupal\Tests\smithsonian_appliance\Functional;

use Drupal\smithsonian_appliance\Routing\SearchViewRoute;
use Drupal\Core\Url;

/**
 * Test search block.
 *
 * @group smithsonian_appliance
 */
class SearchBlockTest extends SmithsonianApplianceFunctionalTestBase {

  /**
   * Test search block form.
   */
  public function testSearchBlock() {
    $this->placeBlock('smithsonian_appliance_search');

    // Test redirect.
    // Go to the front page and submit the search form.
    $this->drupalGet(Url::fromRoute('<front>'));
    $terms = ['search_keys' => 'ponies'];
    $this->submitForm($terms, t('Search'));

    $this->assertEquals(Url::fromRoute(SearchViewRoute::ROUTE_NAME, [
      'search_query' => 'ponies',
    ])->setAbsolute()->toString(), $this->getSession()->getCurrentUrl());
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
  }

}
