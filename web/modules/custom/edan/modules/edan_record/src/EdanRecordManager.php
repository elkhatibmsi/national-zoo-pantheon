<?php

namespace Drupal\edan_record;

use Drupal\edan\EdanManagerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EdanRecordManager.
 */
class EdanRecordManager extends EdanRecordProcess implements EdanRecordManagerInterface {

  protected $edanRecord = [];

  public function getRecord($edan_id, $path_key = '[edan:url]', $type = NULL, $settings = NULL, $search = NULL, $admin = FALSE) {
    $cache_id = $type . ':' . $edan_id;
//    if (!isset($this->edanRecord[$cache_id])) {
//      $this->edanRecord[$cache_id] = NULL;
//      $endpoint = $admin ? 'content/%s/admincontent/getContent.htm' : ($type === 'edanlists' ? 'content/v2.0/admincontent/renderContent.htm' : 'content/%s/content/getContent.htm');
    $endpoint = $admin || $type === 'edanlists' ? 'content/%s/admincontent/getContent.htm' : 'content/%s/content/getContent.htm';

    if ($search) {
      $result = $this->edanConnector->runParamQuery($search);
      $result['data'] = $result['data']['recordCount'] > 0 ? current($result['data']['rows']) : NULL;
    }
    else {
      $key = 'url';
      if ($path_key === '[edan:id]') {
        $key = 'id';
      }
      else {
        if ($type == 'event' && strstr($edan_id, 'event-event')) {
          $record_id = urldecode($edan_id);
          $start = strrpos($record_id, 'event-event') + 6;
          $new_id = substr($record_id, $start);
          $edan_id = $type . ':' . $new_id;
        }
        elseif ($type == 'event' && strstr($edan_id, 'event-')) {
          $record_id = urldecode($edan_id);
          $start = strrpos($record_id, 'event-');
          $new_id = substr($record_id, $start);
          $edan_id = $type . ':' . $new_id;
        }
        elseif ($type) {
          $record_id = urldecode($edan_id);
          $start = $type === 'page' ? 1 : strrpos($record_id, ':') + 1;
          $new_id = $start === 1 ? $record_id : substr($record_id, $start);
          $edan_id = $type . ':' . $new_id;
        }
      }

      $result = $this->edanConnector->getRecord($edan_id, $key, $type, $endpoint);
    }
    if ($result['data']) {
      $recordSettings = $this->configLoad('', 'edan_record.settings');
      if (in_array($type, [
          'event',
          'location',
          'si-unit',
          '3d_package',
          'ecr'
        ]) || !$recordSettings['limit_to_local'] || ($recordSettings['limit_to_local'] && isset($recordSettings['unit_codes'][$result['data']['unitCode']]))) {
        switch ($result['data']['type']) {
          case 'page':
            $result['data']['migratedData'] = TRUE;
            if (!isset($result['data']['content']['page']) && isset($result['data']['linkedId'])) {
              $data = $result['data'];
              $content = $data['content'];
              if ($settings) {
                $settings['fields'] = ['page:content'];
                $settings['summary_fields'] = ['page:content'];
              }
              $objectgroup = $this->edanConnector->getRecord($data['linkedId'], 'id', 'objectgroup', $endpoint);

              $data['content'] = $objectgroup['data']['content'];
              $data['content']['url'] = $objectgroup['data']['url'];
              $data['content']['objectGroupId'] = $data['linkedId'];
              $data['content']['defaultPageId'] = $objectgroup['data']['content']['defaultPageId'];
              $data['content']['page'] = $content;
              $data['content']['page']['type'] = 'page';
              $data['content']['page']['url'] = $data['url'];
              $data['publicSearch'] = $objectgroup['data']['publicSearch'];
              $result['data'] = $data;
              $result['data']['migratedData'] = FALSE;
            }
            break;
          case 'objectgroup':
            if ($settings) {
              $settings['fields'] = ['defaultPage:content'];
              $settings['summary_fields'] = ['description'];
            }
            $result['data']['migratedData'] = TRUE;
            if (!isset($result['data']['content']['defaultPage'])) {
              $page = $this->edanConnector->getRecord($result['data']['content']['defaultPageId'], 'id', 'page', $endpoint);
              $data = $result['data'];
              $data['content']['url'] = $data['url'];
              $data['content']['defaultPage'] = $page['data']['content'];
              $data['content']['defaultPage']['pageId'] = $page['data']['id'];
              $data['content']['defaultPage']['objectGroupId'] = $data['content']['objectGroupId'] = $result['data']['id'];
              $data['content']['defaultPage']['url'] = $page['data']['url'];
              $data['content']['objectGroupId'] = $data['id'];
              $result['data'] = $data;
              $result['data']['migratedData'] = FALSE;
            }
            break;
        }

        if ($settings) {
          $this->processData($result['data'], $settings, FALSE, FALSE);
        }
        else {
          $this->getMedia($result['data']);
        }
        if ($result['data']['type'] === '3d_package' && isset($result['data']['content']['content.edanmdm'])) {
          $doc['content'] = $result['data']['content']['content.edanmdm'];
          $edanmdm = $this->entityLoad('edanmdm', 'edan_record');
          $edanSettings = $edanmdm->getRecordSettings();
          $doc['flatArray'] = $this::record_array_flatten($doc);
          $this->getFields($doc, $edanSettings);
          $result['data']['fields'] = $doc['fields'];
          $result['data']['mini_fields'] = $doc['mini_fields'];
        }
      }
      else {
        $result['data'] = [];
      }

      $this->edanRecord[$cache_id] = $result['data'];
//      }
    }
    return $this->edanRecord[$cache_id];
  }


