<?php

namespace Drupal\music_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\music_search\MusicSearchApiKeys;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Music Search
 *
 * @return array
 *  Our Message in a render array
 */

class MusicSearchController extends ControllerBase {
  /**
   * The API key service
   *
   * @var \Drupal\music_search\MusicSearchApiKeys
   */
  protected $spotify_client_id;

  /**
   * MusicSearchController constructor.
   * @param MusicSearchApiKeys $spotify_client_id
   */
  public function __construct(MusicSearchApiKeys $spotify_client_id) {
    $this->spotify_client_id = $spotify_client_id;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('music_search.spotify_client_id')
    );
  }

  public function musicSearch() {
    return [
      '#markup' => $this->spotify_client_id->getApiKeys(),
    ];
  }
}
