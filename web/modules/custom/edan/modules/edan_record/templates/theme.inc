<?php
/**
 * Created by PhpStorm.
 * User: phamh
 * Date: 11/16/20
 * Time: 1:18 PM
 */

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Render\Markup;

function template_preprocess_edan_record(&$vars) {
  $record = $vars['record'];
  $vars['attributes'] = $vars['record']['attributes'];
  $vars['attributes']['class'][] = 'edan-record';
  $vars['attributes']['class'][] = $vars['embedded'] ? 'embedded' : 'full-page';
  $vars['attributes']['class'][] = $vars['view_mode'];
  $vars['attributes']['class'][] = $vars['record']['type'];
  $vars['attributes']['class'][] = isset($vars['record']['menu']) ? 'has-menu' : 'no-menu';
  $vars['attributes']['data-recordId'] = $vars['record']['id'];
  $vars['title_wrapper'] = $vars['embedded'] ? 'h2' : 'h1';
  if ($vars['media']) {
    $edanMedia = $vars['media'];
    $vars['media'] = $edanMedia->buildMedia($vars['record']);
    $vars['attributes']['class'][] = 'has-media';
    $vars['attributes']['class'][] = !$vars['record']['alt_template'] && $edanMedia->getValues('media_layout') ? 'media-layout' : 'media-default';
  }

  switch ($record['type']) {
    case 'location':
      // Process full address.
      $address = "";
      $map_link = "";
      $museum_link = "";
      $floor_plan = "";
      if (!empty($record['content']['address']) && !empty($record['content']['address']['extended'])) {
        $address_extended = $record['content']['address']['extended'];
        if (!empty($address_extended['street_address'])) {
          $address = $address_extended['street_address'];
        }
        if (!empty($address_extended['city']) || !empty($address_extended['state'])) {
          $address .= "</br>";
          $address .= $address_extended['city'] ?: '';
          $address .= $address_extended['state'] ? ", {$address_extended['state']}": '';

        }
      }
      $vars['address_full'] = $address;
      // Map link
      if (!empty($record['content']['geolocation']) && !empty($record['content']['geolocation']['extended'])) {
        $address_extended = $record['content']['geolocation']['extended'];
        if (!empty($address_extended['url'])) {
          $map_link_url = $address_extended['url'];
          $map_icon = ' <i class="location-map-icon fa fa-map-marker"></i>';
          $map_link_text = !empty($address_extended['url_text']) ? $address_extended['url_text'] : t('See on Map');
          $map_link_text .= $map_icon;
          $link_options = array(
            'attributes' => array(
              'class' => array(
                'location-map-link',
              ),
            ),
          );
          $url = Url::fromUri($map_link_url);
          $url->setOptions($link_options);
          $map_link = Link::fromTextAndUrl(Markup::create($map_link_text), $url)->toString();
        }
      }
      $vars['map_link'] = $map_link;

      // Museum link
      if (!empty($record['content']['record_link'])) {
        $record_link_url = $record['content']['record_link'];
        $record_link_text = parse_url($record_link_url)['host'];
        $link_options = array(
          'attributes' => array(
            'class' => array(
              'museum-link',
            ),
          ),
        );
        $url = Url::fromUri($record_link_url);
        $url->setOptions($link_options);
        $museum_link = Link::fromTextAndUrl(Markup::create($record_link_text), $url)->toString();
      }
      $vars['museum_link'] = $museum_link;

      // Floor Plan link
      if (!empty($record['content']['floor_plan'])) {
        $floor_plan_url = $record['content']['floor_plan'];
        $floor_plan_text = '<span class="text">' . t('Floor Plan') . '</span>';
        $link_options = array(
          'attributes' => array(
            'class' => array(
              'icon--floor-plan',
            ),
          ),
        );
        $url = Url::fromUri($floor_plan_url);
        $url->setOptions($link_options);
        $floor_plan = Link::fromTextAndUrl(Markup::create($floor_plan_text), $url)->toString();
      }
      $vars['floor_plan_link'] = $floor_plan;


      break;

  }

  //  $grouped_keys = array_keys($grouped);

//  $doc['row_attributes']['class'][] = array_intersect($media_classes, $grouped_keys) ? 'has-media' : (!empty($grouped) ? 'has-text-media' :'no-media');
//  $doc['row_attributes']['class'][] = 'wrapper-'. $doc['type'];
//  $doc['row_attributes']['class'][] = isset($grouped['3d']) ? 'has-3d-object' : 'no-3d-object';
//  $doc['row_attributes']['data-recordId'] = $doc['#record_id'];
//  dump($vars['record']);
  if (\Drupal::request()->query->get('edan_dump')) {
    dump(['record' => $vars['record']]);
    dump(['flatArray' => $vars['record']['flatArray']]);
    dump(['all' => $vars]);
  }
}

