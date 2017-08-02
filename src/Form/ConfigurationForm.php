<?php

namespace Drupal\firebase\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements Firebase API configuration.
 *
 * Creates the administrative form with settings used to
 * connect with Firebase Cloud Messassing API.
 *
 * @see \Drupal\Core\Form\ConfigFormBase
 */
class ConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'firebase.settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['firebase.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('firebase.settings');

    // @see https://firebase.google.com
    $form['firebase'] = [
      '#type' => 'details',
      '#title' => $this->t('Configure Firebase'),
      '#open' => TRUE,
    ];

    $form['firebase']['server_key'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Firebase Server Key'),
      '#description' => $this->t('This is the server key. <em>Do not confuse with API Key</em>'),
      '#default_value' => $config->get('server_key'),
      '#required' => TRUE,
    ];

    $form['firebase']['endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Firebase endpoint'),
      '#description' => $this->t('Google Firebase Cloud Messaging endpoint.'),
      '#default_value' => $config->get('endpoint'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('firebase.settings');
    $config
      ->set('server_key', $form_state->getValue('server_key'))
      ->set('endpoint', $form_state->getValue('endpoint'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
