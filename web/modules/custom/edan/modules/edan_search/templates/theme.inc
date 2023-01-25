<?php
/**
 * Created by PhpStorm.
 * User: phamh
 * Date: 11/13/20
 * Time: 8:22 PM
 */

function template_preprocess_edan_search(&$vars) {
	$element = $vars['element'];
  $edanSearch = $element['#edan_search'];
  $render_settings = $edanSearch->getRenderSettings();
  $vars['search_name'] = $edanSearch->id();
  $vars['search_title'] = ($edanSearch->id() !== 'ogmt_results') ? $edanSearch->label() : '';
  if ($edanSearch->getSettings('fields', 'mini_fields')) {
    $vars['#attached']['library'][] = 'edan/plugin';
  }
  $attributes = [
    'class' => ['edan-search','edan-search-'. $edanSearch->id(), 'view--'. $render_settings['display_mode']]
  ];
  $rowCount = number_format($element['#record_count']);

  $results_count_message = $render_settings['results_count_message'] ?? FALSE;
  $description = $edanSearch->get('summary');
  $vars['description'] = $description ? [
    '#type' => 'processed_text',
    '#text' => $description,
    '#format' => 'edan_html',
  ]: '';
  if($element['#executed'] && $results_count_message) {
	  $vars['message'] = $element['#record_count'] > 0 ? (strlen($element['#query_string']) > 1 ? t('@count result(s) for %text', ['@count' => $rowCount, '%text' => $element['#query_string']]) :  t('@count result(s)', ['@count' => $rowCount])) :
		  t($render_settings['results_empty_message']);
  }

  if (isset($element['#facets']) && !empty($element['#facets'])) {
    $attributes['class'][] = 'with-facets';
	  $vars['facets'] = $element['#facets'];
    $vars['facets_header_text'] = $edanSearch->getSettings('facets', 'facet_header_text') ? t($edanSearch->getSettings('facets', 'facet_header_text')) : t('Facets');

  }
  else {
    $vars['facets'] = [];
  }

	$vars['active_facets'] = $element['#active_facets'] ?? NULL;
  $vars['search_form'] = $element['#search_form'];
  $vars['rows'] = $element['#rows'];
  $vars['display_mode'] = $render_settings['display_mode'];
  $vars['attributes'] = $attributes;
//	if ($element['#record_count'] > $render_settings['results_per_page']) {
//    $pager_manager = \Drupal::service('pager.manager');
//    $pager_manager->createPager($element['#record_count'], $render_settings['results_per_page']);
//		$vars['pager'] = [
//			'#type' => 'pager'
//		];
//	}
//	else {
//		$vars['pager'] = NULL;
//	}

  if ($render_settings['display_mode'] === 'masonry' && \Drupal::service('module_handler')->moduleExists('masonry')) {
    $masonry = \Drupal::service('masonry.service');
    $options = [
      'layoutColumnWidth' => '.grid-sizer',
      'layoutColumnWidthUnit' => '',
      'isLayoutImagesLazyLoaded' => TRUE,
      'imageLazyloadSelector' => 'b-lazy',
      'imageLazyloadedSelector' => 'b-loaded',
      'isItemsPositionInPercent' => TRUE
    ];
    $options += $masonry->getMasonryDefaultOptions();

    $vars['#attached']['library'][] = 'edan_search/masonry';
    $masonry->applyMasonryDisplay($vars, '.edan-search .search-results.masonry', '.edan-search-result', $options);
  }
  $vars['pager'] = $element['#pager'] ?? '';
  if (\Drupal::request()->query->get('edan_dump')) {
    dump(['all' => $vars]);
    dump(['rows' => $vars['rows']]);
  	dump(['facets' => $vars['facets']]);
  	dump(['active_facets' =>$vars['active_facets']]);
  }
 // dump($vars);
}