function template_preprocess_edan_media(&$vars) {
  $vars['attributes']['class'] = ['media-wrapper'];

  $media = $vars['media'];
  if (!$vars['record']['alt_template']) {
    $vars['#attached']['library'][] = 'edan_record/media';
  }

  if ($media->getValues('has_slideshow')) {
    $vars['attributes']['class'][] = 'has-slideshow';
    $vars['#attached']['library'][] = 'edan_record/slick';
  }
  if (isset($_GET['media_dump'])) {
    dump($vars);
  }
}

function template_preprocess_edan_viewer(&$vars) {
  $vars['attributes'] = [
    'class' => [
      'edan-viewer-container',
      'edan-record',
      $vars['slideshow'] ?  'has-slideshow' : ''
      ]
  ];
}
function template_preprocess_edan_toolbar(&$vars) {
  $vars['expand'] = $vars['viewer'] = $vars['accordion'] = NULL;
  $edanMedia = \Drupal::service('edan_record.media');
  $icons = $edanMedia::svg_icons();
  $settings = $vars['settings'];

  $media = $vars['media'];
  // Replace `.` in media id.

  $record = $vars['record'];
  $cc0 = isset($media['usage']['access']) && $media['usage']['access'] == 'CC0';
  $usage = $cc0 ? 'media--openaccess' : 'media--no-openaccess';
  $vars['attributes'] = array(
    'class' => array(
      'media-metadata',
      $usage,
      $media['has_ids'] ? 'has-ids' : 'no-ids',
    ),
    'data-idsid' => $media['has_ids'] ? $media['idsId'] : '',
    'data-type' => $media['type']
  );
  $placement = $settings['viewer'] ? 'right' : 'bottom';

  $vars['accordion'] = array(
    'usage' => array(
      'image' => $icons['cc0'],
      'wrapper' => [
        'data-popover-element' => 'heading-tab-usage-' . $media['id'],
        'class' => ['card-header', 'wrapper-heading-tab-usage']
      ],
      'attributes' => array(
        'id' => 'heading-tab-usage-' . $media['id'],
        'tabindex' => 0,
        'type' => 'button',
        'data-parent' => '#media-tab-' . $record['id'],
        'data-target' => '#tab-usage-' . $media['id'],
        'data-placement' => 'bottom',
        'data-offset' => '0,8px',
        'class' => array(
          'icon',
          'btn',
          'btn-popover',
          'btn-usage',
          'heading-tab-usage',
        ),
      ),
      'data' => $usage,
    ),
  );
  if ($settings['has_ids']) {
    $vars['accordion']['download'] = array(
      'text' => t('Click to view download files'),
      'image' => $icons['download'],
      'wrapper' => [
        'data-popover-element' => 'heading-tab-download-' . $media['id'],
        'class' => ['card-header', 'wrapper-heading-tab-download']
      ],
      'attributes' => array(
        'id' => 'heading-tab-download-' . $media['id'],
        'tabindex' => 0,
        'type' => 'button',
        'data-parent' => '#media-tab-' . $record['id'],
        'data-target' => '#tab-download-' . $media['id'],
        'data-placement' => $placement,
        'data-offset' => '0,8px',
        'class' => array(
          'icon',
          'btn',
          'btn-popover',
          'btn-download',
          'heading-tab-download',
        )
      ),
    );
    $vars['accordion']['iiif'] = array(
      'text' => t('Click to view IIIF info'),
      'image' => $icons['iiif'],
      'wrapper' => [
        'data-popover-element' => 'heading-tab-iiif-' . $media['id'],
        'class' => ['card-header', 'wrapper-heading-tab-iiif']
      ],
      'attributes' => array(
        'id' => 'heading-tab-iiif-' . $media['id'],
        'tabindex' => 0,
        'type' => 'button',
        'data-parent' => '#media-tab-' . $record['id'],
        'data-target' => '#tab-iiif-' . $media['id'],
        'data-placement' => $placement,
        'data-offset' => '0,8px',
        'class' => array(
          'icon',
          'btn',
          'btn-popover',
          'btn-iiif',
          'heading-tab-iiif',
        )
      ),
    );
  }
  if ($settings['mode'] === 'embed' && !$settings['viewer']) {
    $vars['accordion']['viewToggle'] = array(
      'text' => t('Click to toggle image'),
      'image' => $icons['list'],
      'attributes' => array(
        'id' => 'heading-tab-viewToggle',
        'tabindex' => 0,
        'type' => 'button',
        'data-parent' => '#media-tab-' . $media['id'],
        'data-bs-target' => '#tab-viewToggle',
        'data-bs-placement' => $placement,
        'data-bs-offset' => '0,8px',
        'class' => array(
          'icon',
          'btn',
          'btn-popover',
          'btn-viewToggle',
        )
      ),
    );
  }
  if ($media['type'] == '3d_package') {
    $vars['media']['usage']['voyager'] = $cc0 ? 'We also suggest that users: <ul>
    <li>Give attribution to the Smithsonian.</li>
    <li>Contribute back any modifications or improvements.</li>
    <li>Do not mislead others or misrepresent the datasets or its sources. </li>
    <li>Be responsible.</li>
    </ul>' : 'These files consist of 3D scans of historical objects in the collections of the Smithsonian and may be downloaded by you only for non-commercial, educational, and personal uses subject to this disclaimer (https://3d.si.edu/disclaimer) and in accordance with the Terms of Use (https://3d.si.edu/termsofuse).';
  }
  $vars['accordion']['usage']['conditions'] = $cc0 ?
    'This media is in the public domain (free of copyright restrictions).  You can copy, modify, and distribute this work without contacting the Smithsonian.  For more information, visit the Smithsonian\'s <a href="https://www.si.edu/openaccess">Open Access</a> page.' :
    'There are restrictions for re-using this image. For more information, visit the Smithsonian\'s <a href="https://www.si.edu/termsofuse">Terms of Use</a> page .';

  if ($settings['viewer']) {
    $vars['viewer'] = array(
      'zoomInButton' => array(
        'text' => t('Zoom In'),
        'image' => $icons['zoomIn'],
        'attributes' => array(
          'id' => 'zoom-in',
          'class' => array(
            'icon',
            'icon--viewer',
            'icon--zoom-in',
            'btn'
          )
        )
      ),
      'zoomOutButton' => array(
        'text' => t('Zoom Out'),
        'image' => $icons['zoomOut'],
        'attributes' => array(
          'id' => 'zoom-out',
          'class' => array(
            'icon',
            'icon--viewer',
            'icon--zoom-out',
            'btn'
          )
        )
      ),
      'homeButton' => array(
        'text' => t('Home'),
        'image' => $icons['home'],
        'attributes' => array(
          'id' => 'home',
          'class' => array(
            'icon',
            'icon--viewer',
            'icon--home',
            'btn'
          )
        )
      ),
    );
    if (isset($settings['features']['showRotationControl'])) {
      $vars['viewer'] += array(
        'rotateLeftButton' => array(
          'text' => t('Rotate Left'),
          'image' => $icons['rotate'],
          'attributes' => array(
            'id' => 'rotate-left',
            'class' => array(
              'icon',
              'icon--viewer',
              'icon--rotate-left',
              'btn'
            )
          )
        ),
        'rotateRightButton' => array(
          'text' => t('Rotate Right'),
          'image' => $icons['rotate'],
          'attributes' => array(
            'id' => 'rotate-right',
            'class' => array(
              'icon',
              'icon--viewer',
              'icon--rotate-right',
              'btn'
            )
          )
        ),
      );
    }
    if (count($record['media']) > 1) {
      $vars['viewer'] += array(
        'previousButton' => array(
          'text' => t('Previous'),
          'image' => $icons['arrow'],
          'attributes' => array(
            'id' => 'zoom-previous',
            'class' => array(
              'icon',
              'icon--previous',
              'btn',
              'arrow'
            )
          )
        ),
        'nextButton' => array(
          'text' => t('Next'),
          'image' => $icons['arrow'],
          'attributes' => array(
            'id' => 'zoom-next',
            'class' => array(
              'icon',
              'icon--next',
              'btn',
              'arrow'
            )
          )
        ),
      );
    }
  }
  elseif ($settings['has_ids']) {
    $vars['expand'] = array(
      'text' => t('Expand to view image'),
      'image' => $icons['expand'],
      'source' => $media['viewer'],
      'attributes' => array(
        'id' => 'viewer',
        'class' => array(
          'icon',
          'icon--expand',
          'btn',
          'arrow'
        )
      )
    );
  }
  $vars['#attached']['library'][] = 'edan_record/media';
  if (isset($_GET['toolbar_dump'])) {
    dump($vars);
  }
}

function template_preprocess_edan_media_item(&$vars) {
  $vars['link'] = $vars['record']['alt_template'] ? NULL : $vars['media']['viewer'];

  if ($vars['media']['type'] == 'images')
  $vars['attributes'] = [
    'itemType' => 'http://schema.org/ImageObject'
  ];
  else {
    $vars['attributes']['class'][] = 'wrapper--iframe';
  }

}
