<?php

namespace Drupal\edan_record\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Url;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'edan_record_reference' entity field type.
 *
 * @FieldType(
 *   id = "edan_record_reference",
 *   label = @Translation("EDAN Record"),
 *   description = @Translation("Referenced EDAN Record"),
 *   category = @Translation("EDAN"),
 *   default_widget = "edan_record_reference_default_widget",
 *   default_formatter = "edan_record_reference_default_formatter"
 * )
 */
class EdanRecordReferenceItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'value' => [
          'description' => 'The id of the edan record.',
          'type' => 'varchar',
          'length' => 2048,
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
        'content_types' => ['edanmdm' => 'edanmdm'],
        'local_units' => FALSE,
      ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Text value'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['value'] = "edanmdm:nmah_670130";
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public static function fieldSettingsToConfigData(array $settings) {
    $settings['content_types'] = array_filter($settings['content_types']);
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $settings = $this->getSettings();

    $contentTypes = \Drupal::service('edan_record.manager')->entityLoadMultiple('edan_record');
    $contentTypes = array_keys($contentTypes);
    $contentTypes = empty($contentTypes) ? \Drupal::service('edan_record.manager')->getEdanConnector()->getTypes() : array_combine($contentTypes, $contentTypes);
    $element['content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed Record Types'),
      '#options' => $contentTypes,
      '#default_value' => $settings['content_types'],
      '#required' => TRUE,
    ];

    if (\Drupal::service('module_handler')->moduleExists('edan_search')) {
      $element['local_units'] = [
        '#type'           => 'checkbox',                        // Use a textbox
        '#title'          => $this->t('Limit Unit Codes'),                      // Widget label
        '#description'    => $this->t('Limit search results to unit codes set on the <a href="@url">EDAN record settings page</a>.',
          ['@url' => Url::fromRoute('edan_record.settings_form')->toString()]),  // helper text
        '#required' => FALSE,
        '#default_value'  => $settings['local_units'] ?? FALSE,               // Get the value if it's already been set
      ];
    }


    return $element;
  }

  public function loadEdanRecord($edandId) {
    $value = $result = NULL;
    $edanManager = \Drupal::service('edan_record.manager');
    if (strstr($edandId, ':') !== FALSE) {
      list($type, $id) = explode(':', $edandId);
      $result = $edanManager->getRecord($edandId, '[edan:url]', $type);
      $result = $result ?? $edanManager->getRecord($edandId, '[edan:url]', $type, NULL, NULL, TRUE);
    }
    else {
      $result =$edanManager->getRecord($edandId, '[edan:id]');
      $result = $result ?? $edanManager->getRecord($edandId, '[edan:id]', NULL, NULL, NULL, TRUE);

    }
    if ($result) {
      $edanProcess = \Drupal::service('edan_record.process');
      $value = $edanProcess->getTitle($result) .' ('. $result['url'] .')';
    }
    return $value;
  }

}

