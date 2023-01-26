<?php

namespace Drupal\edan_record;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Edan record entities.
 */
class EdanRecordListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Edan record');
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
    $row['path'] = $entity->get('path');
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

	/**
	 * {@inheritdoc}
	 */
	public function getDefaultOperations(EntityInterface $entity) {
		$operations = parent::getDefaultOperations($entity);

		if (isset($operations['edit'])) {
			$operations['edit']['title'] = $this->t('Configure');
		}

		$operations['duplicate'] = [
			'title'  => $this->t('Duplicate'),
			'weight' => 15,
			'url'    => $entity->toUrl('duplicate-form'),
		];

//		if ($entity->id() == 'default') {
//			unset($operations['delete']);
//		}

		return $operations;
	}

}
