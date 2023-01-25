<?php

namespace Drupal\edan_record\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;

/**
 * Plugin implementation of the 'edan_record_reference_widget' widget.
 *
 * @FieldWidget(
 *   id = "edan_record_reference_default_widget",
 *   label = @Translation("Autocomplete"),
 *   field_types = {
 *     "edan_record_reference"
 *   }
 * )
 */
class EdanRecordAutocompleteWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'match_limit' => 10,
        'size' => 60,
        'placeholder' => '',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['match_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of results'),
      '#default_value' => $this->getSetting('match_limit'),
      '#min' => 0,
      '#description' => $this->t('The number of suggestions that will be listed. Use <em>0</em> to remove the limit.'),
    ];
    $element['size'] = [
      '#type' => 'number',
      '#title' => t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#min' => 1,
      '#required' => TRUE,
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

    $size = $this->getSetting('match_limit') ?: $this->t('unlimited');
    $summary[] = $this->t('Autocomplete suggestion list size: @size', ['@size' => $size]);
    $summary[] = t('Textfield size: @size', ['@size' => $this->getSetting('size')]);
    $placeholder = $this->getSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = t('Placeholder: @placeholder', ['@placeholder' => $placeholder]);
    }
    else {
      $summary[] = t('No placeholder');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = NULL;
    if (isset($items[$delta]->value)) {
      //	  dump($items[$delta]->value);
      $edanId = $items[$delta]->value;
      $value = $items[$delta]->loadEdanRecord($edanId);
    }
    //	dump($items);
    $element['value'] = $element + [
        '#type' => 'textfield',
        '#default_value' => $value ?? $items[$delta]->value,
        '#size' => $this->getSetting('size'),
        '#placeholder' => $this->getSetting('placeholder'),
        '#maxlength' => 1024,
        '#autocomplete_route_name' => 'edan_record.autocomplete',
        '#autocomplete_route_parameters' => [
          'target_type' => $this->fieldDefinition->getTargetEntityTypeId(),
          'entity_bundle' => $this->fieldDefinition->getTargetBundle(),
          'field_name' => $this->fieldDefinition->getName(),
          'match_limit' => $this->getSetting('match_limit')
        ],
        '#element_validate' => [
          [
            get_called_class(),
            'validateEdanAutocomplete'
          ]
        ],
        '#description' => $this->t('Add (content type) to the limit the search by edan content type.')
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
      if (!$edan_id) {
        $form_state->setError($element, t('No valid edan_id.'));
      }
      elseif (strstr($edan_id, ':') !== FALSE) {
        list($type, $id) = explode(':', $edan_id);
        if ($type) {
          $results = \Drupal::service('edan_record.manager')
            ->getRecord($edan_id, '[edan:url]', $type);
          $results = $results ?? \Drupal::service('edan_record.manager')
              ->getRecord($edan_id, '[edan:url]', $type, NULL, NULL, TRUE);
        }
        else {
          $results = [];
        }
        if (!$results) {
          $form_state->setError($element, t('An invalid edan_id given.'));
        }
        else {
          $value = $edan_id;
        }
      }
      else {
        $form_state->setError($element, t('Please enter a valid edan_id.'));
      }
    }
    $form_state->setValueForElement($element, $value);
  }
}

