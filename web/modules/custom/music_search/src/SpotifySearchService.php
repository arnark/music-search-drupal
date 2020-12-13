<?php

namespace Drupal\music_search;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\Exception\RequestException;


class SpotifySearchService {

  /**
   * Sends a GET query to Spotify - String Search
   *
   * @param $api_request_uri string
   *   Given API request URI
   * @return array|bool|object
   *   Returns JSON of the search results
   */
  function _spotify_api_get_query($api_request_uri) {

    $client = \Drupal::httpClient();
    $cache = $this->_spotify_api_get_cache($api_request_uri);
    $search_results = [];

    if (!empty($cache)) {
      $search_results = $cache;
    } else {
      $token_cache = $this->_spotify_api_get_cache('token_cache');
      if (!empty($token_cache)) {
        $token = $token_cache;
      } else {
        $token = $this->_spotify_api_get_auth_token();
        $this->_spotify_api_set_cache_token($token);
      }

      $options = array(
        'method' => 'GET',
        'timeout' => 3,
        'headers' => array(
          'Accept' => 'application/json',
          'Authorization' => "Bearer " . $token['access_token'],
        ),
      );

      try {
        $request = $client->get($api_request_uri, $options);
        $search_results = $request->getBody();
        $search_results = Json::decode($search_results);
      } catch (RequestException $e) {
        watchdog_exception('Exception: ', $e);
      }
    }

    $this->_spotify_api_set_cache_search($api_request_uri, $search_results);
    return $search_results;
  }

  /**
   * Gets Auth token from the Spotify API
   */
  function _spotify_api_get_auth_token() {

    // Get config data from config music_search.api_keys
    $config = \Drupal::config('music_search.api_keys');

    $client_id = $config->get('spotify_client_id');
    $client_secret = $config->get('spotify_client_secret');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,            'https://accounts.spotify.com/api/token' );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt($ch, CURLOPT_POST,           1 );
    curl_setopt($ch, CURLOPT_POSTFIELDS,     'grant_type=client_credentials' );
    curl_setopt($ch, CURLOPT_HTTPHEADER,     array('Authorization: Basic '.base64_encode($client_id.':'.$client_secret)));

    $result=curl_exec($ch);

    return Json::decode($result);
  }

  /**
   * Saves a search to Drupal's internal cache.
   *
   * @param string $cid
   *   The cache id to use.
   * @param array $data
   *   The data to cache.
   */
  function _spotify_api_set_cache_search($cid, array $data) {
    \Drupal::cache()->set($cid, $data, time() + 3600);
  }

  /**
   * Saves a search to Drupal's internal cache.
   *
   * @param array $token
   *   The token data
   */
  function _spotify_api_set_cache_token(array $token) {
    \Drupal::cache()->set('token_cache', $token, time() + $token['expires_in']);
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
  function _spotify_api_get_cache($cid) {
    $cache = \Drupal::cache()->get($cid);
    if (!empty($cache)) {
      if ($cache->expire > time()) {
        return $cache->data;
      }
    }
    return FALSE;
  }

}
