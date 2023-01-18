<?php

namespace Drupal\edan_record;

use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\edan\EdanManagerBase;

/**
 * Class EdanRecordProcess.
 */
class EdanRecordProcess extends EdanManagerBase implements EdanRecordProcessInterface {
  protected $idsUrl;

  protected $record_settings = [];

  protected $teaser_view;

  public function setEdanRecordSettings() {
    $this->record_settings = $this->configFactory->get('edan_record.settings');
    $this->idsUrl =  \Drupal::request()->getScheme(). '://ids.si.edu/ids/deliveryService';
    return $this;
  }

  public function setTeaserView($is_teaser = FALSE) {
    $this->teaser_view = $is_teaser;
  }

  /**
   * @param $doc
   * returns normalized record with title, guid and metadata_usage
   */
  public function constructData(&$doc, $teaser = FALSE) {
    $doc['title'] = $this->getTitle($doc);
    $doc['title_plain'] = strip_tags($doc['title']);
    $doc['content']['metadata_usage'] = [];
    if (isset($doc['content']['usage'])) {
      $usage = $doc['content']['usage'];
      unset($doc['content']['usage']);
    }
    else {
      $usage = $doc['content']['descriptiveNonRepeating']['metadata_usage'] ?? $doc['content']['metadata_usage'] ?? NULL;
    }

    $guid = $doc['content']['descriptiveNonRepeating']['guid'] ?? $doc['content']['guid'] ?? NULL;
    $record_id = $doc['content']['descriptiveNonRepeating']['record_ID'] ?? $doc['content']['record_ID'] ?? $doc['id'];
    if ($usage) {
      $usage['content'] = $usage['access'];
      $usage['label'] =   'Metadata Usage';
      $doc['content']['metadata_usage'] = [$usage];
    }
    if ($guid) {
      $guid = str_replace('"','', $guid);
      $link = Link::fromTextAndUrl($guid, Url::fromUri($guid, ['attributes' => ['target' => '_blank']]));
      $doc['content']['guid'] = [
        [
          'label' => 'GUID',
          'content' => $link->toRenderable(),
          // 'content' => '<a href="'. $guid .'">'. $guid .'</a>',
          'guid' => $guid
        ]
      ];
    }
    $doc['content']['record_Id'] = [
      [
        'label' => 'Record ID',
        'content' => $record_id,
      ]
    ];
    if ($doc['type'] === 'event') {
      $this->processEvent($doc);
    }
    if ($doc['type'] === 'location') {
      $this->processLocation($doc);
    }
    $alt_records = $this->record_settings->get('alt_template') ?? [];
    $doc['alt_template'] = in_array($doc['type'], $alt_records);
  }

  /**
   * @param $doc
   * returns processed record media and fields array based on EdanRecord entity settings
   */
  public function processData(&$doc, $settings, $teaser = FALSE) {
    $this->setTeaserView($teaser);
    $this->constructData($doc);
    $doc['flatArray'] = $this->record_array_flatten($doc['content']);
    $this->getDescription($doc, $settings['summary_fields']);
    if ($doc['type'] == 'page') $doc['flatArray']['description'] = $doc['description'];
    $this->getMedia($doc);

    $link = $this->getRecordLink($settings['path_key'], $doc, $settings['path']);
    //$doc['record_link'] = $doc['type'] === 'page' ? $this->getRecordLink($settings['path_key'], $doc) : $this->getRecordLink($settings['path_key'], $doc);
    $doc['record_link'] = str_replace('%3A', ':', $link->toString());
    $doc['urlObject'] = $link;
    $this->getFields($doc, $settings);
    $doc['attributes'] = [
      'class' => [
        $doc['id'],
        $doc['thumbnail'] ? 'has-media' : 'no-media'
      ]
    ];
    if (isset($settings['mini_fields'])) {
      $doc['attributes']['aria-live'] = 'polite';
    }

    $this->moduleHandler->alter('edan_record', $doc);
  }

