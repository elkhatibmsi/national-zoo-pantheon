<?php

namespace Drupal\edan_record;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\edan\EdanIDS;
use Drupal\slick\SlickDefault;
use Drupal\slick\Entity\Slick;
use Drupal\file\Entity\File;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Utility\Html;


/**
 * Class EdanRecordMedia.
 */
class EdanRecordMedia implements EdanRecordMediaInterface {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\edan\EdanIDS definition.
   *
   * @var \Drupal\edan\EdanIDS
   */

  protected $media_settings;

  protected $media;

  protected $record;

  protected $media_layout = FALSE;

  protected $has_3d = FALSE;

  protected $has_toolbar = FALSE;
  protected $has_slideshow = FALSE;

  /**
   * Constructs a new EdanRecordMedia object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    $this->media_settings = $this->configFactory->get('edan_record.settings');
  }

  public function getValues($name) {
    return $this->{$name} ?? NULL;
  }

  public function buildMedia($record) {
    $this->record = $record;
    $media = $items = $grouped = [];
    $default_template = isset($record['alt_template']) && !$record['alt_template'];
    $build = [
      '#theme' => 'edan_media',
      '#media_extra' => $default_template
    ];
    $viewerSettings = $this->media_settings->get('viewer');
    // indicate if toolbar should include ids elements
    $viewerSettings['has_ids'] = FALSE;
    $viewerSettings['viewer'] = FALSE;
    $video_types = array(
      'mov',
      'mp4',
      'm4a',
      'm4v',
      'mpeg',
      'avi'
    );
    $audio_types = array(
      'ogg',
      'mp3',
      'wav',
    );
    $has_record_details = !empty($record['fields']);
    foreach ($record['media'] as $index => $asset) {
      $media_type = trim(preg_replace('/\s|-/', '_', $asset['type']));
      $media_type = strtolower($media_type);
      // get media_type by extension
      $ext = $media_type == 'scanned_books' && $asset['thumbnail'] != 'http://sirismm.si.edu/siris/ScannedBook.jpg' ?
        strtolower(pathinfo($asset['thumbnail'], PATHINFO_EXTENSION)) : strtolower(pathinfo($asset['content'], PATHINFO_EXTENSION));
      if ($media_type == 'youtube_videos') {
        $media_type = 'video_recordings';
        $asset['sub_type'] = 'youtube';
      }
      elseif (stristr($asset['content'], 'vimeo')) {
        $media_type = 'video_recordings';
        $asset['sub_type'] = 'vimeo';
      }
      elseif (in_array($ext, $video_types)) {
        $media_type = 'video_recordings';
        $asset['sub_type'] = $ext;
      }
      elseif (in_array($ext, $audio_types)) {
        $media_type = 'audio';
        $asset['sub_type'] = $ext;
      }
      elseif ($ext == 'pdf') {
        $asset['sub_type'] = 'pdf';
      }
      elseif ($media_type == 'scanned_books' && strstr($ext, 'jpg')) {
        $asset['sub_type'] = 'jpg';
      }
      $asset['orig_type'] = $asset['type'];
      $asset['type'] = $media_type;
      $asset['id'] = HTML::getId($record['id'] . '--' . $index . '--' . $asset['type']);
      $asset['attributes'] = array(
        'id' => 'media--' . $asset['id'],
        'class' => array(
          'media-container',
          isset($asset['usage']) && $asset['usage']['access'] == 'CC0' ? 'media--openaccess' : 'media--no-openaccess',
          $asset['has_ids'] ? 'has-ids' : 'no-ids',
          'type--' . $asset['type']
        ),
      );
      $asset['slideshow'] = NULL;
      $asset['toolbar'] = $default_template && (isset($asset['usage']) || $asset['type'] != 'video_recordings') ?
        ['#theme' => 'edan_toolbar', '#settings' => $viewerSettings, '#media' => $asset, '#record' => $record] : [];

      $singular = array(
        'Blog posts' => 'Blog post',
        'Finding aids' => 'Finding aid',
        'Online exhibits' => 'Online exhibit',
        'Scanned books' => 'Scanned book',
        'Transcripts' => 'Transcript',
        'Full text documents' => 'Document',
        'Online publications' => 'Publication'
      );
      switch ($asset['type']) {
        case 'images':
          $this->media_layout = $has_record_details;
          $asset['viewer'] = isset($asset['idsId']) ? Url::fromRoute('edan_record.viewer', ['edan_id' => $record['url']],['query' => ['id' => $asset['idsId']], 'absolute' => TRUE])->toString() : $asset['content'];
          $asset['rendered'] = [
            '#theme' => 'edan_media_item',
            '#item' => [
              '#theme' => 'image',
              '#uri' => $asset['large'],
              '#alt' => $asset['alt_text'],
            ],
            '#caption' => $asset['caption'] ?? NULL,
            '#media' => $asset,
            '#record' => $record
          ];
          $grouped['slide'][] = $asset;
          break;
        case 'map':
        case 'video_embed':
          $this->media_layout = $has_record_details;
          $this->renderIframe($asset, $record);
          $grouped['slide'][] = $asset;
          break;
        case 'url':
          $link = Link::fromTextAndUrl('External Link', Url::fromUri($asset['content']));
          $asset['rendered'] = $link->toRenderable();
          $items[] = $asset;
          break;
        case 'video_recordings':
          $this->media_layout = $has_record_details;
          $video_id = HTML::getId($record['id'] .'-video-'. $index);
          $output = '<video preload="none" style="max-width:100%;" width="640" height="360" id="'. $video_id .'">';
            $video_attributes['stretching'] = 'responsive';
          $output .= '<source src="'. $asset['content'] .'"';
            $asset['attributes']['class'][] = 'mediaelement';
            $build['#attached']['library'][] = 'edan_record/mediaelement';
            $src_attributes = array(
              'src' => $asset['content']
            );
            if ($asset['sub_type'] == 'youtube') {
              $src_attributes['type'] = 'video/youtube';
              $output .= ' type="video/youtube"';
            }
            elseif ($asset['sub_type'] == 'vimeo') {
              $build['#attached']['library'][] = 'edan_record/vimeo';
              $output .= ' type="video/vimeo"';
            }
            else {
              $output .= ' type="video/'. $asset['sub_type'] .'"';
            }
            $output .= ' /></video>';
          $asset['rendered'] = [
//            '#theme' => 'edan_media_item',
//            '#item' => [
              '#markup' => $output,
              '#allowed_tags' => ['video', 'source'],
              '#prefix' => '<div class="mediaelement-video">',
              '#suffix' => '</div>'
//            ],
//            '#caption' => $asset['caption'] ?? NULL,
//            '#media' => $asset,
//            '#record' => $record
          ];
            $video_settings['#'. $video_id] = array(
              'features' => ["playpause", "current", "progress", "duration", "tracks", "volume", "fullscreen"],
              'stretching' => 'responsive',
              'shimScriptAccess' => 'always'
            );

          $build['#attached']['drupalSettings']['edanMediaelement'] = $video_settings;
          $items[] = $asset;
          break;
        case 'audio':
          $audio_id = HTML::getId($record['id'] .'-audio-'. $index);
          $asset['attributes']['class'][] = 'mediaelement';
          $build['#attached']['library'][] = 'edan_record/mediaelement';

          $output = '<audio preload="none" style="max-width:100%;" id="'. $audio_id .'" src="'. $asset['content'].'" controls></audio>';

          $asset['rendered'] = [
//            '#theme' => 'edan_media_item',
//            '#item' => [
            '#markup' => $output,
            '#allowed_tags' => ['audio'],
            '#prefix' => '<div class="mediaelement-audio">',
            '#suffix' => '</div>'
//            ],
//            '#caption' => $asset['caption'] ?? NULL,
//            '#media' => $asset,
//            '#record' => $record
          ];

          $audio_settings['#'. $audio_id] = array(
            'features' => ["playpause", "current", "progress", "duration", "tracks", "volume"],
            'shimScriptAccess' => 'always'
          );
          $build['#attached']['drupalSettings']['edanMediaelement'] = $audio_settings;
          $items[] = $asset;
          break;
        case 'slideshowhtml':
          $this->media_layout = $has_record_details;
          $markup = isset($asset['large']) ? '<img src="' . $asset['large'] . '" alt="" />' : '';
          $markup .= isset($asset['caption']) ? '<span class="caption">'. $asset['caption'] .'</span>': '<span class="caption">View Slideshow <span class="visually-hidden">for ' . $record['title'] . '</span></span>';
          $asset['rendered'] = [
            '#type' => 'link',
            '#title' => [
              '#markup' => $markup,
            ],
            '#url' => Url::fromUri($asset['content']),
          ];
          $items[] = $asset;
          break;
        case '3d_package':
          $this->media_layout = $has_record_details;
          $this->renderIframe($asset, $record);
          $this->has_3d = TRUE;
          $asset['viewer'] = Url::fromRoute('edan_record.viewer', ['edan_id' => $record['url']],['query' => ['id' => $asset['idsId']], 'absolute' => TRUE])->toString();

          $grouped['3d_package'][] = $asset;
          break;
        default:
          $text = isset($singular[$asset['orig_type']]) ? $singular[$asset['orig_type']] : $asset['orig_type'];
          if (isset($asset['sub_type']) && $asset['sub_type'] === 'pdf' && !isset($asset['thumbnail'])) {
            $markup = self::svg_icons('pdf');
            $markup .= isset($asset['caption']) ? '<span class="caption">'. $asset['caption'] .'</span>': '<span class="caption"> <span class="visually-hidden">' . $media['orig_type'] . ' for ' . $record['title'] . '</span></span>';
            $asset['rendered'] = [
              '#type' => 'link',
              '#title' => [
                '#markup' => $markup,
              ],
              '#url' => Url::fromUri($asset['content']),
            ];
          }
          else {
            $markup = isset($asset['large']) ? '<img src="' . $asset['large'] . '" alt="" />' : '';
            $markup .= isset($asset['caption']) ? '<span class="caption">'. $asset['caption'] .'</span>' : '<span class="caption">View ' . $text . ' <span class="visually-hidden">for ' . $record['title'] . '</span></span>';
            $asset['rendered'] = [
              '#type' => 'link',
              '#title' => [
                '#markup' => $markup,
              ],
              '#url' => Url::fromUri($asset['content']),
            ];
          }
          $items[] = $asset;
          break;

      }
    }
    // ensure slideshow is rendered first;
    if ($grouped) {
      // add 3d_package to beginning of slideshow
      $slideshow = $this->has_3d ? (isset($grouped['slide']) ? array_merge($grouped['3d_package'], $grouped['slide']) : $grouped['3d_package']) : $grouped['slide'];
      $item = current($slideshow);

      $item['slideshow'] = $default_template ? $this->buildSlideshow($record, $slideshow) : [];
      $viewerSettings['has_ids'] = TRUE;
      $item['toolbar'] = $default_template && (isset($item['usage']) || $item['type'] != 'video_recordings') ?
        ['#theme' => 'edan_toolbar', '#settings' => $viewerSettings, '#media' => $item, '#record' => $record] : [];
      array_unshift($items, $item);
    }
    $build['#items'] = $items;
    $build['#record'] = $record;
    $build['#media'] = $this;

    return $build;
  }

  public function renderViewer($record, $idsId = NULL) {
    $media = $this->buildMedia($record);
    $item = current($media['#items']);
    if ($idsId) {
      $items = $media['#record']['media'];
      foreach($items as $index => $data) {
        if ($data['idsId'] === $idsId) {
          $mediaItem = $data;
          $mediaItem['id'] = HTML::getId($record['id'] . '--' . $index . '--' . $data['type']);
        }
      }
    }
    else {
      $mediaItem = $item;
    }
    $mediaArray = [
      'idsId' => $mediaItem['idsId'],
      'type' => strtolower($mediaItem['type']),
      'content' => $mediaItem['content'],
      'caption' => $mediaItem['caption'] ?? '',
      'alt_text' => $mediaItem['alt_text']
    ];
    $build = [
      '#theme' => 'edan_viewer',
      '#idsId' => $idsId ?? $mediaItem['idsId'] ?? NULL,
      '#attached' => [
        'library' => []
      ],
    ];
    $build['#attached']['drupalSettings']['edanViewer']['hasSlideshow'] = FALSE;
    if (isset($item['toolbar'])) {
      $item['toolbar']['#settings']['viewer'] = TRUE;
      $item['toolbar']['#media'] = $mediaItem;
      $build['#toolbar'] = $item['toolbar'];
    }
    if (isset($item['slideshow'])) {
      $build['#slideshow'] = $item['slideshow'];
      $build['#attached']['drupalSettings']['edanViewer']['hasSlideshow'] = TRUE;
      $build['#attached']['library'][] = 'edan_record/slick';
    }
    $build['#media'] = $item;
    $build['#attached']['library'][] = 'edan_record/viewer';
    $build['#attached']['drupalSettings']['edanViewer']['media'] = $mediaArray;
    $build['#attached']['drupalSettings']['edanViewer']['type'] = $item['type'];
    $viewerSettings = $this->media_settings->get('viewer');
    //
    $settings = [
      'id' => 'edan-viewer',
      'prefixUrl' => 'https://openseadragon.github.io/openseadragon/images/',
      'toolbar' => 'edan-image-toolbar-' . HTML::getId( $mediaItem['id']),
      'zoomInButton' => 'zoom-in',
      'zoomOutButton' => 'zoom-out',
      'sequenceMode' => FALSE,
      'homeButton' => 'home',
    ];
    foreach($viewerSettings['features'] as $setting) {
      if ($setting === 'showRotationControl') {
        $settings['showRotationControl'] = TRUE;
        $settings['rotateLeftButton'] = 'rotate-left';
        $settings['rotateRightButton'] = 'rotate-right';
      }
      if ($setting === 'showNavigator') {
        $settings['showNavigator'] = TRUE;
        $settings['navigatorPosition'] = $viewerSettings['navigatorPosition'] ?? 'TOP_RIGHT';
      }
    }
    $build['#attached']['drupalSettings']['edanViewer']['settings'] = $settings;
    //  dump($build);
    return $build;
  }

  private function renderIframe(&$media, $record) {
    $media['rendered'] = [
      '#theme' => 'edan_media_item',
      '#item' => [
          '#type' => 'html_tag',
          '#tag' => 'iframe',
          '#attributes' => [
            'src' => $media['content'],
            'frameborder' => 0,
            'scrolling' => FALSE,
            'allowtransparency' => TRUE,
            'width' => 640,
            'height' => 360,
            'title' => $media['alt_text'] ?: $media['record_title'],
          ]
        ],
      '#caption' => $media['caption'] ?? NULL,
      '#media' => $media,
      '#record' => $record
    ];
  }

  private function buildSlideshow($record, $slideshow) {
    $handleSlideshow = $this->media_settings->get('sliderSource') === 'module';
    $lazyLoad = $handleSlideshow && $this->media_settings->get('lazyload');
    //$imageSizes = $this->getImageInfo($record, 'thumbnail');
    $viewerSettings = $this->media_settings->get('viewer');
    $viewMode = $viewerSettings['mode'];
    $settings = [];
    if (isset($slideshow) && count($slideshow) > 1) {
      $this->has_slideshow = TRUE;
      if ($handleSlideshow) {
        $slick_manager = \Drupal::service('slick.manager');
        $settings = SlickDefault::imageSettings();
        $settings['namespace'] = 'slick';
        $settings['optionset'] = 'edan_nav';
        $settings['id'] = \Drupal\blazy\Blazy::getHtmlId('slick', $this->record['id']);

        $optionset = Slick::loadWithFallback('edan_nav');
      }

      foreach ($slideshow as $delta => $slide) {
        $idsId = $slide['idsId'] ?? '';
        $caption = $slide['type'] === '3d_package' ? t('3D Model') : ($slide['caption'] ?? NULL);

//        $data_array = array(
//          'url' => $slide['has_ids'] ? $slide['large'] : $slide['content'],
//          'idsId' => $idsId,
//          'usage' => $slide['usage']['access'] == 'CC0' ? 'media--openaccess' : 'media--no-openaccess',
//          'type' => $slide['type'],
//          'index' => $delta,
//          // 'expand' => url('/object/viewer', array('absolute' => TRUE)) . '/'. $doc['id'] .'/'. $i,
//          'caption' => $slide['type'] == '3d_package' && isset($slide['usage']['text']) ? $slide['usage']['text'] : (isset($slide['caption']) ? $slide['caption'] : ''),
//          'has_ids' => $slide['has_ids'],
//          'content' => $slide['content'],
//          'alt_text' => $slide['alt_text'],
//        );
//        $text = $slide['type'] == '3d_package' && isset($slide['usage']['text']) ? $slide['usage']['text'] : (isset($slide['caption']) ? $slide['caption'] : '');
//        $text = Json::encode($text);
//        $text = str_replace('"', '', $text);
        $data_array = [
          'data-index' => $delta,
          'data-idsid' => $idsId,
          'data-type' => $slide['type'],
          'data-url' => $slide['type'] == 'images' ? $slide['large'] : $slide['content'],
          'data-content' => $slide['content'],
          'data-caption' => $slide['type'] == '3d_package' && isset($slide['usage']['text']) ? $slide['usage']['text'] : (isset($slide['caption']) ? $slide['caption'] : ''),
          'data-view-mode' => $viewMode,
          'data-title' => $slide['alt_text'],
          'data-viewer' => $slide['viewer']
        ];

      //  $data_array['data-caption'] = Json::encode($data_array['data-caption']);
      //  $data_array['data-edan'] = Json::encode($data_array);
//        $size = isset($slide['idsId']) && isset($imageSizes[$slide['idsId']]) ? $imageSizes[$slide['idsId']] : [
//          'width' => NULL,
//          'height' => NULL
//        ];

       // $data_array = Json::encode($data_array);
          if ($lazyLoad) {
            $settings['delta'] = $delta;

            $item['slide'] = $this->renderImage($slide, 'thumbnail', $settings, $data_array);
          }
          else {
            if ($handleSlideshow) {
              $image =  $this->renderImage($slide, 'thumbnail', $settings, $data_array);
              if ($image) {
                $item = [
                  'item' => $image,
                  'settings' => $settings,
                  //'item_attributes' => $data_array,
                  'slide' => $image
                  // 'attributes' => $data_array
                ];
              }

            }
            else {
              $item =  $this->renderImage($slide, 'thumbnail', $settings, $data_array);
            }

          }
        if ($caption) {
          if ($handleSlideshow) {
            if (!$slide['thumbnail']) {
              $output = '<div class="slide-data"';
              foreach($data_array as $key => $value) {
                if ($key != 'data-caption') {
                  $output .= ' '. $key. '="'. $value .'"';
                }
              }
              $output .= '>'. $caption .'</div>';
              $item['caption']['data'] = ['#markup' =>
                $output,
                '#allowed_tags' => ['div'],

              ];
        //      $item['caption']['data']['#attributes'] = $data_array;
            }
            else {
              $item['caption']['data'] = ['#markup' => $caption];
            }
          }
          else {
            $item['children'] = [$caption];
          }
        }
        $items[$delta] = $item;
      }
      if ($handleSlideshow) {
        $build = [
          'settings' => $settings,
          'optionset' => $optionset,
          'items' => $items,
        ];
        $slick = $slick_manager->slick($build);
        $build = $slick_manager->build($build);
        $build['#items'] = $slick;

        $build['#prefix'] = '<div class="media-slider">';
        $build['#suffix'] = '</div>';
        return $build;
      }
      else {
        return [
          '#theme' => 'item_list',
          '#items' => $items,
          '#wrapper_attributes' => [
            'class' => ['reset-list']
          ],
        ];
      }
    }
    return NULL;
  }

public function renderImage($media, $image_key = 'thumbnail', $settings = [], $data_attributes = []) {
    if ($media[$image_key]) {
      if ($this->media_settings->get('lazyload')) {
        $file = File::create([
          'uri' => $media[$image_key],
          'uid' => 1,
          'status' => FILE_STATUS_PERMANENT,
          'filename' => $media['idsId'] ?? $media['content'],
          'width' => NULL,
          'height' => NULL
        ]);
        $settings['blazy'] = TRUE;
        $settings['lazy'] = 'blazy';
        $settings['_grid'] = FALSE;
        $settings['_lazy'] = TRUE;
        $settings['uri'] = $media[$image_key];
        $settings['_uri'] = $media[$image_key];
        $settings['placeholder'] = $media[$image_key];
        $data_attributes['alt'] = $media['alt_text'];
//        $item = [
//          'item' => $file,
//          'settings' => $settings,
//          'item_attributes' => $data_attributes,
//        ];
//        $blazy_manager = \Drupal::service('blazy.manager');
//        $build =  $blazy_manager->getBlazy($item);
//        $items = ['settings' => $build['#build']['settings'], 0 => $build];
//        $build = isset($settings['namespace']) ? $build : $blazy_manager->build($items);
        $build = [
          '#theme'    => 'blazy',
          '#settings' => $settings,
          '#item_attributes' => $data_attributes,
          '#attached' => ['library' => ['blazy/load']],
        ];
        return $build;
      }
      else {
        return [
          '#theme' => 'image',
          '#uri' => $media[$image_key],
          '#alt' => $media['alt_text'],
          '#width' => NULL,
          '#height' => NULL,
          '#attributes' => $data_attributes
        ];
      }
    }
    return NULL;

}

  public function getImageInfo($record, $sizeName) {
    $items = [];

    $edanId = $record['type'] === '3d_package' ? (
    isset($record['content']['content.edanmdm']) && isset($record['content']['content.edanmdm']['descriptiveNonRepeating']) ?
      'edanmdm:'. $record['content']['content.edanmdm']['descriptiveNonRepeating']['record_ID'] : NULL)
      : $record['url'];
    if ($edanId) {
      $response = EdanIDS::fetchMedia($edanId);
      $constrain = $sizeName === 'thumbnail' ? 'max_h' : $this->media_settings->get('constrain');
      $size = $this->media_settings->get($sizeName);
      $size = $size ?? $this->media_settings->get('medium');

      if ($response) {
        foreach ($response['sequences'][0]['canvases'] as $image) {
          $idsId = trim(str_replace('image', '', $image['label']));
          $items[$idsId] = [
            'originalW' => $image['width'],
            'originalH' => $image['height'],
          ];

          $items[$idsId] += self::getAdjstedDimensions($image['width'], $image['height'], $constrain, $size);
        }
      }
    }

    return $items;
  }

  public static function getAdjstedDimensions($originalW, $originalH, $constrain, $size) {
    $aspect = $originalW / $originalH;
    $width = $height = NULL;

    switch ($constrain) {
      case 'max':
        $width = $originalW > $originalH ? $size : NULL;
        $height = $originalH > $originalW ? $size : NULL;
        break;
      case 'max_h':
        $height = $size;
        break;
      case 'max_w':
        $width = $size;
        break;
    }
    if ($width) {
      $height = ceil($width / $aspect);
    }
    if ($height) {
      $width = ceil($height * $aspect);
    }

    return ['height' => $height, 'width' => $width];
  }

  public static function svg_icons($icon = NULL) {
    $items = array(
      'cc0' => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 viewBox="0 0 76 36" style="enable-background:new 0 0 76 36;" xml:space="preserve" aria-label="Creative Commons - No Rights Reserved icon" role="img">
<circle class="svg-bg" cx="18.2" cy="18" r="15.8"/>
<path class="fill" d="M18,0.5c4.7-0.1,9.2,1.8,12.5,5.1c1.6,1.6,2.9,3.5,3.7,5.6c0.9,2.1,1.3,4.4,1.3,6.7c0,2.3-0.4,4.6-1.3,6.7
	c-0.8,2.1-2.1,4-3.7,5.5c-1.7,1.7-3.6,3-5.8,3.9c-2.1,1-4.4,1.5-6.7,1.5c-2.3,0-4.5-0.4-6.6-1.3c-2.1-0.9-4-2.2-5.7-3.8
	c-1.7-1.6-3-3.5-3.8-5.7C1,22.6,0.6,20.3,0.5,18c0-2.3,0.4-4.6,1.3-6.7C2.7,9.2,4,7.2,5.7,5.6C8.9,2.2,13.4,0.4,18,0.5z M18,3.7
	c-3.8-0.1-7.5,1.4-10.1,4.2c-1.3,1.3-2.4,2.9-3.1,4.6c-0.7,1.7-1.1,3.6-1.1,5.5c0,1.9,0.4,3.7,1.1,5.4c0.7,1.8,1.8,3.4,3.2,4.7
	c1.3,1.4,2.9,2.4,4.7,3.1c1.7,0.7,3.5,1.1,5.4,1.1c3.9,0,7.6-1.6,10.3-4.3c2.7-2.6,4.2-6.2,4.1-10c0-1.9-0.4-3.8-1.1-5.5
	c-0.7-1.7-1.7-3.3-3.1-4.6C25.6,5.2,21.9,3.6,18,3.7z M17.8,15.1l-2.3,1.2c-0.2-0.4-0.5-0.8-0.9-1.1c-0.3-0.2-0.6-0.3-1-0.3
	c-1.6,0-2.3,1-2.3,3.1c0,0.8,0.2,1.6,0.6,2.2c0.4,0.6,1.1,0.9,1.8,0.8c1,0,1.9-0.6,2.2-1.5l2.2,1.1c-0.4,0.8-1.1,1.5-1.9,2
	c-0.8,0.5-1.8,0.7-2.7,0.7c-1.4,0-2.8-0.5-3.8-1.4c-1-1.1-1.5-2.5-1.4-4c-0.1-1.5,0.5-2.9,1.5-4c1-1,2.3-1.5,3.7-1.5
	C15.3,12.5,16.9,13.5,17.8,15.1z M27.9,15.1l-2.3,1.2c-0.2-0.4-0.5-0.8-0.9-1.1c-0.3-0.2-0.6-0.3-1-0.3c-1.6,0-2.3,1-2.3,3.1
	c0,0.8,0.2,1.6,0.6,2.2c0.4,0.5,1.1,0.8,1.8,0.8c1,0,1.9-0.6,2.2-1.5l2.2,1.1c-0.4,0.8-1.1,1.5-1.9,2c-0.8,0.5-1.8,0.7-2.7,0.7
	c-1.4,0-2.8-0.5-3.8-1.4c-1-1.1-1.5-2.5-1.4-4c-0.1-1.5,0.5-2.9,1.5-4c1-1,2.3-1.5,3.7-1.5C25.4,12.5,27,13.5,27.9,15.1z"/>
<circle class="svg-bg" cx="57.9" cy="18.2" r="16.5"/>
<path class="fill text2809" d="M58,7.9c-5.8,0-7.2,5.5-7.2,10.1s1.5,10.1,7.2,10.1s7.2-5.5,7.2-10.1S63.8,7.9,58,7.9z M58,11.7
	c0.2,0,0.4,0,0.6,0.1c0.5,0.4,0.6,1,0.2,1.5L55,20.4c-0.1-0.8-0.1-1.6-0.1-2.4C54.9,16,55,11.7,58,11.7z M60.9,15c0.2,1,0.2,2,0.2,3
	c0,2-0.1,6.3-3.1,6.3c-0.2,0-0.4,0-0.6-0.1h-0.1c-0.1,0-0.1,0-0.2-0.1c-0.7-0.3-1.1-0.8-0.5-1.7L60.9,15z"/>
<path class="fill path2815" d="M58,0.5c-4.6-0.1-9.1,1.8-12.3,5.1c-1.7,1.7-3,3.6-3.9,5.8c-0.9,2.1-1.3,4.3-1.3,6.6c0,2.3,0.4,4.6,1.3,6.7
	c0.9,2.1,2.2,4.1,3.8,5.7c1.6,1.7,3.6,3,5.7,3.8c2.1,0.9,4.4,1.3,6.7,1.3c2.3,0,4.6-0.5,6.7-1.3c2.2-0.9,4.1-2.2,5.8-3.9
	c1.6-1.5,2.9-3.4,3.7-5.5c0.9-2.2,1.3-4.5,1.3-6.8c0-2.3-0.4-4.6-1.3-6.7c-0.9-2.1-2.1-4-3.7-5.6C67.2,2.3,62.7,0.4,58,0.5z M58,3.6
	c3.8-0.1,7.5,1.5,10.1,4.2c1.3,1.3,2.4,2.9,3.1,4.6c0.7,1.7,1.1,3.6,1.1,5.5c0.1,3.8-1.4,7.4-4.1,10c-1.4,1.4-3,2.4-4.8,3.2
	c-1.7,0.7-3.5,1.1-5.4,1.1c-1.9,0-3.7-0.4-5.4-1.1c-1.7-0.7-3.3-1.8-4.7-3.1c-1.4-1.3-2.4-2.9-3.2-4.7c-0.7-1.7-1.1-3.5-1.1-5.4
	c0-1.9,0.4-3.7,1.1-5.4c0.7-1.8,1.8-3.4,3.2-4.7C50.5,5.1,54.2,3.5,58,3.6z"/>
</svg>
',
      'download' => '<svg version="1.1" class="icon" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="30px" height="30px"
	 viewBox="0 0 30 30" style="enable-background:new 0 0 30 30;" xml:space="preserve" aria-label="download" role="img">
	 <path class="fill" d="M22.7,15c0.3-0.3,0.3-0.8,0-1.2l0,0c-0.3-0.3-0.8-0.3-1.1,0l0,0l-5.8,5.8V0.5c0-0.5-0.3-0.8-0.8-0.8c-0.5,0-0.8,0.3-0.8,0.8
	v19.1l-5.8-5.8c-0.3-0.3-0.8-0.3-1.2,0l0,0c-0.3,0.3-0.3,0.8,0,1.2l0,0l7.2,7.2c0.3,0.3,0.8,0.3,1.2,0C15.5,22.2,22.7,15,22.7,15z
	 M28.4,20.4c0-0.5,0.3-0.8,0.8-0.8s0.8,0.3,0.8,0.8v7.8c0,0.5-0.3,0.8-0.8,0.8H0.7c-0.5,0-0.8-0.3-0.8-0.8l0,0v-7.8
	c0-0.5,0.3-0.8,0.8-0.8s0.8,0.3,0.8,0.8v7h26.9V20.4z"/></svg>',
      'expand' => '<svg alt="Enlarge image" title="Enlarge image"  width="32px" height="32px" class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path class="fill" d="M10.204 12.052L3.806 5.656l-3.812 3.76V-.012h9.478L5.71 3.75l8.254 8.302h-3.76zM18.03 12.076l8.254-8.302L22.522.012H32V9.44l-3.81-3.76-6.398 6.396zM21.792 19.936l6.398 6.396 3.812-3.76V32h-9.48l3.762-3.762-8.252-8.302h3.76zM13.964 19.912L5.71 28.214l3.762 3.762H-.006v-9.428l3.81 3.76 6.4-6.396z"></path></svg>',
      'iiif' => '<svg class="iiif icon fill" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 viewBox="0 0 33 30" width="33px" width="30px" style="enable-background:new 0 0 33 30;" xml:space="preserve">
<path d="M15.8,4.8c0-1.9,1.3-2.9,3.2-2.4c2.3,0.6,4.3,3.3,4,5.7c-0.1,1.3-1.3,2.2-2.5,2C18.1,9.8,15.8,7.2,15.8,4.8z"/>
<polygon points="16.3,10.6 22.6,12.8 22.6,30 16.3,27.7 "/>
<path d="M15.2,4.8c0-1.9-1.3-2.9-3.2-2.4C9.7,2.9,7.8,5.6,8.1,8c0.1,1.3,1.3,2.2,2.5,2C13,9.8,15.2,7.2,15.2,4.8z"/>
<polygon points="14.8,10.6 8.5,12.8 8.5,30 14.8,27.7 "/>
<path d="M0.2,4.8c0-1.9,1.3-2.9,3.2-2.4c2.3,0.6,4.3,3.3,4,5.7c-0.1,1.3-1.3,2.2-2.5,2C2.5,9.8,0.2,7.2,0.2,4.8z"/>
<polygon points="0.7,10.6 7,12.8 7,30 0.7,27.7 "/>
<path d="M30.2,10.6V9.5c0-2.9,0.6-3.9,2.5-3.6V0C28.4,0,24,3.2,24,9.1V30l6.2-2.3V14.8l2.5,0l0-4.2L30.2,10.6z"/>
</svg>',
      'close' => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" height="35px" width="35px" viewBox="0 0 35 35" style="enable-background:new 0 0 35 35;" xml:space="preserve" aria-label="close" role="img">
									<path fill="#262626" d="M35,34c0,0.6-0.5,1-1.1,1H1.1C0.5,35,0,34.6,0,34V1.1c0-0.6,0.5-1,1.1-1h32.9c0.6,0,1.1,0.5,1.1,1V34z"/>
									<g>
										<path fill="none" stroke="#FFFFFF" stroke-width="2" stroke-miterlimit="10" d="M24.3,11L10.7,24.2 M10.9,10.8l13.1,13.5"/>
									</g>
								</svg>',
      'zoomIn' => '<svg version="1.1" class="icon" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="35px" height="35px" viewBox="0 0 35 35" style="enable-background:new 0 0 35 35;" xml:space="preserve" aria-label="Zoom In" role="img">
<path class="svg-bg" d="M35,34c0,0.6-0.5,1-1.1,1H1.1C0.5,35,0,34.5,0,34V1c0-0.6,0.5-1,1.1-1H34c0.5,0,1,0.5,1,1V34z"/>
<g>
	<path fill="none" class="stroke" stroke-width="2" stroke-miterlimit="10" d="M17.5,8.1V27 M8.1,17.5h18.8"/>
</g>
</svg>',
      'zoomOut' => '<svg version="1.1" class="icon" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="35px" height="35px" viewBox="0 0 35 35" style="enable-background:new 0 0 35 35;" xml:space="preserve" aria-label="Zoom Out" role="img">
<path class="svg-bg"  d="M35,34c0,0.6-0.5,1-1.1,1H1.1C0.5,35,0,34.5,0,34V1c0-0.6,0.5-1,1.1-1H34c0.5,0,1,0.5,1,1V34z"/>
<path fill="none" class="stroke" stroke-width="2" stroke-miterlimit="10" d="M8.1,17.5h18.8"/>
</svg>',
      'home' => '<svg version="1.1" class="home-icon icon" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="35px" height="35px" viewBox="0 0 35 35" style="enable-background:new 0 0 35 35;" xml:space="preserve" aria-label="home" role="img">
<polygon class="fill" points="28.3,14.2 28.3,5.8 23.8,5.8 23.8,10.5 17.5,5.6 0,19.6 4.2,19.6 17.5,8.8 31,19.6 35,19.6 "/>
<polygon class="fill" points="17.5,10.3 6.1,19.6 6.1,29.4 15.2,29.4 15.2,22.2 20,22.2 20,29.4 29,29.4 29,19.6 "/>
</svg>',
      'rotate' => '<svg version="1.1" class="icon" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="35px" height="35px" viewBox="0 0 35 35" style="enable-background:new 0 0 35 35;" xml:space="preserve" aria-label="rotate" role="img">
<path class="svg-bg" d="M35,34c0,0.6-0.5,1-1.1,1H1.1C0.5,35,0,34.5,0,34V1c0-0.6,0.5-1,1.1-1h32.9C34.5,0,35,0.5,35,1V34z"/>
<path class="fill" d="M24,11.7C22.5,10,20.2,9,17.7,9c-5,0-9,4.1-9,9c0,5,4.1,9,9,9c4.2,0,7.7-2.9,8.7-6.8H24
	c-0.9,2.6-3.4,4.5-6.3,4.5c-3.7,0-6.8-3-6.8-6.8c0-3.7,3.1-6.8,6.8-6.8c1.9,0,3.5,0.8,4.7,2l-3.6,3.6h7.9V9C26.8,9,24,11.7,24,11.7z
	"/>
</svg>',
      'arrow' => '<svg version="1.1" class="icon" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="35px" height="35px" viewBox="0 0 35 35" style="enable-background:new 0 0 35 35;" xml:space="preserve">
									<path class="svg-bg" d="M35,34c0,0.6-0.5,1-1.1,1H1.1C0.5,35,0,34.5,0,34V1c0-0.6,0.5-1,1.1-1H34c0.5,0,1,0.5,1,1V34z"/>
									<path class="fill" d="M22.6,16.3c0.7,0.7,0.7,1.8,0,2.5c0,0-7.8,7.7-7.8,7.7c-0.7,0.7-1.8,0.7-2.5,0c-0.7-0.7-0.7-1.8,0-2.5l6.5-6.5
										l-6.4-6.4c-0.7-0.7-0.7-1.8,0-2.5c0.7-0.7,1.8-0.7,2.5,0L22.6,16.3"/>
								</svg>',
      'pdf' => '<svg version="1.1" class="icon" xmlns="http://www.w3.org/2000/svg" width="38px" height="50px" x="0px" y="0px" viewBox="0 0 384 512" style="enable-background:new 0 0 384 512;" xml:space="preserve" aria-label="pdf" role="img">
							<path d="M369.9,97.9L286,14c-9-9-21.2-14.1-33.9-14.1H48C21.5,0,0,21.5,0,48v416c0,26.5,21.5,48,48,48h288c26.5,0,48-21.5,48-48
								V131.9C384,119.2,378.9,106.9,369.9,97.9z M332.1,128H256V51.9L332.1,128z M48,464V48h160v104c0,13.3,10.7,24,24,24h104v288H48z"/>
							<path fill="#BE1512" d="M298.2,320.3c-12.2-12-47-8.7-64.4-6.5c-17.2-10.5-28.7-25-36.8-46.3c3.9-16.1,10.1-40.6,5.4-56
								c-4.2-26.2-37.8-23.6-42.6-5.9c-4.4,16.1-0.4,38.5,7,67.1c-10,23.9-24.9,56-35.4,74.4c-20,10.3-47,26.2-51,46.2
								c-3.3,15.8,26,55.2,76.1-31.2c22.4-7.4,46.8-16.5,68.4-20.1c18.9,10.2,41,17,55.8,17C306.2,359,308.7,330.8,298.2,320.3z
								 M100.1,398.1c5.1-13.7,24.5-29.5,30.4-35C111.5,393.4,100.1,398.8,100.1,398.1z M181.7,207.5c7.4,0,6.7,32.1,1.8,40.8
								C179.1,234.4,179.2,207.5,181.7,207.5z M157.3,344.1c9.7-16.9,18-37,24.7-54.7c8.3,15.1,18.9,27.2,30.1,35.5
								C191.3,329.2,173.2,338,157.3,344.1z M288.9,339.1c0,0-5,6-37.3-7.8C286.7,328.7,292.5,336.7,288.9,339.1z"/>
							</svg>',
      'file' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 384 512"><path d="M369.9 97.9L286 14C277 5 264.8-.1 252.1-.1H48C21.5 0 0 21.5 0 48v416c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48V131.9c0-12.7-5.1-25-14.1-34zM332.1 128H256V51.9l76.1 76.1zM48 464V48h160v104c0 13.3 10.7 24 24 24h104v288H48z"/></svg>',
      'link' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="50px" height="50px" viewBox="0 0 512 512"><path d="M432,320H400a16,16,0,0,0-16,16V448H64V128H208a16,16,0,0,0,16-16V80a16,16,0,0,0-16-16H48A48,48,0,0,0,0,112V464a48,48,0,0,0,48,48H400a48,48,0,0,0,48-48V336A16,16,0,0,0,432,320ZM488,0h-128c-21.37,0-32.05,25.91-17,41l35.73,35.73L135,320.37a24,24,0,0,0,0,34L157.67,377a24,24,0,0,0,34,0L435.28,133.32,471,169c15,15,41,4.5,41-17V24A24,24,0,0,0,488,0Z"/></svg>',
      'list' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon" viewBox="0 0 115.43 91.77"><title>list-with-dots</title><rect x="32" y="76.13" width="83.43" height="6.26" rx="3.13" ry="3.13"/><path d="M13.3,91.88a12.08,12.08,0,0,1-8.86-3.65,12.55,12.55,0,0,1,0-17.72,12.15,12.15,0,0,1,8.86-3.65,12.15,12.15,0,0,1,8.86,3.65,12.58,12.58,0,0,1,0,17.72A12.08,12.08,0,0,1,13.3,91.88Zm0-20.18a7.37,7.37,0,0,0-5.44,2.23,7.73,7.73,0,0,0,0,10.88A7.36,7.36,0,0,0,13.3,87a7.36,7.36,0,0,0,5.44-2.23,7.75,7.75,0,0,0,0-10.88A7.37,7.37,0,0,0,13.3,71.7Z" transform="translate(-0.78 -0.12)" class="fill" fill="#231f20"/><rect x="32" y="42.76" width="83.43" height="6.26" rx="3.13" ry="3.13"/><path d="M13.3,58.51a12.12,12.12,0,0,1-8.86-3.65,12.55,12.55,0,0,1,0-17.72,12.11,12.11,0,0,1,8.86-3.65,12.11,12.11,0,0,1,8.86,3.65,12.58,12.58,0,0,1,0,17.72A12.12,12.12,0,0,1,13.3,58.51Zm0-20.18a7.37,7.37,0,0,0-5.44,2.23,7.73,7.73,0,0,0,0,10.88,7.36,7.36,0,0,0,5.44,2.23,7.36,7.36,0,0,0,5.44-2.23,7.75,7.75,0,0,0,0-10.88A7.37,7.37,0,0,0,13.3,38.33Z" transform="translate(-0.78 -0.12)" class="fill" fill="#231f20"/><rect x="32" y="9.39" width="83.43" height="6.26" rx="3.13" ry="3.13"/><path d="M13.3,25.14a12.12,12.12,0,0,1-8.86-3.65A12.12,12.12,0,0,1,.78,12.63,12.09,12.09,0,0,1,4.44,3.77,12.11,12.11,0,0,1,13.3.12a12.14,12.14,0,0,1,8.86,3.64,12.15,12.15,0,0,1,3.65,8.87,12.12,12.12,0,0,1-3.65,8.86A12.12,12.12,0,0,1,13.3,25.14ZM13.3,5A7.37,7.37,0,0,0,7.86,7.19a7.35,7.35,0,0,0-2.24,5.44,7.38,7.38,0,0,0,2.24,5.44A7.36,7.36,0,0,0,13.3,20.3a7.36,7.36,0,0,0,5.44-2.23A7.34,7.34,0,0,0,21,12.63a7.34,7.34,0,0,0-2.23-5.44A7.37,7.37,0,0,0,13.3,5Z" transform="translate(-0.78 -0.12)" class="fill" fill="#231f20"/></svg>'

    );
    return $icon ? $items[$icon] : $items;
  }
}
