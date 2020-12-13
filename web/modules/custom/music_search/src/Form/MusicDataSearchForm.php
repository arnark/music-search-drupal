<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

/**
 * Form to handle article autocomplete.
 */
class MusicDataSearchForm extends FormBase {

  private $tempStoreFactory;

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'music_data_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(PrivateTempStoreFactory $tempStoreFactory) {
    $this->tempStoreFactory = $tempStoreFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Radio button selection
    $form['search_type_select'] = [
      '#type' => 'radios',
      '#title' => $this->t('What are you searching for:'),
      '#options' => [
        'albums' => $this->t('Albums'),
        'artists' => $this->t('Artists'),
        'songs' => $this->t('Songs')
      ],
    ];

    // Songs radio button chosen
    $form['song_search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Song search'),
      '#autocomplete_route_name' => 'music_search.autocomplete.songs',
      '#attributes' => [
        'id' => 'song-search',
      ],
      '#states' => [
        'visible' => [
          ':input[name="search_type_select"]'=> ['value' => 'songs'],
        ],
      ],
    ];

    // Albums radio button chosen
    $form['album_search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Album search'),
      '#autocomplete_route_name' => 'music_search.autocomplete.albums',
      '#attributes' => [
        'id' => 'album-search',
      ],
      '#states' => [
        'visible' => [
          ':input[name="search_type_select"]'=> ['value' => 'albums'],
        ],
      ],
    ];

    // Artists radio button chosen
    $form['artist_search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Artist search'),
      '#autocomplete_route_name' => 'music_search.autocomplete.artists',
      '#attributes' => [
        'id' => 'artist-search',
      ],
      '#states' => [
        'visible' => [
          ':input[name="search_type_select"]'=> ['value' => 'artists'],
        ],
      ],
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

    $item_type = $form_state->getValue('search_type_select');
    $string_query = '';

    if ($item_type === 'albums') {
      $string_query = $form_state->getValue('album_search');
    }
    if ($item_type === 'artists') {
      $string_query = $form_state->getValue('artist_search');
    }
    if ($item_type === 'songs') {
      $string_query = $form_state->getValue('song_search');
    }

    $tempstore = $this->tempStoreFactory->get('music_search.form_data');
    $tempstore->set('item_type', $item_type);
    $tempstore->set('string_query', $string_query);

    $form_state->setRedirect('music_search.music_search_step_2');
  }
}
