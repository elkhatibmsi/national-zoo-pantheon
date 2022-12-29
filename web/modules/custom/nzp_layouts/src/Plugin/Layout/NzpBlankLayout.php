<?php

namespace Drupal\nzp_layouts\Plugin\Layout;

use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Uuid;


/**
 * Base class  for configuring Layout section properties.
 *
 * @internal
 *   Plugin classes are internal.
 */


class NzpBlankLayout extends LayoutDefault implements PluginFormInterface
{
  public function __construct(array $configuration, $plugin_id, $plugin_definition)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }



  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration()
  {
    return parent::defaultConfiguration() + [
      'custom_class' => ''
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'custom_class';
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state)
  {
    $configuration = $this->getConfiguration();


    $form['custom_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wrapper Class'),
      '#description' => $this->t('Enter a custom class name'),
      '#default_value' => !empty($configuration['custom_class']) ? $configuration['custom_class'] : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state)
  {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
  {
    $this->configuration['custom_class'] = $form_state->getValue('custom_class');
  }
}
