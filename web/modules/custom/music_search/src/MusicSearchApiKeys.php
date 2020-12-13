<?php

namespace Drupal\music_search;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class MusicSearchApiKeys {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

    /**
     * MusicSearchApiKeys constructor.
     *
     * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
     *   The config factory.
    */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  public function getApiKeys() {
    $config = $this->configFactory->get('music_search.api_keys');
    $spotify_client_id = $config->get('spotify_client_id');

    if ($spotify_client_id !== "" && $spotify_client_id) {
      return $spotify_client_id;
    }

  }
}
