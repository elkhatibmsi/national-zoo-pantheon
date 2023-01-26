<?php

namespace Drupal\edan_tabs;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Edan tab entities.
 */
class EdanTabListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Edan tab');
    $header['id'] = $this->t('Machine name');
    $header['path'] = $this->t('Path');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $url =  Url::fromRoute('edan_tabs.view_'. $entity->id());
    $row['path'] =  Link::fromTextAndUrl( $entity->get('path'), $url);

    return $row + parent::buildRow($entity);
  }

}
