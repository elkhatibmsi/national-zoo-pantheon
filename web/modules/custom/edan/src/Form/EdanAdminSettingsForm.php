<?php

namespace Drupal\edan\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Xss;

/**
 * Configure EDAN API settings for this site.
 */
class EdanAdminSettingsForm extends ConfigFormBase {
  const EDAN_CONFIG_NAME = "edan.settings";
  const EDAN_SETTINGS_URL = "api.url";
  const EDAN_SETTINGS_APP_ID = "api.app_id";
  const EDAN_SETTINGS_AUTH_TOKEN = "api.auth_token";
  const EDAN_SETTINGS_VERSION = "api.version";

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edan_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['edan.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('edan.settings');
    $form['api'] = [
      '#type' => 'details',
      '#title' => $this->t('EDAN API settings'),
      '#open' => TRUE,
    ];

    $form['api']['edan_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $config->get(self::EDAN_SETTINGS_URL) ?? 'https://edan.si.edu/',
      '#placeholder' => 'https://',
      '#maxlength' => 255,
      '#size' => 60,
      '#required' => TRUE,
    ];

    $form['api']['edan_app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App ID'),
      '#default_value' => $config->get(self::EDAN_SETTINGS_APP_ID),
      '#maxlength' => 60,
      '#size' => 30,
      '#required' => TRUE,
    ];

    $form['api']['edan_auth_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Authentication token'),
      '#default_value' => $config->get(self::EDAN_SETTINGS_AUTH_TOKEN),
      '#maxlength' => 255,
      '#size' => 60,
      '#required' => TRUE,
    ];

    $form['api']['edan_version'] = [
      '#type' => 'select',
      '#title' => $this->t('API version'),
      '#default_value' => $config->get(self::EDAN_SETTINGS_VERSION) ?? 'v2.0',
      '#options' => [
        'v1.1' => 'EDAN v1.1',
        'v2.0' => 'EDAN v2.0',
      ],
      '#required' => TRUE,
    ];
    $form['api']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug'),
      '#default_value' => $config->get('api.debug')
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(self::EDAN_CONFIG_NAME);

    $config->set(self::EDAN_SETTINGS_URL,
      Xss::filter($form_state->getValue('edan_url')));
    $config->set(self::EDAN_SETTINGS_APP_ID,
      Xss::filter($form_state->getValue('edan_app_id')));
    $config->set(self::EDAN_SETTINGS_AUTH_TOKEN,
      Xss::filter($form_state->getValue('edan_auth_token')));
    $config->set(self::EDAN_SETTINGS_VERSION,
      Xss::filter($form_state->getValue('edan_version')));
    $config->set('api.debug', $form_state->getValue('debug'));
    $config->save();

    parent::submitForm($form, $form_state);
  }
}