  private function processEvent(&$doc) {
    $status = $short_date = $full_date = '';
    $future_date = new DrupalDateTime('+31 days');
    $content = $doc['content'];
    $event_status = strtolower($content['event_status']['content']);
    // $unique_id = _siedu_custom_exhibition_url_from_data($doc);

    if ($event_status === 'onview' && !empty($content['close']['extended']['closing_date'])) {
      $close_date =  new DrupalDateTime($content['close']['extended']['closing_date']);
      $status = $close_date->diff($future_date)->days < 30 ? '<span class="callout">' . t('Closing Soon') . '</span>' : '';
    }
    $doc['content']['location']['extended']['address'] = array();
    $doc['content']['location']['extended']['address']['full_address'] = NULL;
    $doc['content']['location']['formatted_location'] = empty($content['location']['extended']['building']) ? '' : '<span class="location">' . $content['location']['extended']['building'] . '</span>';
    $doc['content']['status_tag'] = $status;

    $openLen = strlen($content['open']['content']);
    $closeLen = strlen($content['close']['content']);
    $divider = $closeLen > 0 ? (stristr($content['close']['content'], 'through') ? ' ' : ' &ndash; ') : '';

    if ($closeLen === 0 && $openLen > 0) {
      $short_date = $event_status === 'upcoming' ? t('Opens ') . $content['open']['content'] : t('Opened ') . $content['open']['content'];
    }
    elseif ($openLen > 0) {
      $full_date = $content['open']['content'] . $divider . $content['close']['content'];

      $is_date = strtotime($content['close']['content']);
      if (strlen($is_date) > 0) {
        $short_date = $content['open']['content'];
        $short_date .= $openLen > 0 ? $divider . $content['close']['content'] : $content['close']['content'];
      }
      else {
        $short_date = $event_status === 'upcoming' ? t('Opens ') . $content['open']['content'] : $content['open']['content'] . $divider . $content['close']['content'];
      }
    }
    $doc['content']['short_date'] = $short_date;
    $doc['content']['full_date'] = $full_date;

    if (strlen($content['location']['extended']['location_id']) > 1 && !$this->teaser_view) {
      $args = array(
        'fqs' => array(
          'id:' . $content['location']['extended']['location_id']
        ),
      );
      $result = $this->edanConnector->runParamQuery($args);
      if ($result['data']['recordCount'] > 0) {
        $location = $result['data']['rows'][0];
        $doc['content']['location']['extended']['address']['short_name'] = $location['content']['title_shortname'];
        $doc['content']['location']['extended']['address']['location_name'] = $location['title'];
        if (isset($location['content']['address'])) {
          $doc['content']['location']['extended']['address'] += $location['content']['address']['extended'];
          $location_address = $location['content']['address']['extended'];
          $address = '';
          $address1 = isset($location_address['street_address']) && strlen(trim($location_address['street_address'])) > 0 ?
            $location_address['street_address'] : '';
          $address1 .= isset($location_address['street_address2']) && strlen(trim($location_address['street_address2'])) > 0 ?
            '<br />' / $location_address['street_address2'] : '';
          $address2 = strlen(trim($location_address['city'])) > 0 ?
            $location_address['city'] . ', ' : '';
          $address2 .= isset($location_address['state']) &&
          strlen(trim($location_address['state'])) > 0 ?
            $location_address['state'] : '';
          $address2 .= isset($location_address['postalcode']) &&
          strlen(trim($location_address['postalcode'])) > 0 ?
            ' ' . $location_address['postalcode'] : '';
          $address = strlen($address1) > 0 ? $address1 . '<br />' : '';
          $address .= strlen($address2) > 0 ? $address2 : '';
          $doc['content']['location']['extended']['address']['full_address'] = $address;
          $doc['content']['location']['formatted_location'] = $doc['content']['location']['formatted_location'] . '<span class="address">'. $address .'</span>';
          $location_entity = $this->entityLoad($location['type']);
          $record_settings = $location_entity ? $location_entity->getRecordSettings() : [];
          $url = isset($record_settings['path']) ? $this->getRecordLink($record_settings['path_key'], $location, $record_settings['path']) : $this->getRecordLink(NULL, $location);
          $location['record_link'] = $url->toString();
          $this->processLocation($location);
          $doc['content']['location']['record'] = $location;
        }
      }
    }

  }