  public function getRelated(&$record) {
    $params = $related = [];
    // dump($record);
    switch ($record['type']) {
      case 'ead_collection':
        $search = $this->entityLoad('ead_collection', 'edan_search');
        $edanSettings = $search->getEdanSettings();
        $edanSettings['fq_params'] = ['p.ead_component.collection_id:"' . $record['content']['collection_id'] . '"'];
        $search->setSettings('edan_settings', $edanSettings);
        $view_builder = \Drupal::entityTypeManager()
          ->getViewBuilder('edan_search');
        $build = $view_builder->view($search);
        $record['search_results'] = $build['#record_count'] == 0 ? NULL : $build;
        break;
      case 'ead_component':
        $params['fqs'] = array(
          'p.ead_collection.collection_id:"' . $record['content']['collection_id'] . '"'
        );
        $related = $this->edanConnector->runParamQuery($params);
        if ($related['data']['recordCount'] > 0) {
          $search = $this->entityLoad('ead_collection', 'edan_search');
          $search->setSettings('label', 'Related Content');
          $edanSearchManager = \Drupal::service('edan_search.manager');
          $edanSearchManager->getPageParameters();
          $items = [];
          foreach ($related['data']['rows'] as $delta => $row) {
            $entity = $this->entityLoad($row['type'], 'edan_record');
            $settings = $entity ? $entity->getRecordSettings() : $this->getDefault();
            $this->processData($row, $settings, TRUE);
            $related['data']['rows'][$delta] = $row;
          }
          $build = $edanSearchManager->renderSearch($search, $related['data']);
          $build['#theme'] = 'edan_search';
          $build['#edan_search'] = $search;
          $build['#view_mode'] = 'full';
          $record['search_results'] = $build;
        }
        //   dump($record);
        break;
      case 'page':
        $build = $this->getObjectList('pageId', $record['id'], $record);
        if ($build['#record_count'] == 0 && !$record['content']['page']['settings']['disableObjects']) {
          $build = $this->getObjectList('objectGroupId', $record['content']['objectGroupId'], $record);
        }
        $record['search_results'] = $build['#record_count'] == 0 ? NULL : $build;
        break;
      case 'objectgroup':
        $build = $this->getObjectList('objectGroupId', $record['id'], $record);
        if ($build['#record_count'] == 0 && isset($record['content']['defaultPageId'])) {
          $build = $this->getObjectList('pageId', $record['content']['defaultPageId'], $record);
        }
        $record['search_results'] = $build['#record_count'] == 0 ? NULL : $build;
        break;
      case 'si-unit':
        $params['fqs'] = ['p.location.parent_unit_id:' . $record['id']];
        // $related = $this->edanConnector->runParamQuery($params);
        break;
      case '3d_package':
        if (isset($record['content']['content.edanurl']) && empty($record['content']['content.edanmdm'])) {
          $args = array(
            'rows' => 1,
            'fqs' => array(
              'url:"' . $record['content']['content.edanurl'] . '"'
            ),
          );
          $list = $this->edanConnector->runParamQuery($args);
          if ($list['data']['recordCount'] > 0) {
            $doc = current($list['data']['rows']);
            $fqs = array();
            foreach ($doc['content']['items'] as $item) {
              $fqs[] = 'url:"' . $item . '"';
            }
            $args = array(
              'fqs' => array(
                implode(' OR ', $fqs)
              ),
              'rows' => count($fqs),
            );

            $results = $this->edanConnector->runParamQuery($args);
            if ($results['data']['recordCount'] > 0) {
              $list = [];
              foreach ($results['data']['rows'] as $row) {
                $entity = $this->entityLoad($row['type'], 'edan_record');
                $settings = $entity ? $entity->getRecordSettings() : $this->getDefault();
                $this->processData($row, $settings);
                $list[] = $row;
              }
//					$record['list'] = array(
//						'docs' => $results['response']['docs'],
//						'docs_raw' => $results['response']['docs_raw'],
//						'numFound' => $results['numFound'],
//						'additional_theme' => array('view--list'),
//						'container_class' => 'object-list',
//						'results_class' => 'search-results',
//					);
              $record['list'] = $list;
            }
          }
        }

        break;
    }
    $edan_id = str_replace($record['type'] . ':', '', $record['url']);

//		$args = [
//    	'fqs' => [
//		    'type:objectlists',
//		    'p.objectlists.items:*'. $edan_id
//	    ]
//    ];
//		$results = $this->edanConnector->runParamQuery($args);
//		if ($results['data']) {
//
//		}
  }

