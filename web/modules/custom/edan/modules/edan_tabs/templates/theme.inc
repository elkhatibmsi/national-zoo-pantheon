<?php
function template_preprocess_edan_tabs_page(&$vars) {
  $vars['attributes'] = [
    'class' => [
      'edan-tabs',
      'tabs-'. count($vars['tabs']),
      $vars['content']['#theme'] === 'edan_tabs_landing' ? 'tabs-landing' : ''
    ]
  ];
  $searchTerm = $vars['searchTerm'] ? ' for '. $vars['searchTerm'] : '';
  $vars['summary'] =  t('@count results@term', ['@count' => number_format($vars['total']), '@term' => $searchTerm]);
  $vars['tab_page_title'] = $vars['edanTab']->label();
}


function template_preprocess_edan_tabs_landing(&$vars) {
  $vars['attributes'] = [
    'class' => count($vars['columns']) > 1 ? ['grid-wrapper'] : ['span-width']
  ];
}