  private function processLocation(&$doc) {
    foreach($doc['content']['online_media'] as $media) {
      if ($media['description'] === 'icon') {
        $doc['content']['logo'] = $media['content'];
      }
      elseif($media['description'] === 'floor plan') {
        $doc['content']['floor_plan'] = $media['content'];
      }
    }
  }
  public function getDefault() {
    return [
      'summary_fields' => ['summary'],
      'fields' => ['*' => '*'],
      'hidden_fields' => [],
      'label_replacements' => [],
      'path_key' => NULL,
      'path' => NULL
    ];
  }
  public function getTitle($doc) {
    if (NULL == $doc) {
      return '';
    }
    // Process through all record types.
    $type = $doc['type'] ?? '';
    $doc['type'] = $type;
    $title = NULL;
    switch ($type) {
      case 'damsmdm':
        // damsmdm records can be a challenge.
        $title = isset($doc['content']['title']) ? (is_array($doc['content']['title']) ? implode(', ', $doc['content']['title']) : $doc['content']['title']) : NULL;
        $title = (empty($title) && isset($doc['content']['collection_title'])) ? $doc['content']['collection_title'] : $title;
        $title = (empty($title) && isset($doc['content']['title'])) ? $doc['content']['title'] : $title;
        $title = (empty($title) && isset($doc['content']['IPTC_headline'])) ? $doc['content']['IPTC_headline'] : $title;
        $title = (empty($title) && isset($doc['content']['keywords'])) ? preg_replace('/(\.)([[:alpha:]]{2,})/', '$1 $2', $doc['content']['keywords']) : $title;
        break;
      case 'edanmdm':
      case 'edanauth':
        $title = isset($doc['content']['descriptiveNonRepeating']) ? $doc['content']['descriptiveNonRepeating']['title']['content'] : $doc['title'];
        break;
      case 'concept':
      case 'objectgroup':
        $title = isset($doc['content']['title']) ? $doc['content']['title'] : $doc['title'];
        break;
      case 'edanead':
        $title = ucwords($doc['content']['coll_unittitle']);
        break;
      case 'transasset':
      case 'transproject':
        $title = $doc['content']['projectName'];
        break;
      case 'museum':
        $title = $doc['title'];
        break;
      case '3d_model':
      case '3d_tour':
      case 'location':
      case 'si-unit':
        //@todo temp
        $title = $doc['content']['title']['content'] ?? $doc['content']['title'] ?? NULL;
        break;
      case '3d_package';
        $title = $doc['title'] ? $doc['title'] : (!empty($doc['content']['content.edanmdm']) ? $doc['content']['content.edanmdm']['descriptiveNonRepeating']['title']['content'] : NULL);
        break;
      case 'ecr':
      case 'event':
        $title = $doc['content']['title']['content'];
        break;
      case 'page':
        $title = !isset($doc['content']['page']) || (isset($doc['content']['defaultPageId']) && $doc['id'] === $doc['content']['defaultPageId']) ? $doc['content']['title'] : $doc['content']['page']['title'];
        break;
      case 'nmaahc_fb':
        $title = $doc['content']['image_title'];
        break;
      default:
        $title = '';
        if (is_array($doc)) {
          if (isset($doc['content'])) {
            if (isset($doc['content']['descriptiveNonRepeating']) &&
              isset($doc['content']['descriptiveNonRepeating']['title']['content'])) {
              $title = $doc['content']['descriptiveNonRepeating']['title']['content'];
            }
            elseif (isset($doc['content']['title'])) {
              $title = is_string($doc['content']['title']) ? $doc['content']['title'] : (isset($doc['content']['title']['content']) ? $doc['content']['title']['content'] : '');
            }
            elseif (isset($doc['title'])) {
              $title = $doc['title'];
            }
          }
        }
    }

    // Remove brackets from titles.
    $title = str_replace(['[', ']'], '', $title);
    $title = htmlspecialchars_decode($title);
    return $title;
  }

