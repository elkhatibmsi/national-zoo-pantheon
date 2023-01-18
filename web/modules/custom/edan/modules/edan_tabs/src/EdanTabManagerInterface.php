<?php

namespace Drupal\edan_tabs;

use Drupal\edan_tabs\Entity\EdanTab;

/**
 * Interface EdanTabManagerInterface.
 */
interface EdanTabManagerInterface {

  /**
   * @param \Drupal\edan_tabs\Entity\EdanTab $edanTab
   * @params array $searchTotals
   *  An associative array of search totals
   * @params \Drupal\edan_search\Entity\EdanSearch
   * @return renderable array of tabs
   */
  public function renderTabs(EdanTab $edanTab, $edan_search = NULL);

  /**
   * @param \Drupal\edan_tabs\Entity\EdanTab $edanTab
   * @param \Drupal\edan_search\Entity\EdanSearch $edanSearch entity
   * modules can use the hook_edan_tabs_alter to change tabs or result totals
   * @return renderable array for edan_tabs_page
   */
  public function renderPage(EdanTab $edanTab, $edanSearch = NULL);
}
