<?php

namespace Drupal\edan_tabs\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\edan_tabs\EdanTabManagerInterface;
use Drupal\edan_tabs\Form\EdanTabForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'EDAN Search form' block.
 *
 * @Block(
 *   id = "edan_tabs_form_block",
 *   admin_label = @Translation("EDAN Search Tabs form"),
 *   category = @Translation("EDAN")
 * )
 */
class EdanTabsBlockForm extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The search page repository.
   *
   * @var \Drupal\edan_tabs\EdanSearchManagerInterface
   */
  protected $edanTabManager;

  /**
   * Constructs a new SearchLocalTask.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\edan_tabs\EdanSearchManagerInterface
   *   The search page repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder, EdanTabManagerInterface $edanTabManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
    $this->edanTabManager = $edanTabManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('form_builder'),
      $container->get('edan_tabs.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'view edan search form entity');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $edanTab = $this->edanTabManager->entityLoad($this->configuration['edan_tab'], 'edan_tab');

    $form_obj = \Drupal::service('class_resolver')
      ->getInstanceFromDefinition('Drupal\edan_tabs\Form\EdanTabForm');
    $form_obj->setInfo($edanTab, TRUE);
    return $this->formBuilder->getForm($form_obj, $this->configuration['display_extra']);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'edan_tab' => '',
      'display_extra' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // The configuration for this block is which search page to connect the
    // form to. Options are all configured/active search pages.
    $options = [];
    $tabs = $this->edanTabManager->getActiveEntities('edan_tab');
    foreach ($tabs as $entity_id => $entity) {
      $options[$entity_id] = $entity->label();
    }

    $form['edan_tab'] = [
      '#type' => 'select',
      '#title' => $this->t('EDAN Search Tab'),
      '#description' => $this->t('The EDAN search tab that the form should be directed to.'),
      '#default_value' => $this->configuration['edan_tab'],
      '#options' => $options,
      '#required' => TRUE
    ];

    $form['display_extra'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display Extra'),
      '#default_value' => $this->configuration['display_extra'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['edan_tab'] = $form_state->getValue('edan_tab');
    $this->configuration['display_extra'] = $form_state->getValue('display_extra');
  }

}