  /**
   * Get Media and construct
   * thumbnail, medium, large derivatives
   *
   * adds
   *
   */
  public function getMedia(&$doc) {
    if (NULL == $doc) {
      return '';
    }
    $image_array = $items = [];
    $type = isset($doc['type']) ? $doc['type'] : '';
    $fileMap = $this->fileMap();
    $doc['thumbnail'] = NULL;
    switch ($type) {
      case 'damsmdm':
        if (isset($doc['content']['uan']) && !empty($doc['content']['uan'])) {
          $image_array[] = array(
            'content' => $this->idsUrl. '?id=' . $doc['content']['uan'],
            'idsId' => $doc['content']['uan'],
            'type' => 'Images',
            'text_equiv' => isset($doc['content']['text_equiv']) ? $doc['content']['text_equiv'] : ''
          );
        }
        break;
      case 'edanmdm':
      case 'edanauth':
        $image_array = $doc['content']['descriptiveNonRepeating']['online_media']['media'] ?? [];
        break;
      case 'objectgroup':
        $image_array = $this->getOGMTMedia($doc);
        break;
      case 'page':
        $page = ['content' => $doc['content']['page']];
        $image_array = $this->getOGMTMedia($page);

        break;
      case 'museum':
        if (isset($doc['content']['image'])) {
          $image_array[] = array(
            'content' => $doc['content']['image'],
            'title' => $doc['title'],
          );
        }
        break;
      case '3d_model':
      case '3d_tour':
      case 'ecr':
      case 'event':
      case 'location':
      case 'si-unit':
//          //@todo temp
        $image_array = $doc['content']['online_media'] ?? [];
        if ($type == 'location') {
          $images = $image_array;
          foreach ($images as $i => $image) {
            if ($image['description'] == 'image') {
//							$main = $image;
//							unset($images[$i]);
              $image_array = [$image];
              break;
            }
          }
          //$image_array = isset($main) ? array_merge(array($main), $images) : $images;
        }
        break;
      case '3d_package':
        // show 3d model first
        $item = array(
          'content' => '//3d-api.si.edu/voyager/' . $doc['content']['voyagerId'],
          'type' => $type,
          'text_equiv' => '3d model of ' . $doc['title'],
          'voyagerId' => $doc['content']['voyagerId'],
          'usage' => $doc['content']['metadata_usage'][0],
          'idsId' => $doc['content']['voyagerId'],
          'caption'  =>  $doc['content']['usage']['text'] ?? NULL
        );
        if (isset($doc['content']['thumbnail'])) {
          $item['thumbnail'] = $doc['content']['thumbnail'];
        }
        $image_array[] = $item;
        if (isset($doc['content']['content.edanmdm']) && isset($doc['content']['content.edanmdm']['descriptiveNonRepeating']['online_media']['media'])) {
          foreach ($doc['content']['content.edanmdm']['descriptiveNonRepeating']['online_media']['media'] as $image) {
            if (!empty($image) && $image['type'] !== '3d_voyager') {
              $image_array[] = $image;
            }
          }
        }
        break;
      default:
        $image_array = isset($doc['content']['online_media']) && isset($doc['content']['online_media']['media']) ? $doc['content']['online_media']['media'] : (isset($doc['content']['media']) ? [$doc['content']['media']] : []);
        break;
    }
    if ($image_array) {
      $has_thumbnail = FALSE;
      foreach ($image_array as $key => $media) {
        $media['alt_text'] = $media['text_equiv'] ?? $media['alt'] ?? '';
        $media['record_title'] = htmlspecialchars($doc['title']);
        $media['type'] = !isset($media['type']) ? 'Images' : $media['type'];
        $media['has_ids'] = FALSE;
        // If there is no idsId, go for the thumbnail.
        // Stitch it all together.
        if (strstr($media['content'], 'mads.si.edu/assets/player.html')) {
          $media['type'] = 'Video embed';
          if ($media['thumbnail'] == $media['content']) {
            unset($media['thumbnail']);
            if (isset($media['idsId'])) {
              unset($media['idsId']);
            }
          }
        }
        switch ($media['type']) {
          case 'Youtube videos':
            $parsedData = self::parseValue($media);
            if ($parsedData && isset($parsedData['id'])) {
              $media['thumbnail'] = '//img.youtube.com/vi/' . $parsedData['id'] . '/mqdefault.jpg';
              $media['medium'] = '//img.youtube.com/vi/' . $parsedData['id'] . '/hqdefault.jpg';
              $media['large'] = '//img.youtube.com/vi/' . $parsedData['id'] . '/sddefault.jpg';
            }
            if (!$has_thumbnail) {
              $doc['thumbnail'] = $media;
              $has_thumbnail = TRUE;
            }
            $media['has_ids'] = FALSE;
            $items[] = $media;
            break;
          case 'slideshowXML':
            $xml = simplexml_load_file($media['content']);
            //dpm($xml);
            foreach ($xml as $i => $item) {
              $caption = isset($item->caption) && !empty($item->caption) ? (string) $item->caption : NULL;
              $path = (string) $item->NAME;
              $img_info = [
                'type' => 'Images',
                'content' => $path,
                'medium' => $path,
                'large' => $path,
                'thumbnail' => $path,
                'caption' => $caption,
                'has_ids' => FALSE,
                'alt_text' => $item['text_equiv'] ?? $item['alt'] ?? '',
                'usage' => $media['usage'] ?? []
              ];
              $parsedData = self::parseValue($img_info);
              if ($parsedData && $parsedData['type'] === 'ids') {
                $img_info['idsId'] = $parsedData['id'];
                $img_info['has_ids'] = TRUE;
                $img_info['content'] = $this->idsUrl . '?id=' . $parsedData['id'];
                $this->imageSizes($img_info);
                if (!$has_thumbnail) {
                  $doc['thumbnail'] = $img_info;
                  $has_thumbnail = TRUE;
                }
              }
              $items[] = $img_info;
            }
            break;
          case '3d_voyager':
          case '3d_package':
            $media['idsId'] = $media['voyagerId'];
            $media['alt_text'] = '3d model of ' . $doc['title'];
            $media['type'] = '3d_package';
            //$this->imageSizes($media);
            if (!$has_thumbnail) {
              $doc['thumbnail'] = $media;
              $has_thumbnail = TRUE;
            }
            $media['has_ids'] = TRUE;

            $items[$media['idsId']] = $media;
            break;
          case 'URL':
            if (strstr($media['content'], 'google.com/map')) {
              $media['content'] = str_replace('http://', 'https://', $media['content']);
              $media['type'] = 'map';
            }
            $items[] = $media;
            break;
          default:
            if (strstr($media['content'], 'youtube') || strstr($media['content'], 'youtu.be')) {
              $media['type'] = 'Youtube videos';
              $parsedData = self::parseValue($media);
              if ($parsedData && isset($parsedData['id'])) {
                $media['thumbnail'] = '//img.youtube.com/vi/' . $parsedData['id'] . '/mqdefault.jpg';
                $media['medium'] = '//img.youtube.com/vi/' . $parsedData['id'] . '/hqdefault.jpg';
                $media['large'] = '//img.youtube.com/vi/' . $parsedData['id'] . '/sddefault.jpg';
              }
            }
            elseif (isset($media['idsId'])) {
              $this->imageSizes($media);
              $media['has_ids'] = TRUE;
              if (!$has_thumbnail) {
                $doc['thumbnail'] = $media;
                $has_thumbnail = TRUE;
              }
            }
            elseif (isset($media['thumbnail'])) {
              // ensure that if thumbnail is an ids image that id is passed
              $clone = $media;
              $clone['content'] = $media['thumbnail'];
              $parsedData = self::parseValue($clone);
              if ($parsedData && $parsedData['type'] === 'ids') {
                if (empty($parsedData['id'])) {
                  $media['thumbnail'] = $media['medium'] = $media['large'] = NULL;
                }
                else {
                  $media['idsId'] = $parsedData['id'];
                  $media['has_ids'] = TRUE;

                  $this->imageSizes($media);
                  if (!$has_thumbnail) {
                    $doc['thumbnail'] = $media;
                    $has_thumbnail = TRUE;
                  }
                }
              }
              else {
                if (strpos($media['content'], 'http') === FALSE && isset($fileMap[$doc['unitCode']])) {
                  $media['content'] = $fileMap[$doc['unitCode']] . $media['content'];
                }
                if (strpos($media['thumbnail'], 'http') === FALSE && isset($fileMap[$doc['unitCode']])) {
                  $media['thumbnail'] = $fileMap[$doc['unitCode']] . $media['thumbnail'];
                }
                $media['medium'] = $media['large'] = $media['thumbnail'] ?? $media['content'];
                $media['has_ids'] = FALSE;
                if (!$has_thumbnail) {
                  $doc['thumbnail'] = $media;
                  $has_thumbnail = TRUE;
                }
              }
            }
            else {
              $media['has_ids'] = FALSE;
              if ($media['type'] === 'Images') {
                $media['thumbnail'] = $media['medium'] = $media['large'] = $media['content'];
                if (!$has_thumbnail) {
                  $doc['thumbnail'] = $media;
                  $has_thumbnail = TRUE;
                }
              }

            }
            if (isset($media['caption']) && $media['caption'] === $media['content']) {
              unset($media['caption']);
            }
            $items[] = $media;
            break;
        }

      }
    }
    $doc['media'] = $items;
  }


