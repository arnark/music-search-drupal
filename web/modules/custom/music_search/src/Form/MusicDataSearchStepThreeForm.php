<?php

namespace Drupal\music_search\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\music_search\DiscogsSearchService;
use Drupal\music_search\SpotifySearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Component\Render\FormattableMarkup;
use \Drupal\node\Entity\Node;
use \Drupal\node\Entity\NodeType;
use \Drupal\file\Entity\File;

/**
 * Form to handle article autocomplete.
 */
class MusicDataSearchStepThreeForm extends FormBase {

  private $tempStoreFactory;
  protected $spotify_search;
  protected $discogs_search;

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'music_data_search_form_step_3';
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

  public function getItemType() {
    $tempstore = $this->tempStoreFactory->get('music_search.form_data');
    $item_type = $tempstore->get('item_type');
    if ($item_type === 'songs') { $item_type = 'tracks'; }
    $item_type_translation = [
      'artists' => 'artist',
      'albums' => 'album',
      'tracks' => 'track'
    ];
    return $item_type_translation[$item_type];
  }

  public function generateTableSelectForm($header, $output, $multiple = False) {
    return [
      '#type' => 'tableselect',
      '#multiple' => $multiple,
      '#header' => $header,
      '#options' => $output,
      '#empty' => t('No items to show'),
    ];
  }