  private function getObjectList($key, $id, $record) {
    $search = $this->entityLoad('ogmt_results', 'edan_search');
    //set fqs to get objectlist
    $edanSettings = $search->getEdanSettings();
    if (isset($record['content']['objects']) && $record['content']['objects']['listType'] == 1) {
      $edanSettings['endpoint'] = '/metadata/%s/metadata/search.htm';
      $fqs = [];
      foreach ($record['content']['objects']['items'] as $fq) {
        if (strstr($fq, ':')) {
          $fqs[] = $fq;
        }
        else {
          $edanSettings['default_q'] = $fq;
        }
      }
      $edanSettings['fq_params'] = $fqs;
    }
    else {
      $edanSettings['other_params'][$key] = $id;
      $edanSettings['other_params']['includePrivate'] = 'true';
    }
    $search->setSettings('edan_settings', $edanSettings);
    //set display_mode
    $renderSettings = $search->getRenderSettings();
    $renderSettings['display_mode'] = isset($record['content']['settings']['ux_objects_display']) && $record['content']['settings']['ux_objects_display'] == 1 ? 'list' : ($renderSettings['display_mode'] == 'masonry' ? $renderSettings['display_mode'] : 'grid');
    $search->setSettings('render_settings', $renderSettings);
    //set facet data
    $facet = $record['content']['settings']['show_facets'] ?? FALSE;
    $facetSettings = $search->getFacetSettings();
    $facetSettings['facet_status'] = TRUE;
    $facetSettings['facet_limit'] = $facet == FALSE ? 1 : $facetSettings['facet_limit'];
    $search->setSettings('facets', $facetSettings);
    $url = str_replace($record['type'] . ':', '', $record['url']);
    $url = str_replace('-', '_', $url);
    $search->setSettings('info', explode(':', $url));
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('edan_search');
    $build = $view_builder->view($search);
    $build['#facets'] = $facet == FALSE ? [] : $build['#facets'];
    return $build;
  }

  public function getMenu(&$record, $embed = FALSE) {
    $menu = $record['content']['menu'] ?? NULL;
    $menu = count($menu) > 1 ? $menu : NULL;
    $items = $links = [];
    $objectgroup = $record['content'];
    $objectgroup['type'] = 'objectgroup';
    $base_url = 'page:' . str_replace('objectgroup:', '', $objectgroup['url']) . ':';
    if ($menu || $record['publicSearch'] == FALSE) {
      $results = $record['migratedData'] ? $record['content']['menu'] : $this->edanConnector->getLinkedIdContent($record['content']['objectGroupId'], 'page');
      if ($results && count($results) > 1) {
        if (!$menu) {
          foreach ($results as $delta => $page) {
            $menu[$delta] = $page['id'];
          }
        }
        if ($record['migratedData']) {
          foreach ($results as $delta => $page) {
            $page['type'] = 'page';
            $this->getMenuLink($page, $record, $embed);
            $links[$delta] = Link::fromTextAndUrl($page['menuTitle'], $page['menuLink'])
              ->toRenderable();
            $items[$delta] = $page;
          }
        }
        else {
          $keys = array_flip($menu);
          foreach ($results as $page) {
            if (isset($keys[$page['id']])) {

              $new_page = [
                'id' => $page['id'],
                'url' => strstr($page['url'], $base_url) ? $page['url'] : $base_url . str_replace('page:', '', $page['url']),
                'title' => $page['content']['title'],
                'listTitle' => $page['content']['listTitle'],
                'type' => $page['type']
              ];
              $this->getMenuLink($new_page, $record, $embed);
              $links[$keys[$page['id']]] = Link::fromTextAndUrl($new_page['menuTitle'], $new_page['menuLink'])
                ->toRenderable();
              $items[$keys[$page['id']]] = $new_page;
            }
          }
          ksort($items);
          ksort($links);
        }

        $menu = $items;
        $links = [
          '#theme' => 'item_list',
          '#wrapper_attributes' => [
            'class' => ['menu']
          ],
          '#items' => $links,
        ];
      }
    }
    $record['menuLinks'] = $links;
    $record['menu'] = $menu;
  }