  protected function imageSizes(&$media) {
    $thumbnail = $this->record_settings->get('thumbnail') ? $this->record_settings->get('thumbnail') : 200;
    $medium = $this->record_settings->get('medium') ? $this->record_settings->get('medium') : 600;
    $large = $this->record_settings->get('large') ? $this->record_settings->get('large') : 980;
    $constrain = $this->record_settings->get('constrain') ? $this->record_settings->get('constrain') : 'max';
    $id = $media['idsId'];
    if ($id) {
      $query = [
        'id' => $id,
        'max_h' => $thumbnail
      ];
      $url = Url::fromUri($this->idsUrl, ['query' => $query]);
      $media['thumbnail'] = $url->toString();
      $query = [
        'id' => $id,
        $constrain => $medium
      ];
      $url->setOption('query', $query);
      $media['medium'] = $url->toString();
      $query[$constrain] = $large;
      $url->setOption('query', $query);
      $media['large'] = $url->toString();
    }


  }
  protected function getOGMTMedia($doc) {
    $image_array = [];
    if (isset($doc['content']['feature']['thumbnail']) && UrlHelper::isValid($doc['content']['feature']['thumbnail'])) {
      $img_info = [
        'type' => 'Images',
        'content' => $doc['content']['feature']['thumbnail'],
        'thumbnail' => $doc['content']['feature']['thumbnail']
      ];
      $parsedData = self::parseValue($img_info);
      if ($parsedData && $parsedData['type'] === 'ids') {
        $img_info['idsId'] = $parsedData['id'];
        $img_info['content'] = $this->idsUrl . '?id=' . $parsedData['id'];
      }
      $image_array[] = $img_info;
    }
    if (isset($doc['content']['feature']['url']) && UrlHelper::isValid($doc['content']['feature']['url'])) {
      $img_info = [
        'type' => 'Images',
        'content' => $doc['content']['feature']['url'],
        'thumbnail' => $doc['content']['feature']['url']
      ];
      $parsedData = self::parseValue($img_info);

      if ($parsedData && $parsedData['type'] === 'ids') {
        $img_info['idsId'] = $parsedData['id'];
        $img_info['content'] = $this->idsUrl . '?id=' . $parsedData['id'];
      }
      $image_array[] = $img_info;

    }
    if (isset($doc['content']['feature']['media']) && UrlHelper::isValid($doc['content']['feature']['media'])) {
      $img_info = [
        'type' => 'Images',
        'content' => $doc['content']['feature']['media'],
        'thumbnail' => $doc['content']['feature']['media']
      ];
      $parsedData = self::parseValue($img_info);

      if ($parsedData && $parsedData['type'] === 'ids') {
        $img_info['idsId'] = $parsedData['id'];
        $img_info['content'] = $this->idsUrl . '?id=' . $parsedData['id'];
      }
      $image_array[] = $img_info;
    }

    return $image_array;
  }

