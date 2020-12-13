<?php

namespace Drupal\music_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\music_search\SpotifySearchService;
use Drupal\music_search\DiscogsSearchService;

/**
 * Defines a route controller for watches autocomplete form elements.
 */
class AutocompleteSearchController extends ControllerBase {

  /**
   * The API key service
   *
   * @var \Drupal\music_search\SpotifySearchService
   * @var \Drupal\music_search\DiscogsSearchService
   */
  protected $spotify_search;
  protected $discogs_search;

  /**
   * MusicSearchController constructor.
   * @param SpotifySearchService $spotify_search
   * @param DiscogsSearchService $discogs_search
   */
  public function __construct(SpotifySearchService $spotify_search, DiscogsSearchService $discogs_search) {
    $this->spotify_search = $spotify_search;
    $this->discogs_search = $discogs_search;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('music_search.spotify_search'),
      $container->get('music_search.discogs_search')
    );
  }

  /**
   * Handler for song autocomplete request.
   */
  public function handleAutocompleteSongs(Request $request) {
    $results = [];
    $input = $request->query->get('q');
    $input = Xss::filter($input);

    if (!$input) {
      return new JsonResponse($results);
    }

    $spotify_results = $this->spotify_search->_spotify_api_get_query("https://api.spotify.com/v1/search/?q=$input&type=track&limit=5");
    $discogs_results = $this->discogs_search->_discogs_api_get_query("https://api.discogs.com/database/search?track=$input&type=release&per_page=5");

    // Spotify list item view
    foreach ($spotify_results['tracks']['items'] as $track) {
      $results[] = [
        'value' => $track['name'],
        'label' => $track['name'] . " - " . $track['artists'][0]['name'] . " (Spotify)",
      ];
    }

    // Discogs list item view
    foreach ($discogs_results as $track) {
      $results[] = [
        'value' => $track['title'],
        'label' => $track['title'] . ' (Discogs)',
      ];
    }

    //$all_results = array_merge($spotify_results, $discogs_results);

    return new JsonResponse($results);
  }

  /**
   * Handler for album autocomplete request.
   */
  public function handleAutocompleteAlbums(Request $request) {
    $results = [];
    $input = $request->query->get('q');
    $input = Xss::filter($input);

    if (!$input) {
      return new JsonResponse($results);
    }

    $spotify_results = $this->spotify_search->_spotify_api_get_query("https://api.spotify.com/v1/search/?q=$input&type=album&limit=5");
    $discogs_results = $this->discogs_search->_discogs_api_get_query("https://api.discogs.com/database/search?q=$input&type=release&per_page=5");

    // Spotify list item view
    foreach ($spotify_results['albums']['items'] as $album) {
      $results[] = [
        'value' => $album['name'],
        'label' => $album['name'] . " - " . $album['artists'][0]['name'] . " (Spotify)",
      ];
    }

    // Discogs list item view
    foreach ($discogs_results as $album) {
      $results[] = [
        'value' => $album['title'],
        'label' => $album['title'] . ' (Discogs)',
      ];
    }

    //$all_results = array_merge($spotify_results, $discogs_results);

    return new JsonResponse($results);
  }

  /**
   * Handler for artist autocomplete request.
   */
  public function handleAutocompleteArtists(Request $request) {
    $results = [];
    $input = $request->query->get('q');
    $input = Xss::filter($input);

    if (!$input) {
      return new JsonResponse($results);
    }

    $spotify_results = $this->spotify_search->_spotify_api_get_query("https://api.spotify.com/v1/search/?q=$input&type=artist&limit=5");
    $discogs_results = $this->discogs_search->_discogs_api_get_query("https://api.discogs.com/database/search?q=$input&type=artist&per_page=5");

    // Spotify list item view
    foreach ($spotify_results['artists']['items'] as $artist) {
      $results[] = [
        'value' => $artist['name'],
        'label' => $artist['name'] . " (Spotify)",
      ];
    }

    // Discogs list item view
    foreach ($discogs_results as $artist) {
      $results[] = [
        'value' => $artist['title'],
        'label' => $artist['title'] . ' (Discogs)',
      ];
    }

    return new JsonResponse($results);
  }
}
