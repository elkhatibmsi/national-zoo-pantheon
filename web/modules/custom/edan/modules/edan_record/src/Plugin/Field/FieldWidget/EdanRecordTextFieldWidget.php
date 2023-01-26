<?php

namespace Drupal\edan_record\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'string_textfield' widget.
 *
 * @FieldWidget(
 *   id = "edan_record_text_widget",
 *   label = @Translation("Textfield"),
 *   field_types = {
 *     "edan_record_reference"
 *   }
 * )
 */
class EdanRecordTextFieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'size' => 60,
        'placeholder' => '',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['size'] = [
      '#type' => 'number',
      '#title' => t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    ];
    $element['placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Textfield size: @size', ['@size' => $this->getSetting('size')]);
    $placeholder = $this->getSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = t('Placeholder: @placeholder', ['@placeholder' => $placeholder]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $edanId = isset($items[$delta]->value) ? $items[$delta]->value : NULL;
    $value = $edanId ? $items[$delta]->loadEdanRecord($edanId) : NULL;

    $element['value'] = $element + [
        '#type' => 'textfield',
        '#default_value' => $value,
        '#size' => $this->getSetting('size'),
        '#placeholder' => $this->getSetting('placeholder'),
        '#maxlength' => $this->getFieldSetting('max_length'),
        '#attributes' => ['class' => ['js-text-full', 'text-full']],
        '#element_validate' => [[get_called_class(), 'validateEdanAutocomplete']],
      ];

    return $element;
  }
  /**
   * Form element validation handler for entity_autocomplete elements.
   */
  public static function validateEdanAutocomplete($element, FormStateInterface $form_state) {
    $value = NULL;
    if (!empty($element['#value'])) {
      // GET forms might pass the validated data around on the next request, in
      // which case it will already be in the expected format.
      $edan_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($element['#value']);
      $edan_url = empty($edan_id)? $element['#value'] : $edan_id;

      if (!$edan_url) {
        $form_state->setError($element, t('No valid edan_id.'));
      }
      else {
        $results = \Drupal::service('edan_record.manager')->getRecord($edan_url, '[edan:url]');
        if (!$results) {
          $form_state->setError($element, t('An invalid edan_id given.'));
        }
        else {
          $value = $edan_url;
        }
      }
    }
    $form_state->setValueForElement($element, $value);
  }


}