  public static function parseValue($media) {
    $parsed = UrlHelper::parse($media['content']);
    $item = $parsed;
    if ($media['type'] == 'Youtube videos') {
      if (isset($parsed['query']['q'])) {
        $item['id'] = $parsed['query']['q'];
      }
      elseif (isset($parsed['query']['v'])) {
        $item['id'] = $parsed['query']['v'];
      }
      $item['type'] = 'youtube';
    }
    elseif (strstr($media['content'], 'ids.si.edu/ids') && isset($parsed['query']['id'])) {
      $item['id'] = $parsed['query']['id'];
      $item['type'] = 'ids';
    }
    else {
      $item['type'] ='other';
    }
    return $item;
  }

  protected function fileMap() {
    return [
      'NASM' => '//airandspace.si.edu',
      'NPG' => '//npg.si.edu'
    ];
  }

  /**
   * Flattens an array, or returns FALSE on fail.
   */
  public static function record_array_flatten($array, $parent = '') {
    $result = [];
    $exclude = [
      'indexedStructured',
      'descriptiveNonRepeating',
      'thumbnail',
      'online_media',
      'filelocation',
      'digital_assets_available',
      'level',
      'language',
      'title',
      'boost',
      'type',
      'fileSignature'
    ];
    foreach ($array as $key => $value) {
      if (!in_array($key, $exclude)) {
        $key = str_replace('.', ':', $key);
        $new_key = $parent == '' ? $key : $parent . ':' . $key;
        if (is_array($value)) {
          if (array_key_exists(0, $value)) {
            $result[$new_key] = $value;
          }
          else {
            $result = array_merge($result, self::record_array_flatten($value, $new_key));
          }
        }
        else {
          $result[$new_key] = $value;
        }
      }
    }
    return $result;
  }

