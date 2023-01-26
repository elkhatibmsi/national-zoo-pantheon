<?php

namespace Drupal\edan_search;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a listing of Edan search entity entities.
 */
class EdanSearchEntityListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Edan search entity');
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
    $url =  Url::fromRoute('entity.edan_search.canonical', ['edan_search' => $entity->id()]);
    $row['path'] =  Link::fromTextAndUrl( $url->toString(), $url);

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

		if (in_array($entity->id(), ['default', 'ead_collection', 'ogmt_results'])) {
			unset($operations['delete']);
		}

		return $operations;
	}

	/**
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state) {
		parent::submitForm($form, $form_state);

		$this->messenger()->addMessage($this->t('The optionsets order has been updated.'));
	}

}
