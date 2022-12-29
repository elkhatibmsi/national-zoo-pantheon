<?php

namespace Drupal\nzp_layouts\Plugin\Layout;

use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Credit goes to npinos at https://github.com/npinos/drupal8-layouts.
 */
class NzpButton extends LayoutDefault implements PluginFormInterface {

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
    return parent::defaultConfiguration() + [
      'link_url' => '',
      'link_text' => '',
      'link_target' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $regions = $this->getPluginDefinition()->getRegions();

      $form['link_url'] = [
        '#type' => 'url',
        '#title' => $this->t('Enter a link url.'),
        '#default_value' => !empty($configuration['link_url']) ? $configuration['link_url'] : '',
      ];

      $form['link_text'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Enter a link label.'),
        '#default_value' => !empty($configuration['link_text']) ? $configuration['link_text'] : '',
      ];

      $form['link_target'] = [
        '#type' => 'select',
        '#options' => array(
            '0' => 'Same Window',
            '1' => 'New Window',
        ),
        '#title' => $this->t('Select Link Target'),
        '#default_value' => !empty($configuration['link_target']) ? $configuration['link_target'] : '',
      ];

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
    $this->configuration['link_text'] = $form_state->getValue('link_text');
    $this->configuration['link_url'] = $form_state->getValue('link_url');
    $this->configuration['link_target'] = $form_state->getValue('link_target');

  }

}
