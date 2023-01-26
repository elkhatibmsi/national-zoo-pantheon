<?php

namespace Drupal\edan_search\Entity;

use Drupal\Core\Config\Config;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Theme\Registry;
use Drupal\edan_search\EdanSearchManagerInterface;
use Drupal\edan\EdanFacets;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EdanSearchEntityViewBuilder extends EntityViewBuilder{
  /**
   * @var \Drupal\edan_search\EdanSearchManagerInterface
   */
  protected $manager;

  /**
   * Constructs a new EdanSearchEntityViewBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Theme\Registry $theme_registry
   *   The theme registry.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\edan_search\EdanSearchManagerInterface $search_manager
   */
  public function __construct(EntityTypeInterface $entity_type, EntityRepositoryInterface $entity_repository, LanguageManagerInterface $language_manager, Registry $theme_registry, EntityDisplayRepositoryInterface $entity_display_repository, EdanSearchManagerInterface $search_manager) {
    parent::__construct($entity_type, $entity_repository, $language_manager, $theme_registry, $entity_display_repository);
    $this->manager = $search_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.repository'),
      $container->get('language_manager'),
      $container->get('theme.registry'),
      $container->get('entity_display.repository'),
      $container->get('edan_search.manager')
    );
  }

	/**
	 * Provides entity-specific defaults to the build process.
	 *
	 * @param \Drupal\Core\Entity\EntityInterface $entity
	 *   The entity for which the defaults should be provided.
	 * @param string $view_mode
	 *   The view mode that should be used.
	 *
	 * @return array
	 */
	protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
		$build = parent::getBuildDefaults($entity, $view_mode);

		$searchResults = $this->manager->executeSearch($entity);

	  $build = array_merge($this->manager->renderSearch($entity, $searchResults), $build);
    $build['#cache']['max-age'] = 0;
		return $build;
	}

}
