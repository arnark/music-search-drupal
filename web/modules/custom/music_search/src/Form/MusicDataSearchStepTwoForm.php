<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\music_search\DiscogsSearchService;
use Drupal\music_search\SpotifySearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Form to handle article autocomplete.
 */
class MusicDataSearchStepTwoForm extends FormBase {

  private $tempStoreFactory;
  protected $spotify_search;
  protected $discogs_search;

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'music_data_search_form_step_2';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    PrivateTempStoreFactory $tempStoreFactory,
    SpotifySearchService $spotify_search,
    DiscogsSearchService $discogs_search
  ) {
    $this->tempStoreFactory = $tempStoreFactory;
    $this->spotify_search = $spotify_search;
    $this->discogs_search = $discogs_search;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('music_search.spotify_search'),
      $container->get('music_search.discogs_search')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $tempstore = $this->tempStoreFactory->get('music_search.form_data');
    $string_query = $tempstore->get('string_query');
    $item_type = $tempstore->get('item_type');

    if ($item_type === 'songs') { $item_type = 'tracks'; }
    $item_type_translation = [
      'artists' => 'artist',
      'albums' => 'album',
      'tracks' => 'track'
    ];

    $spotify_results = [];
    $discogs_results = [];
    if ($item_type) {
      if ($item_type === 'album' ) {
        $discogs_request_uri = 'https://api.discogs.com/database/search?q=' . $string_query . '&type=release&per_page=5';
      }
      else if ($item_type === 'track') {
        $discogs_request_uri = 'https://api.discogs.com/database/search?track=' . $string_query . '&type=release&per_page=5';
      }
      else if ($item_type === 'artist') {
        $discogs_request_uri = 'https://api.discogs.com/database/search?q=' . $string_query . '&type=' . $item_type_translation[$item_type] . '&per_page=5';
      }
      else {
        $discogs_request_uri = 'https://api.discogs.com/database/search?q=' . $string_query . '&per_page=5';
      }

      $spotify_results = $this->spotify_search
        ->_spotify_api_get_query("https://api.spotify.com/v1/search/?q=$string_query&type=$item_type_translation[$item_type]&limit=5");
      $discogs_results = $this->discogs_search->_discogs_api_get_query($discogs_request_uri);
    }

    /**
     * Spotify table
     */
    // Spotify results into table data
    $spotify_output = array();
    $spotify_header = array();
    if ($spotify_results) {
      foreach ($spotify_results[$item_type]['items'] as $item) {
        if (array_key_exists('name', $item)) {
          $spotify_output[$item['id']]['name'] = $item['name'];
          $spotify_header['name'] = t('Name');
        }
        if (array_key_exists('artists', $item)) {
          $spotify_output[$item['id']]['artist'] = $item['artists'][0]['name'];
          $spotify_header['artist'] = t('Artist');
        }
        if (array_key_exists('total_tracks', $item)) {
          $spotify_output[$item['id']]['total_tracks'] = $item['total_tracks'];
          $spotify_header['total_tracks'] = t('No. tracks');
        }
        if (array_key_exists('release_date', $item)) {
          $spotify_output[$item['id']]['release_date'] = $item['release_date'];
          $spotify_header['release_date'] = t('Release date');
        }
        if (array_key_exists('images', $item)) {
          if (array_key_exists(1, $item['images'])) {
            $spotify_output[$item['id']]['thumb'] = new FormattableMarkup('<img src="@src" />', ['@src' => $item['images'][1]['url']]);
            $spotify_header['thumb'] = t('Image');
          }
        }
      }
    }

    // Label for spotify results, for some reason the title on spotify_table doesn't  work
    $form['spotify_label'] = [
      '#type' => 'label',
      '#title' => $this->t('Spotify '. $item_type .' results'),
    ];

    // Spotify results table
    $form['spotify_table'] = [
      '#type' => 'tableselect',
      '#header' => $spotify_header,
      '#options' => $spotify_output,
      '#empty' => t('No '. $item_type .' found'),
    ];

    /**
     * Discogs table
     */
    // Discogs results into table data
    $discogs_output = array();
    $discogs_header = array();
    if ($discogs_results) {
      foreach ($discogs_results as $item) {
        if (array_key_exists('title', $item)) {
          $discogs_output[$item['id']]['name'] = $item['title'];
          $discogs_header['name'] = t('Name');
        }
        if (array_key_exists('year', $item)) {
          $discogs_output[$item['id']]['year'] = $item['year'];
          $discogs_header['year'] = t('Year');
        }
        if (array_key_exists('thumb', $item)) {
          $discogs_output[$item['id']]['thumb'] = new FormattableMarkup('<img src="@src" />',['@src' => $item['thumb']]);
          $discogs_header['thumb'] = t('Image');
        }
      }
    }

    // Label for discogs results, for some reason the title on discogs_table doesn't  work
    $form['discogs_label'] = [
      '#type' => 'label',
      '#title' => $this->t('Discogs '. $item_type .' results'),
    ];

    // Discogs results table
    $form['discogs_table'] = [
      '#type' => 'tableselect',
      '#header' => $discogs_header,
      '#options' => $discogs_output,
      '#empty' => t('No '. $item_type .' found'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $spotify_table_selections = array_filter($form_state->getValue('spotify_table'));
    $discogs_table_selections = array_filter($form_state->getValue('discogs_table'));

    $tempstore = $this->tempStoreFactory->get('music_search.form_data');
    $tempstore->set('spotify_id_selections', $spotify_table_selections);
    $tempstore->set('discogs_id_selections', $discogs_table_selections);

    $form_state->setRedirect('music_search.music_search_step_3');
  }
}
