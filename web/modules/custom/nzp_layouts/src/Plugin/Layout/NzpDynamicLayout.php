<?php

namespace Drupal\nzp_layouts\Plugin\Layout;

use Drupal\Core\Layout\LayoutDefault;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Layout\LayoutPluginManager;
use Drupal\Core\Layout\DynamicRegionsProviderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dynamic layout.
 */
class NzpDynamicLayout extends LayoutDefault implements PluginFormInterface, ContainerFactoryPluginInterface, DynamicRegionsProviderInterface {

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'subsections' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $config = $this->getConfiguration();

    $subsections = $form_state->get('subsections') ?? $config['subsections'];
    $form_state->set('subsections', $subsections);

    $form['subsections'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Subsections'),
      '#prefix' => '<div id="subsections-fieldset-wrapper">',
      '#suffix' => '</div>',
      '#tree'=> TRUE,
    ];

    $form['subsections']['default'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#default_value' => 'default',
    ];

    foreach ($subsections as $uuid => $data) {

      $form['subsections'][$uuid] = [
        '#type' => 'fieldset',
      ];

      $form['subsections'][$uuid]['uuid'] = [
        '#type' => 'textfield',
        '#disabled' => TRUE,
        '#default_value' => $uuid,
      ];

      $form['subsections'][$uuid]['layout'] = [
        '#type' => 'select',
        '#disabled' => TRUE,
        '#options' => $this->getSubsectionOptions(),
        '#default_value' => $data['layout'],
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['add_subsection'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Add subsection')
    ];

    $form['actions']['add_subsection']['layout'] = [
      '#type' => 'select',
      '#title' => $this->t('Sublayout'),
      '#options' => $this->getSubsectionOptions(),
    ];

    $form['actions']['add_subsection']['button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#submit' => [
        [$this, 'addSubsection']
      ],
      '#ajax' => [
        'callback' => [$this, 'updateSubsectionsCallback'],
        'wrapper' => 'subsections-fieldset-wrapper',
      ],
    ];

    return $form;
  }

  /**
   * Submit handler for the "add_subsection" button.
   */
  public function addSubsection(array &$form, FormStateInterface $form_state) {
    $subsections = $form_state->get('subsections');

    $layout = $form_state->getValue(['layout_settings', 'actions', 'add_subsection', 'layout']);

    /** @var UuidInterface $uuid */
    $uuid = \Drupal::service('uuid');

    $subsections[$uuid->generate()] = [
      'layout' => $layout,
    ];

    $form_state->set('subsections', $subsections);

    $form_state->setRebuild();
  }

  /**
   * Callback for subsection related ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the subsections in it.
   */
  public function updateSubsectionsCallback(array &$form, FormStateInterface $form_state) {
    return $form['layout_settings']['subsections'];
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
    $config['subsections'] = $form_state->get('subsections');
    $this->setConfiguration($config);
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);

    $dynamicSubsections = [];
    $config = $this->getConfiguration();
    $subsections = $config['subsections'];

    foreach ($subsections as $uuid => $subsection) {
      $layoutData = $this->getSubsectionLayout($subsection['layout']);

      $dynamicSubsections[$uuid] = [
        'layout' => $layoutData,
        'regions' => [],
      ];

      $subsectionRegions = $layoutData['regions'];
      foreach ($subsectionRegions as $subsectionRegion) {
        $dynamicSubsections[$uuid]['regions'][$this->buildSubsectionRegionId($uuid, $subsectionRegion)] = $subsectionRegion;
      }
    }

    $build['#subsections'] = $dynamicSubsections;

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicRegions() {

    $regions = [];
    $config = $this->getConfiguration();
    $subsections = $config['subsections'];

    foreach ($subsections as $uuid => $data) {
      $layoutData = $this->getSubsectionLayout($data['layout']);
      $subsectionRegions = $layoutData['regions'];

      foreach ($subsectionRegions as $subsectionRegion) {
        $regions[$this->buildSubsectionRegionId($uuid, $subsectionRegion)] = [
          'label' => $layoutData['label'] . ': ' . $subsectionRegion,
        ];
      }
    }

    return $regions;
  }

  /**
   * @return array
   */
  private function getSubsectionOptions(): array {
    $types = $this->LayoutPluginManager->getLayoutOptions();
    $options = [];
    foreach ($types as $id => $data) {
      $options[$id] = $data['label'];
    }

    return $options;
  }

  /**
   * @param string $layout
   *
   * @return array
   */
  private function getSubsectionLayout($layout): array {
    $layouts = $this->LayoutPluginManager->getLayoutOptions();
    return $layouts[$layout];
  }

  /**
   * @return array
   */
  
//   private function getSubsectionLayouts(): array {
//     $layout_options = [];
//     foreach ($this
//       ->getGroupedDefinitions() as $category => $layout_definitions) {
//       foreach ($layout_definitions as $name => $layout_definition) {
//         $layout_options[$category][$name] = $layout_definition
//           ->getLabel();
//       }
//     }
//     return $layout_options;
//   }


  private function buildSubsectionRegionId($uuid, $name) {
    return "${uuid}_$name";
  }
}
