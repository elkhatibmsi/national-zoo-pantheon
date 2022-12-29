<?php

namespace Drupal\nzp_layouts\Plugin\Layout;

use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Credit goes to npinos at https://github.com/npinos/drupal8-layouts.
 */


/**
 * Base class  for configuring Layout section properties.
 *
 * @internal
 *   Plugin classes are internal.
 */
abstract class NzpWrapperBase extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {

    // Each of the variables below corresponds to custom arrays for each layout.
    // These values are defined in the layout plugins for each layout (example: nzpTwoColLayouts.php).
    $region_classes = array_keys($this->getRegionClasses());
    $container_classes = array_keys($this->getContainerClasses());
    $html_elements = array_keys($this->getHtmlElements());
    $background_color = array_keys($this->getBackgroundColor());
    $region_bg_color = array_keys($this->getRegionBackgroundColor());


    return [
      'custom_classes' => '',
      'region_classes' => array_shift($region_classes),
      'container_classes' => array_shift($container_classes),
      'html_container_elements' => array_shift($html_elements),
      'html_region_elements' => array_shift($html_elements),
      'background_color' => array_shift($background_color),
      'region_background_color' => array_shift($region_bg_color),


    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $configuration = $this->getConfiguration();
    $regions = $this->getPluginDefinition()->getRegions();

    $form['custom_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wrapper Class'),
      '#description' => $this->t('Enter a custom class name'),
      '#default_value' => !empty($configuration['custom_classes']) ? $configuration['custom_classes'] : '',
    ];


    if(!empty($this->getHtmlElements())) {
    $form['html_container_elements'] = [
      '#group' => 'HTML Elements',
      '#type' => 'select',
      '#options' => $this->getHtmlElements(),
      '#title' => $this->t('Wrapper Class'),
      '#description' => $this->t('Select a HTML Tag'),
      '#default_value' => !empty($configuration['html_container_elements']) ? $configuration['html_container_elements'] : 'div',
    ];
  }

if(!empty($this->getContainerClasses())) {
    $form['container_classes'] = [
      '#type' => 'select',
      '#options' => $this->getContainerClasses(),
      '#title' => $this->t('Wrapper Class'),
      '#description' => $this->t('Select a Wrapper Class'),
      '#default_value' => !empty($configuration['container_classes']) ? $configuration['container_classes'] : 'container',
    ];
  }

  
if(!empty($this->getBackgroundColor())) {
  $form['background_color'] = [
    '#type' => 'select',
    '#options' => $this->getBackgroundColor(),
    '#title' => $this->t('Background Color'),
    '#description' => $this->t('Select a background color. Leave empty for white'),
    '#default_value' => !empty($configuration['background_color']) ? $configuration['background_color'] : 'white',
  ];
}

  
  if(count($regions) > 1) {
    $form['html_region_elements'] = [
      '#type' => 'details',
      '#title' => $this->t('HTML Element Options'),
      '#tree' => TRUE,
    ];

    foreach ($regions as $region_name => $region_definition) {
      if(!empty($this->getRegionBackgroundColor())) {
        $form['region_background_color'][$region_name] = [
          '#type' => 'select',
          '#options' => $this->getRegionBackgroundColor(),
          '#title' => $this->t('Background Color'),
          '#description' => $this->t('Select a background color for @region' , ['@region' => $region_definition['label']]),
          '#default_value' => !empty($configuration['region_background_color'][$region_name]) ? $configuration['region_background_color'][$region_name] : 'bg-none',
        ];
      }
      $form['region_classes'][$region_name] = [
        '#type' => 'select',
        '#options' => $this->getRegionClasses(),
        '#title' => $this->t('Class for @region', ['@region' => $region_definition['label']]),
        '#default_value' => !empty($configuration['region_classes'][$region_name]) ? $configuration['region_classes'][$region_name] : 'gr',
      ];

      $form['html_region_elements'][$region_name] = [
        '#type' => 'select',
        '#options' => $this->getHtmlElements(),
        '#title' => $this->t('HTML Tag for @region', ['@region' => $region_definition['label']]),
        '#default_value' => !empty($configuration['html_region_elements'][$region_name]) ? $configuration['html_region_elements'][$region_name] : 'div',
      ];
    }
  }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {

    $this->configuration['container_classes'] = $form_state->getValue('container_classes');
    $this->configuration['getHtmlElements'] = $form_state->getValue(['html_container_elements']);
    $this->configuration['html_region_elements'] = $form_state->getValue('html_region_elements');
    $this->configuration['region_classes'] = $form_state->getValue('region_classes');
    $this->configuration['custom_classes'] = $form_state->getValue('custom_class');
    $this->configuration['background_color'] = $form_state->getValue('background_color');
    $this->configuration['region_background_color'] = $form_state->getValue('region_background_color');

  }

  /**
   *
   */
  abstract protected function getContainerClasses();

  /**
   *
   */
  abstract protected function getRegionClasses();

  /**
   *
   */
  abstract protected function getHtmlElements();

}
