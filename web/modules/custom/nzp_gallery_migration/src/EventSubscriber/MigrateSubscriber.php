<?php

namespace Drupal\nzp_gallery_migration\EventSubscriber;

use Drupal\Core\Serialization\Yaml;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\path_alias\Entity\PathAlias;
use Drupal\Core\Database\Database;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Class PostMigrateSubscriber.
 *
 * Reset media and node changed date
 * migrate translations without translation
 * fixes to menu layout.
 *
 * @package Drupal\nzp_migrations
 */
class MigrateSubscriber implements EventSubscriberInterface {

  /**
   * Get subscribed events.
   *
   * @inheritdoc
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::POST_ROW_SAVE][] = ['onPostRowSave'];
    $events[MigrateEvents::POST_IMPORT][] = ['onPostImport'];
    return $events;
  }

  /**
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   */
  public function onPostRowSave(MigratePostRowSaveEvent $event) {

    $mig_id = $event->getMigration()->getBaseId();
    $row = $event->getRow();

    switch ($mig_id) {

      case 'upgrade_d7_node_complete_animal':

        $nid = $row->getSourceProperty('nid');
        $title = $row->getSourceProperty('title');
        // load the curent node
        $animal_node = Node::load($nid);
        // get the target ids for the gallery paras
        $gallery_p = $animal_node->get('field_modal_gallery')->getValue();
        if (!empty($gallery_p)) {
          // $gallery_p = explode(',', $gallery_p);
          foreach ($gallery_p as $para) {
            $media = $para->get('field_modal_gallery_media')->getString();
          }

          $media = explode(',', $media);

        // create the gallery node. you'll want to add some kind of check to
        // make sure it doesn't already exist - maybe check if the animal node field is
        // already populated.
          $node = Node::create([
            'type' => 'gallery',
            'title' => $title,
            'field_gallery_items' => $media,
          // 'body' => '',
          // 'field_landing_image' => '',
          // 'field_teaser_title' => '',
            'uid' => 1,
          ]);
          $node->save();
        // get gallery node id
          $nid = $node->id();
        //update the entity ref field on the animal
          //change this to whatever field you have on your end.
          $animal_node->set('field_animal_gallery', $nid);
          $animal_node->save();
        }

        break;

      default:
        // code...
        break;
    }
  }

  /**
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   */
  public function onPostImport(MigrateImportEvent $event) {

  }
}