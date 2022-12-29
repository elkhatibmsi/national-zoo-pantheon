<?php

namespace Drupal\tailwind_views\Plugin\views\style;

use Drupal\core\form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render a list of years and months
 * in reverse chronological order linked to content.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "tailwindGrid",
 *   title = @Translation("Tailwind Views"),
 *   help = @Translation("Apply Tailwind to your Grid"),
 *   theme = "views_view_tailwind_grid",
 *   display_types = { "normal" }
 * )
 */
class tailwindGrid extends StylePluginBase {

  /**
   * {@inheritdoc}
   */

  
   /**
   * Specifies if the plugin uses row plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;


  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['column_count'] = ['default' => 'grid-cols-3'];
    $options['row_classes'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);


    // Whether month links should be nested inside year links.
    $options = array(
        'grid-span-12' => '1',
        'grid-span-6' => '2',
        'grid-span-4' => '3',
        'grid-span-3' => '4',
        'grid-span-2' => '6',
 
    );

    $form['column_count'] = array(
      '#type' => 'radios',
      '#title' => t('number of columns'),
      '#options' => $options,
      '#default_value' => (isset($this->options['column_count'])) ? $this->options['column_count'] : 'grid-col-4',
      '#description' => t('Number of Columns'),
    );

    // Extra CSS classes.
    $form['row_classes'] = array(
      '#type' => 'textfield',
      '#title' => t('Row classes'),
      '#default_value' => (isset($this->options['row_classes'])) ? $this->options['row_classes'] : '',
      '#description' => t('Additional classes for each row'),
    );
  }

}