<?php

namespace Drupal\music_search;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\Exception\RequestException;


class DiscogsSearchService {

  /**
   * Sends a GET query to Discogs - String Search
   *
   * @param $query string
   *   Given query string
   * @param $item_type string
   *   Given item type (artist, track, album)
   * @return array|bool|object
   *   Returns JSON of the search results
   */
  function _discogs_api_get_query($api_request_uri) {

    $client = \Drupal::httpClient();
    $cache = $this->_discogs_api_get_cache($api_request_uri);
    $search_results = [];

    if (!empty($cache)) {
      $search_results = $cache;
    } else {

      $config = \Drupal::config('music_search.api_keys');
      $token = $config->get('discogs_personal_access_token');

      $options = array(
        'method' => 'GET',
        'timeout' => 3,
        'headers' => array(
          'Accept' => 'application/json',
          'Authorization' => 'Discogs token=' . $token,
          'User-Agent' => 'PostmanRuntime/7.26.8'
        ),
      );

      try {
        $request = $client->get($api_request_uri, $options);
        $search_results = $request->getBody();
        $search_results = Json::decode($search_results);
        if (array_key_exists('results', $search_results)) {
          $search_results = $search_results['results'];
        }

      } catch (RequestException $e) {
        watchdog_exception('Exception: ', $e);
      }

    }

    $this->_discogs_api_set_cache_search($api_request_uri, $search_results);
    return $search_results;
  }

  /**
   * Saves a search to Drupal's internal cache.
   *
   * @param string $cid
   *   The cache id to use.
   * @param array $data
   *   The data to cache.
   */
  function _discogs_api_set_cache_search($cid, array $data) {
    \Drupal::cache()->set($cid, $data, time() + 3600);
  }

  /**
   * Looks up the specified cid in cache and returns if found
   *
   * @param string $cid
   *   Any cache id string
   *
   * @return array|bool
   *   Returns either the cache results or false if nothing is found.
   */
  function _discogs_api_get_cache($cid) {
    $cache = \Drupal::cache()->get($cid);
    if (!empty($cache)) {
      if ($cache->expire > time()) {
        return $cache->data;
      }
    }
    return FALSE;
  }

}
