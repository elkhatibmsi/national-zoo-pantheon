<?php

namespace Drupal\edan_search\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the search page entity type.
 *
 * @see \Drupal\edan_search\Entity\EdanSearchEntity
 */
class EdanSearchAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
  	switch ($operation) {
		  case 'view':
        return $entity->status() ? AccessResult::allowedIfHasPermission($account, 'view edan search form entity') : AccessResult::allowedIfHasPermission($account, 'edit edan search form entity');
		  case 'edit':
			  return AccessResult::allowedIfHasPermission($account, 'edit edan search form entity');
		  case 'delete':
			  if ($entity->get('id') === 'default') {
				  return AccessResult::forbidden()->addCacheableDependency($entity);
			  }
			  else {
				  return AccessResult::allowedIfHasPermission($account, 'delete edan search form entity');
			  }
	  }
	  return AccessResult::allowed();
  }


	/**
	 * {@inheritdoc}
	 *
	 * Separate from the checkAccess because the entity does not yet exist, it
	 * will be created during the 'add' process.
	 */
	protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
		return AccessResult::allowedIfHasPermission($account, 'add edan search form entity');
	}
}