  private function getDescription(&$doc, $fields) {
    $summary = $description = NULL;
    if ($doc['type'] === 'page' && isset($doc['content']['page'])) {
      $desc_array = [
        $doc['content']['page']['content']
      ];
    }
    if (!empty($fields)) {
      $desc_array = array();
//      if ($doc['type'] === 'objectgroup') {
//        $desc_array = [
//          $doc['content']['description']
//        ];
//      }

//      else {
      foreach ($fields as $field) {
        $data = $doc['flatArray'];
        if (isset($data[$field])) {
          if (is_array($data[$field])) {
            $text = '';
            foreach ($data[$field] as $item) {
              $text .= isset($item['content']) ? $item['content'] : '';
            }
            if (strlen($text) > 1) {
              $desc_array[$field] = $text;
            }
          }
          else {
            $desc_array[$field] = $data[$field];
          }
        }
      }
      //}

    }
    if (!empty($desc_array)) {
      $description = implode('\n', $desc_array);
    }

    if ($description) {
      $summary = strip_tags($description, 'i');
      $summary = text_summary($summary, NULL, 280);
    }
    $doc['description'] = $description;
    $doc['teaser'] = $summary;

  }

  /**
   * @param array $labels
   *   An associative array containing metadata field labels mapped to an
   *   alternate label. For example: ['Credit Line' => 'Attribution'])
   */
  private function formatLabels(&$vals, $labels) {
    // do not continue if no labels are provided
    if (empty($labels)) {
      return;
    }
    if (is_iterable($vals)) {
      foreach ($vals as $key => $val) {
        if (isset($val['label'])) {
          if (isset($labels[$val['label']])) {
            $vals[$key]['label'] = $labels[$val['label']];
          }
        }
      }
    }
  }