  public function createImageNodes($alt, array $image_selection) {
    $opts = [
      "http" => [
        "method" => "GET",
        "header" =>
          "User-Agent: PostmanRuntime/7.26.8\r\n"
      ]
    ];

    $selected_images = [];
    foreach ($image_selection as $image_url) {
      $context = stream_context_create($opts);
      $data = file_get_contents($image_url, false, $context);
      $file = file_save_data($data, 'public://' . time() . '-' . rand() . '.jpg', TRUE);

      $image_node = [
        'target_id' => $file->id(),
        'alt' => $alt,
        'title' => $alt
      ];

      array_push($selected_images, $image_node);
    }
    return $selected_images;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $item_type = $this->getItemType();
    $tempstore = $this->tempStoreFactory->get('music_search.form_data');
    $spotify_id_selections = $tempstore->get('spotify_id_selections');
    $discogs_id_selections = $tempstore->get('discogs_id_selections');

    if ($item_type === 'artist') {

      $artist_names = [];
      $artist_images = [];
      $artist_descriptions = [];
      $artist_urls = [];
      $artist_dobs = [];
      $artist_dods = [];

      foreach ($spotify_id_selections as $artist_id) {
        $spotify_results = $this->spotify_search->_spotify_api_get_query("https://api.spotify.com/v1/artists/$artist_id");
        $artist_names[$spotify_results['name']]['name'] = $spotify_results['name'];
        foreach ($spotify_results['images'] as $image) {
          $artist_images[$image['url']]['images'] = new FormattableMarkup('<img src="@src" />',['@src' => $image['url']]);
        }
      }

      foreach ($discogs_id_selections as $artist_id) {
        $discogs_results = $this->discogs_search->_discogs_api_get_query("https://api.discogs.com/artists/$artist_id");

        $artist_names[$discogs_results['namevariations'][0]]['name'] = $discogs_results['namevariations'][0];
        $artist_descriptions[$discogs_results['profile']]['description'] = $discogs_results['profile'];

        foreach ($discogs_results['images'] as $image) {
          $artist_images[$image['resource_url']]['images'] = new FormattableMarkup('<img src="@src" />',['@src' => $image['resource_url']]);
        }
        foreach ($discogs_results['urls'] as $url) {
          $artist_urls[$url]['url'] = new FormattableMarkup('<a href="@href">@href</a>',['@href' => $url]);
        }
      }

      $artist_name_form_header = ["name" => "Name"];
      $artist_name_form_output = $artist_names;

      $artist_images_form_header = ["images" => "Images"];
      $artist_images_form_output = $artist_images;

      $artist_description_form_header = ["description" => "Description"];
      $artist_description_form_output = $artist_descriptions;

      $artist_url_form_header = ["url" => "URL"];
      $artist_url_form_output = $artist_urls;

      $form['artist_label'] = [
        '#type' => 'label',
        '#title' => $this->t('Select your preferred values'),
      ];

      $form['artist_name_table'] = $this->generateTableSelectForm($artist_name_form_header, $artist_name_form_output);
      $form['artist_images_table'] = $this->generateTableSelectForm($artist_images_form_header, $artist_images_form_output, TRUE);
      $form['artist_description_table'] = $this->generateTableSelectForm($artist_description_form_header, $artist_description_form_output);
      $form['artist_url_table'] = $this->generateTableSelectForm($artist_url_form_header, $artist_url_form_output);

    }

    if ($item_type === 'album') {

      $album_covers = [];
      $album_names = [];
      $album_artists = [];
      $album_descriptions = [];
      $album_labels = [];
      $album_songs = [];
      $album_years = [];
      $album_genres = [];

      foreach ($spotify_id_selections as $album_id) {
        $spotify_results = $this->spotify_search->_spotify_api_get_query("https://api.spotify.com/v1/albums/$album_id");

        $album_names[$spotify_results['name']]['name'] = $spotify_results['name'];
        $album_artists[$spotify_results['artists'][0]['name']]['artist'] = $spotify_results['artists'][0]['name'];
        $album_years[$spotify_results['release_date']]['year'] = $spotify_results['release_date'];
        $album_covers[$spotify_results['images'][0]['url']]['cover'] = new FormattableMarkup('<img src="@src" />',['@src' => $spotify_results['images'][0]['url']]);

        foreach ($spotify_results['tracks']['items'] as $track) {
          $album_songs[$track['name']]['song'] = $track['name'];
        }
        foreach ($spotify_results['genres'] as $genre) {
          $album_genres[$genre]['genre'] = $genre;
        }

      }

      foreach ($discogs_id_selections as $album_id) {
        $discogs_results = $this->discogs_search->_discogs_api_get_query("https://api.discogs.com/masters/$album_id");

        $album_names[$discogs_results['title']]['name'] = $discogs_results['title'];
        $album_artists[$discogs_results['artists'][0]['name']]['name'] = $discogs_results['artists'][0]['name'];
        $album_years[$discogs_results['year']]['year'] = $discogs_results['year'];
        $album_covers[$discogs_results['images'][0]['uri']]['cover'] = new FormattableMarkup('<img src="@src" />',['@src' => $discogs_results['images'][0]['uri']]);
        foreach ($discogs_results['tracklist'] as $track) {
          $album_songs[$track['title']]['song'] = $track['title'];
        }
        foreach ($discogs_results['genres'] as $genre) {
          $album_genres[$genre]['genre'] = $genre;
        }

      }

      $album_name_form_header = ["name" => "Album name"];
      $album_name_form_output = $album_names;

      $album_covers_form_header = ["cover" => "Cover art"];
      $album_covers_form_output = $album_covers;

      $album_artists_form_header = ["artist" => "Artist"];
      $album_artists_form_output = $album_artists;

      $album_songs_form_header = ["song" => "Song"];
      $album_songs_form_output = $album_songs;

      $album_years_form_header = ["year" => "Year"];
      $album_years_form_output = $album_years;

      $album_genres_form_header = ["genre" => "Genre"];
      $album_genres_form_output = $album_genres;

      $form['album_name_table'] = $this->generateTableSelectForm($album_name_form_header, $album_name_form_output);
      $form['album_cover_table'] = $this->generateTableSelectForm($album_covers_form_header, $album_covers_form_output);
      $form['album_artist_table'] = $this->generateTableSelectForm($album_artists_form_header, $album_artists_form_output);
      $form['album_song_table'] = $this->generateTableSelectForm($album_songs_form_header, $album_songs_form_output, TRUE);
      $form['album_year_table'] = $this->generateTableSelectForm($album_years_form_header, $album_years_form_output);
      $form['album_genre_table'] = $this->generateTableSelectForm($album_genres_form_header, $album_genres_form_output);

    }

    if ($item_type === 'track') {

      $song_names = [];
      $song_lengths = [];
      $song_albums = [];
      $song_genres = [];
      $song_spotify_id = null;

      foreach ($spotify_id_selections as $track_id) {
        $spotify_results = $this->spotify_search->_spotify_api_get_query("https://api.spotify.com/v1/tracks/$track_id");

        $song_names[$spotify_results['name']]['name'] = $spotify_results['name'];
        $song_lengths[round($spotify_results['duration_ms'] / 1000)]['duration'] = round($spotify_results['duration_ms'] / 1000);
        $song_albums[$spotify_results['album']['name']]['album'] = $spotify_results['album']['name'];

      }

      foreach ($discogs_id_selections as $track_id) {
        $discogs_results = $this->discogs_search->_discogs_api_get_query("https://api.discogs.com/releases/$track_id");

        $song_names[$discogs_results['title']]['name'] = $discogs_results['title'];
        foreach ($discogs_results['genres'] as $genre) {
          $song_genres[$genre]['genre'] = $genre;
        }

      }

      $song_name_form_header = ["name" => "Song name"];
      $song_name_form_output = $song_names;

      $song_length_form_header = ["duration" => "Song length (s)"];
      $song_length_form_output = $song_lengths;

      $song_genres_form_header = ["genre" => "Genre"];
      $song_genres_form_output = $song_genres;

      $song_albums_form_header = ["album" => "Album"];
      $song_albums_form_output = $song_albums;

      $form['song_name_table'] = $this->generateTableSelectForm($song_name_form_header, $song_name_form_output);
      $form['song_album_table'] = $this->generateTableSelectForm($song_albums_form_header, $song_albums_form_output);
      $form['song_length_table'] = $this->generateTableSelectForm($song_length_form_header, $song_length_form_output);
      $form['song_genre_table'] = $this->generateTableSelectForm($song_genres_form_header, $song_genres_form_output);

    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit!'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $item_type = $this->getItemType();

    if ($item_type === 'artist') {
      $artist_name_selection = $form_state->getValue('artist_name_table');
      $artist_images_selection = array_filter($form_state->getValue('artist_images_table'));
      $artist_description_selection = $form_state->getValue('artist_description_table');
      $artist_url_selection = $form_state->getValue('artist_url_table');
      $selected_images = $this->createImageNodes($artist_name_selection, $artist_images_selection);

      $node = Node::create([
        'type' => 'artist',
        'title' => $artist_name_selection,
        'field_artist_description' => $artist_description_selection,
        'field_url' => $artist_url_selection,
        'field_images' => $selected_images,
        'status' => 1
      ]);
      $node->save();

      header("Location: /node/". $node->id());
    }

    if ($item_type === 'album') {

      $album_name_selection = $form_state->getValue('album_name_table');
      $album_cover_selection = $form_state->getValue('album_cover_table');
      $album_artist_selection = $form_state->get('album_artist_table');
      $album_song_selection = $form_state->get('album_song_table');
      $album_year_selection = $form_state->get('album_year_table');
      $album_genre_selection = $form_state->get('album_genre_table');
      $selected_cover = $this->createImageNodes($album_name_selection, [$album_cover_selection]);

      $node = Node::create([
        'type' => 'album',
        'title' => $album_name_selection,
        'field_album_cover' => $selected_cover,
        'field_artist' => $album_artist_selection,
        'field_songs' => $album_song_selection,
        'field_album_genre' => $album_genre_selection,
        'field_year' => $album_year_selection,
        'status' => 1
      ]);
      $node->save();

      header("Location: /node/". $node->id());

    }

    if ($item_type === 'track') {

      $song_name_selection = $form_state->getValue('song_name_table');
      $song_length_selection = $form_state->getValue('song_length_table');
      $song_album_selection = $form_state->getValue('song_album_table');
      $song_genre_selection = $form_state->getValue('song_genre_table');

      $album_node = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadByProperties([
          'title' => $song_album_selection,
          'type' => 'album'
        ]);

      if (!$album_node) {
        $album_node = '';
      }

      $node = Node::create([
        'type' => 'song',
        'title' => $song_name_selection,
        'field_album' => $album_node,
        'field_length' => $song_length_selection,
        'field_genre' => $song_genre_selection,
        'status' => 1
      ]);
      $node->save();

      header("Location: /node/". $node->id());

    }

  }
}
