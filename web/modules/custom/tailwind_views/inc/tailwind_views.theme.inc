<?php

/**
 * @file
 * Theme for tailwind views.
 */
function template_preprocess_view_tailwind_views(&$variables) {
  // View options set by user.
  $options = $variables['view']->style_plugin->options;

  // Update options for twig.
  $variables['options'] = $options;
  $variables['test'] = $options;
}