  public function getFields(&$doc, $settings) {
    $fields = isset($settings['fields']) ? $settings['fields'] : [];
    $hide = isset($settings['hidden_fields']) ? $settings['hidden_fields'] : [];
    $mini = isset($settings['mini_fields']) ? $settings['mini_fields'] : [];
    $labels = isset($settings['label_replacements']) ? $settings['label_replacements'] : [];
    $new_ft = $used_fields = $new_content = $mini_fields = [];
    foreach ($doc['flatArray'] as $key => $values) {
      if ($values) {
        switch ($key) {
          case 'record_link':
            if ($doc['type'] === 'event') {
              $values = strlen($values) > 0 ? [
                '#type' => 'link',
                '#title' => [
                  '#markup' => t('Learn More') . '<span class="visually-hidden">' . t('about @title', ['@title' => $doc['title']]) . '</span>'
                ],
                '#url' => Url::fromUri($values)
              ] : $values;
            }
            break;
          case 'online_exhibit':
            $values =  strlen( $values ) > 0 ? ['#type' => 'link', '#title' => t('View Online Exhibit'), '#url' => Url::fromUri( $values )] :  $values ;
            break;
          case 'floor_plan':
          case 'location:record:content:floor_plan':
            $values = [
              '#type' => 'link',
              '#title' => t('Floor Plan'),
              '#url' => Url::fromUri($values),
              '#attributes' => ['class' => ['floor-plan']]
            ];
            break;
          case 'location:record:content:geolocation:extended:url':
          case 'geolocation:extended:url':
            $text_key = $key .'_text';
            $values = [
              '#type' => 'link',
              '#title' => t('@text', ['@text' => $doc['flatArray'][$text_key]]),
              '#url' => Url::fromUri($values),
              '#attributes' => ['class' => ['map-link']]
            ];
            break;
        }

        $this->formatLabels($values, $labels);
        if (isset($mini[$key])) {
          $mini_fields[$key] = $values;
        }
        if (isset($hide[$key])) {
          if (isset($hide[$key][$key])) {
            continue ;
          }
          else {
            foreach ($values as $j => $item) {
              if (isset($item['label']) && !isset($hide[$key][$item['label']])) {
                $new_content[$key][$j] = $item;
              }
            }
          }
        }
        else {
          $new_content[$key] = $values;
        }
      }
    }
    if (!empty($fields)){
      foreach ($fields as $rfield) {
        if (isset($new_content[$rfield])) {
          $new_ft[$rfield] = $new_content[$rfield];
          $used_fields[$rfield] = $rfield;
        }
      }
    }
    //if (isset($fields['*'])) {
    $new_ft += isset($fields['*']) ? array_diff_key($new_content, $used_fields) : [];
    //}

    $new_ft = $mini_fields ? array_diff_key($new_ft, $mini_fields) : $new_ft;
    $doc['fields'] = $new_ft;
    $doc['mini_fields'] = $mini_fields;
  }

  public function getRecordLink($token_text, $doc, $path = NULL) {
    if ($token_text) {
      if ($doc['type'] === 'page') {
        $path = str_replace($doc['type']. ':','', $doc['url']);
        $path_array = explode(':', $path);
        $path_cnt = count($path_array) > 1;
        $object_group = $path_cnt ? $path_array[0] : str_replace('objectgroup:','', $doc['content']['url']);
        $page_id = $path_cnt ? $path_array[1] :  $path_array[0];
        $url = Url::fromRoute('edan_record.view_page', [
          'edan_id' => $object_group,
          'page_id' => $page_id
        ]);
      }
      elseif ($doc['type'] === 'objectgroup') {
        $path = str_replace($doc['type']. ':','', $doc['url']);
        $path_array = explode(':', $path);
        $object_group = $path_array[0];
        $url = Url::fromRoute('edan_record.view_objectgroup', [
          'edan_id' => $object_group,
        ]);
      }
      elseif ($path) {
        // $token_text = strstr($path, '[edan:title]') ? '[edan:url_title]' : $token_text;
        if (strstr($path, '[edan:title]')) {
          $title =  \Drupal::token()->replace('[edan:title]', ['edan' => $doc]);
          $title =  \Drupal::service('pathauto.alias_cleaner')->cleanString($title);
          $edanId = strstr($token_text, 'edan:url') ?  str_replace($doc['type']. ':','', $doc['url']) : $doc['id'];
          $url = Url::fromRoute('edan_record.view_' . $doc['type'], ['edan_id' => $edanId, 'edan_title' => $title]);
        }
        else {
          $tokenized = \Drupal::token()->replace($token_text, ['edan' => $doc]);
          $url = $tokenized === $token_text ? Url::fromRoute('edan_record.record_page', ['edan_id' => $doc['url']]) : Url::fromRoute('edan_record.view_' . $doc['type'], ['edan_id' => $tokenized]);
        }
      }
      else {
        $url = Url::fromRoute('edan_record.record_page', ['edan_id' => $doc['url']]);
      }
    }
    else {
      $url = Url::fromRoute('edan_record.record_page', ['edan_id' => $doc['url']]);
    }

    return $url;
  }
}
