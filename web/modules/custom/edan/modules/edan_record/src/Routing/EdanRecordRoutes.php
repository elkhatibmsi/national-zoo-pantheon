<?php

namespace Drupal\edan_record\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides dynamic routes for edan record type.
 */
class EdanRecordRoutes implements ContainerInjectionInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

	/**
	 * Constructs a new search route subscriber.
	 *
	 * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
	 *   The search page Manager.
	 */
	public function __construct(EntityTypeManagerInterface $entityTypeManager) {
		$this->entityTypeManager = $entityTypeManager;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function create(ContainerInterface $container) {
		return new static(
      $container->get('entity_type.manager')
		);
	}

	/**
	 * Returns an array of route objects.
	 *
	 * @return \Symfony\Component\Routing\Route;
	 *   An array of route objects.
	 */
	public function routes() {
		$routes = [];

		$edan_records = $this->entityTypeManager->getStorage('edan_record')->loadMultiple();
		foreach($edan_records as $entity_id => $entity) {
      $path_key = $entity->get('path_key');
			$path = str_replace($path_key, '{edan_id}', $entity->get('path'));
			if (strstr($path, '[edan:title]')) {
				$path = str_replace('[edan:title]', '{edan_title}', $path);
				$routes["edan_record.view_$entity_id"] = new Route(
					$path,
					[
						'_controller' => 'Drupal\edan_record\Controller\EdanPageController::view',
						'_title_callback' => '\Drupal\edan_record\Controller\EdanPageController::getTitle',
						'entity' => $entity_id,
					],
					[
						'_permission' => 'access content',
					],
					[
						'parameters' => [
							'entity' => [
								'type' => 'entity:edan_record',
							],
							'edan_title' => '^[a-zA-Z0-9_\-\.:]+',
							'edan_id' => '^[a-zA-Z0-9_\-\.:]+'
						],
					]
				);
        if ($entity_id === 'objectgroup') {
          $routes["edan_record.view_page"] = $this->getPageUrl($path, $entity_id);
        }
			}
			else {
				$routes["edan_record.view_$entity_id"] = new Route(
					$path,
					[
						'_controller' => 'Drupal\edan_record\Controller\EdanPageController::view',
						'_title_callback' => '\Drupal\edan_record\Controller\EdanPageController::getTitle',
						'entity' => $entity_id,
            'edan_title' => $path_key === '[edan:url_title]'
          ],
					[
						'_permission' => 'access content',
					],
					[
						'parameters' => [
							'entity' => [
								'type' => 'entity:edan_record',
							],
							'edan_id' => '^[a-zA-Z0-9_\-\.:]+',
						],
					]
				);
        if ($entity_id === 'objectgroup') {
          $routes["edan_record.view_page"] = $this->getPageUrl($path, $entity_id);
        }
			}


		}

		return $routes;
	}

  private function getPageUrl($path, $entity_id) {
    $path .=  '/{page_id}';
    return new Route(
      $path,
      [
        '_controller' => 'Drupal\edan_record\Controller\EdanPageController::view',
        '_title_callback' => '\Drupal\edan_record\Controller\EdanPageController::getTitle',
        'entity' => $entity_id,
      ],
      [
        '_permission' => 'access content',
      ],
      [
        'parameters' => [
          'entity' => [
            'type' => 'entity:edan_record',
          ],
          'edan_id' => '^[a-zA-Z0-9_\-\.:]+'
        ],
      ]
    );
  }

}
