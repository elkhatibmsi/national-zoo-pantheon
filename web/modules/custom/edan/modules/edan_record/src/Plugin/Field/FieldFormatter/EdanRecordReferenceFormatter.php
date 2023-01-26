<?php

namespace Drupal\edan_record\Plugin\Field\FieldFormatter;

use Drupal\edan_record\EdanRecordManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'edan_record_reference_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "edan_record_reference_default_formatter",
 *   label = @Translation("Edan Record Embedded"),
 *   field_types = {
 *     "edan_record_reference"
 *   }
 * )
 */
class EdanRecordReferenceFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The path validator service.
   *
   * @var \Drupal\edan_record\EdanRecordManagerInterface
   */
  protected $edanRecordManager;

  /**
   * Constructs a new EdanRecordReferenceFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\edan_record\EdanRecordManagerInterface
   *   The edan manager service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EdanRecordManagerInterface $edanRecordManager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->edanRecordManager = $edanRecordManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('edan_record.manager')
    );
  }
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        // Implement default settings.
        'view_mode' => 'full'
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $elements['view_mode'] = [
      '#type' => 'select',
      '#title' => t('View mode'),
      '#default_value' => $this->getSetting('view_mode'),
      '#options' => [
        'full' => $this->t('Default'),
        'teaser' => $this->t('Teaser'),
        'list' => $this->t('Object List')
      ],
      '#description' => $this->t('Choose the view mode for the edan record.  Object list only applies to objectgroups and ead_components'),
      '#required' => TRUE
    ];
    return $elements
      + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t(
      'Rendered view mode: @mode',
      ['@mode' => $this->getSetting('view_mode')]
    );


    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $pageId = \Drupal::request()->query->get('pageId');
    $objectGroupId = \Drupal::request()->query->get('objectGroupId');
    $objectGroupIdPage = str_replace('objectgroup:', 'page:', $objectGroupId);
    foreach ($items as $delta => $item) {
      $view_mode = $this->getSetting('view_mode');
      $teaser = $view_mode === 'teaser';
      $path_key = strstr($item->value, ':') ? '[edan:url]' : '[edan:id]';
      $edanId = $type = NULL;
      $edanSettings = [];
      if ($pageId && $objectGroupId && ($item->value === $objectGroupId || strpos($item->value, $objectGroupIdPage) !== FALSE)) {
        $entity = $this->edanRecordManager->entityLoad('objectgroup', 'edan_record');
        $edanSettings = $entity ? $entity->getRecordSettings($teaser) : [];
        $path_key = '[edan:url]';
        $edanId = $pageId;
        $type = NULL;
        //$record = $this->edanRecordManager->getRecord($pageId, '[edan:id]', 'page', $settings);
      }
      else {
        $edanId = $item->value;
        if (strpos($edanId, ':') != FALSE) {
          $type = substr($edanId, 0, strpos($edanId, ':'));
          $entity = $this->edanRecordManager->entityLoad($type, 'edan_record');
          $edanSettings = $entity ? $entity->getRecordSettings($teaser) :  $this->edanRecordManager->getDefault();
        }
      }
      $record = $this->edanRecordManager->getRecord($edanId, $path_key, $type, $edanSettings);
      if($record) {
        if (empty($edanSettings)) {
          $entity = $this->edanRecordManager->entityLoad($record['type'], 'edan_record');
          $edanSettings = $entity ? $entity->getRecordSettings($teaser) :  $this->edanRecordManager->getDefault();
          if ($record['type'] === 'objectgroup') {
            $edanSettings['fields'] = ['defaultPage:content'];
            $edanSettings['summary_fields'] = ['description'];
          }
          $this->edanRecordManager->processData($record, $edanSettings);
        }

//      $edanRecord = $this->edanRecordManager->entityLoad($type, 'edan_record');
//      //$settings = $edanRecord->getRecordSettings($teaser);
//      $settings = $this->edanRecordManager->getDefault();
//    	if ($type === 'objectgroup' && $pageId) {
//        $record = $this->edanRecordManager->getRecord($pageId,'[edan:id]', NULL, $settings);
//      }
//    	else {
//        $record = $this->edanRecordManager->getRecord($item->value,'[edan:url]', NULL, $settings);
//      }
//    	if ($record) {
        $elements[$delta] = $this->edanRecordManager->renderRecord($record, $view_mode, TRUE);
      }
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   * @param \Drupal\edan\Connector\EdanConnectorService $service
   *
   * @return array
   *   Data retrieved from EDAN.
   * @throws \Exception
   */
  protected function viewValue(FieldItemInterface $item, EdanConnectorService $service) {
    return $service->getRecord($item->value);
  }

}

