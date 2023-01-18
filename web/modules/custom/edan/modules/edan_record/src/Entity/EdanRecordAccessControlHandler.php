<?php

namespace Drupal\edan_record\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the search page entity type.
 *
 * @see \Drupal\edan_search\Entity\EdanSearchEntity
 */
class EdanRecordAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
  	switch ($operation) {
		  case 'view':
        return AccessResult::allowedIfHasPermission($account, 'access content');
		  case 'edit':
			  return AccessResult::allowedIfHasPermission($account, 'administer edan');
		  case 'delete':
			  if ($entity->get('id') === 'edanmdm' || $entity->get('id') === 'objectgroup') {
				  return AccessResult::forbidden()->addCacheableDependency($entity);
			  }
			  else {
				  return AccessResult::allowedIfHasPermission($account, 'administer edan');
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
		return AccessResult::allowedIfHasPermission($account, 'administer edan');
	}
}