  private function getMenuLink(&$page, $record, $embed = FALSE) {
    $objectgroup = $record['content'];
    $objectgroup['type'] = 'objectgroup';
    $defaultPage = $page['id'] == $record['content']['defaultPageId'];
    $page['menuTitle'] = $defaultPage ? (strlen($record['listTitle']) > 0 ? $record['listTitle'] : $record['content']['title']) : (strlen($page['listTitle']) > 0 ? $page['listTitle'] : $page['title']);

    $url = $embed ? ($defaultPage ? Url::fromRoute('<current>') : Url::fromRoute('<current>', [], [
      'query' => [
        'pageId' => $page['url'],
        'objectGroupId' => $objectgroup['url']
      ]
    ])) :
      ($defaultPage ? $this->getRecordLink('[edan:url]', $objectgroup) : $this->getRecordLink('[edan:url]', $page));
    $options = $url->getOptions();
    $options['attributes']['class'] = $record['type'] === 'objectgroup' && $defaultPage ? [
      'active',
      'menu-item'
    ] : ($page['id'] === $record['id'] ? [
      'active',
      'menu-item'
    ] : ['menu-item']);
    $url->setOptions($options);
    $page['menuLink'] = $url;
  }

  public function renderRecord($record, $view_mode = 'full', $embed = FALSE) {
    $this->getRelated($record);
    $teaser = $view_mode === 'teaser';
    $build = [
      '#theme' => 'edan_record',
      '#attached' => [
        'library' => $record['alt_template'] ? ['edan_record/edan_record_alternate'] : ['edan_record/edan_record']
      ]
    ];

    if ($record['type'] == 'location') {
      $build = [
        '#theme' => 'edan_record',
        '#attached' => [
          'library' => ['edan_record/edan_record_location']
        ]
      ];
    }
    if (!$teaser) {
      if ($record['type'] == 'page' || $record['type'] == 'objectgroup') {
        $this->getMenu($record, $embed);
      }
      if ($record['type'] == 'timeline' && $record['url']) {
        // Edan timeline endpoint for json.
        // This is a dev url and not availble to public at this time, so this will need to be updated.
        $timeline_json = 'https://edandev.si.edu/timeline/render/' . $record['url'] . '/json';
        $timelineObj = [
          'id' => $record['id'],
          'json' => $timeline_json
        ];
        $build['#attached']['drupalSettings']['timeline'][$record['id']] = $timelineObj;
        $build['#attached']['library'][] = ($view_mode = 'full' || $embed = TRUE) ? 'edan_record/knightlab.timeline' : '';
        $build['#attached']['library'][] = ($view_mode = 'full' || $embed = TRUE) ? 'edan_record/timeline' : '';
      }
      if ($record['media'] && $record['type'] != 'objectgroup') {
        $edanMedia = \Drupal::service('edan_record.media');
        $build['#media'] = $edanMedia;

      }
    }

    $build['#record'] = $record;
    $build['#embedded'] = $embed;
    $build['#view_mode'] = $view_mode;
    if ($embed && in_array($record['type'], ['page', 'objectgroup'])) {
      $build['#cache'] = [
        'tags' => [],
        'max-age' => 0
      ];
    }
    else {
      $build['#cache'] = [
        'tags' => ['edan_record:' . $record['id'], 'view_mode:' . $view_mode],
      ];
    }

    if ($teaser) {
      $build['#theme'] = 'edan_record__teaser';
    }

    return $build;
  }


}
