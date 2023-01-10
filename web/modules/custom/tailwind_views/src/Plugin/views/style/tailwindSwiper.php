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
 *   id = "tailwindSwiper",
 *   title = @Translation("Tailwind Slider"),
 *   help = @Translation("Create a slider using Swiper JS"),
 *   theme = "views_view_tailwind_slider",
 *   display_types = { "normal" }
 * )
 */
class tailwindSwiper extends StylePluginBase {

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
    $options['items_per_slide'] = ['default' => '1'];
    $options['show_controls'] = ['default' => TRUE ];
    $options['show_pagination'] = ['default' => TRUE ];


    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);


    // Whether month links should be nested inside year links.
    $options = array(
        '1' => '1',
        '2' => '2',
        '3' => '3',
        '4' => '4',
        '5' => '5',
 
    );

    $bool = array(
      TRUE => 'yes',
      FALSE => 'no',
    );

    $form['items_per_slide'] = array(
      '#type' => 'radios',
      '#title' => t('number of items per slide'),
      '#options' => $options,
      '#default_value' => (isset($this->options['items_per_slide'])) ? $this->options['items_per_slide'] : '1',
      '#description' => t('Number of Items per Slide'),
    );

    // Show controls.
    $form['show_controls'] = array(
      '#type' => 'checkbox',
      '#options' => $bool,
      '#title' => t('Show controls'),
      '#default_value' => (isset($this->options['show_controls'])) ? $this->options['show_controls'] : TRUE,
      '#description' => t('Show left right controls'),
    );

    $form['show_pagination'] = array(
      '#type' => 'checkbox',
      '#options' => $bool,
      '#title' => t('Show Pagination'),
      '#default_value' => (isset($this->options['show_pagination'])) ? $this->options['show_pagination'] : TRUE,
      '#description' => t('Show Pagination'),
    );
  }

}