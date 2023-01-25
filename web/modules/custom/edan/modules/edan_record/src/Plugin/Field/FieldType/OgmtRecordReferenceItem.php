<?php

namespace Drupal\edan_record\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'ogmt_record_reference' entity field type.
 *
 * @FieldType(
 *   id = "ogmt_record_reference",
 *   label = @Translation("OGMT Record (deprecated - do not use)"),
 *   description = @Translation("Embedded an OGMT Record in an entity that supports fields (e.g. node, taxonomy and user)."),
 *   category = @Translation("EDAN"),
 *   default_widget = "ogmt_record_reference_default_widget",
 *   default_formatter = "ogmt_record_reference_default_formatter"
 * )
 */
class OgmtRecordReferenceItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    // not adding customizable settings to field definition
    // may add it in future

    return parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['type'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Value type'));
      //->setRequired(TRUE);

    $properties['og'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Object Group value'));
      //->setRequired(TRUE);

    $properties['page'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Object Group Page value'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'type' => [
          'description' => 'The type of value in the field (URL or ID).',
          'type' => 'int',
          'size' => 'tiny',
          //'default' => 0, // (0 = url, 1 = id)
        ],
        'og' => [
          'description' => 'Object Group URL or ID.',
          'type' => 'varchar',
          'length' => 2048,
        ],
        'page' => [
          'description' => 'Object Group Page URL or ID.',
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
  public function getConstraints() {
    // not adding any field specific constraint (may add it in future)

    return parent::getConstraints();
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['type'] = 0;
    $values['og'] = 'adding-machines';
    $values['page'] = 'full-keyboard-hill-to-felt-tarrant';
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    // not adding any customizable settings to the field definition
    // may add it in future

    return parent::storageSettingsForm($form, $form_state, $has_data);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('og')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'og';
  }

  /**
   * {@inheritdoc}
   */
  public function getRecord() {
    //return Url::fromUri($this->uri, (array) $this->options);
    //@TODO return the actual array
//    dpm($this->type);
//    dpm($this->og);
//    dpm($this->page);

    return [];
  }


}

