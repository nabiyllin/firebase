<?php

namespace Drupal\firebase\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements Firebase API configuration.
 *
 * Creates the administrative form with settings used to connect with Firebase
 * Cloud Messassing API.
 *
 * @see \Drupal\Core\Form\ConfigFormBase
 */
class ConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'firebase.configuration';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['firebase.configuration'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('firebase.configuration');

    // @see https://firebase.google.com
    $form['firebase'] = [
      '#type' => 'details',
      '#title' => $this->t('Configure Firebase'),
      '#open' => TRUE,
    ];

    $form['firebase']['firebase_server_key'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Firebase Server Key'),
      '#description' => $this->t('This is the server key. <em>Do not confuse with API Key</em>'),
      '#default_value' => $config->get('firebase_server_key'),
      '#required' => TRUE,
    ];

    $form['firebase']['firebase_endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Firebase endpoint'),
      '#description' => $this->t('Google Firebase Cloud Messaging endpoint.'),
      '#default_value' => $config->get('firebase_endpoint'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('firebase.configuration');
    $config
      ->set('firebase_server_key', $form_state->getValue('firebase_server_key'))
      ->set('firebase_endpoint', $form_state->getValue('firebase_endpoint'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
