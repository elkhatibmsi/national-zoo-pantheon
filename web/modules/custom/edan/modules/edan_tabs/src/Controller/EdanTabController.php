<?php
namespace Drupal\edan_tabs\Controller;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\edan\SearchParams;
use Drupal\edan_tabs\EdanTabManagerInterface;
use Drupal\edan_tabs\Entity\EdanTab;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EdanTabController extends ControllerBase {

  /**
   * The EDAN Connector Service
   *
   * @var \Drupal\edan_tabs\EdanTabManagerInterface
   */
  private $edanTabManager;

  /**
   * @var \Drupal\edan\SearchParams
   */
  protected $searchParams;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
	 * Constructs a new edan record controller.
	 *
	 * @param \Drupal\edan_record\EdanRecordManagerInterface $edanRecordManager
	 *   The edan record manager.
	 */

	public function __construct(EdanTabManagerInterface $edanTabManager, SearchParams $searchParams, RendererInterface $renderer) {
		$this->edanTabManager = $edanTabManager;
		$this->searchParams = $searchParams;
		$this->renderer = $renderer;
	//	$this->config = $config_factory->get('edan_record.settings');
	}

	public static function create(ContainerInterface $container) {
		return new static(
			$container->get('edan_tabs.manager'),
      $container->get('edan.search_params'),
      $container->get('renderer')
    );
	}


  /**
   * Provides /object/{edan_id} page.
   * @param string $edan_id EDAN Record Id extracted from the path.
   *
   * @return array Render array for record_page template.
   */
  public function view(EdanTab $entity, $edan_search = NULL) {
    $build = $this->edanTabManager->renderPage($entity, $edan_search);
    $this->renderer->addCacheableDependency($build, $entity);
    return $build;
  }


}
