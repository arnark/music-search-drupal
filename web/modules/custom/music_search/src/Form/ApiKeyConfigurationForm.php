<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * API Configuration form for music data API's
 */
class ApiKeyConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames() {
    return ['music_search.api_keys'];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'api_key_configuration_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('music_search.api_keys');

    $form['spotify_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spotify Client ID'),
      '#description' => $this->t('Please provide your Spotify Client ID'),
      '#default_value' => $config->get('spotify_client_id')
    ];

    $form['spotify_client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spotify Client Secret'),
      '#description' => $this->t('Please provide your Spotify Client Secret'),
      '#default_value' => $config->get('spotify_client_secret')
    ];

    $form['discogs_personal_access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Discogs Personal Access Token'),
      '#description' => $this->t('Please provide your Discogs Personal Access Token'),
      '#default_value' => $config->get('discogs_personal_access_token')
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $spotify_client_id = $form_state->getValue('spotify_client_id');
    $spotify_client_secret = $form_state->getValue('spotify_client_secret');
    $discogs_personal_access_token = $form_state->getValue('discogs_personal_access_token');

    if(strlen($spotify_client_id) < 32 && $spotify_client_id !== '') {
      $form_state->setErrorByName('spotify_client_id', $this->t('The provided Spotify Client ID is invalid.'));
    }

    if(strlen($spotify_client_secret) < 32 && $spotify_client_secret !== '') {
      $form_state->setErrorByName('spotify_client_secret', $this->t('The provided Spotify Client Secret is invalid.'));
    }

    if(strlen($discogs_personal_access_token) < 40 && $discogs_personal_access_token !== '') {
      $form_state->setErrorByName('discogs_personal_access_token', $this->t('The provided Discogs Personal Token is invalid.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('music_search.api_keys')
      ->set('spotify_client_id', $form_state->getValue('spotify_client_id'))
      ->set('spotify_client_secret', $form_state->getValue('spotify_client_secret'))
      ->set('discogs_personal_access_token', $form_state->getValue('discogs_personal_access_token'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
