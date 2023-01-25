<?php
namespace Drupal\edan_record\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\edan_record\EdanRecordManagerInterface;
use Drupal\edan_record\Entity\EdanRecordInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Created by PhpStorm.
 * User: cdesai
 * Date: 12/19/2017
 * Time: 2:59 PM
 */

class EdanPageController extends ControllerBase {

  /**
   * The processed edan record.
   *
   */
  private $edanRecord = NULL;

  /**
   * The EDAN Connector Service
   *
   * @var \Drupal\edan_record\EdanRecordManagerInterface
   */
  private $edanRecordManager;

	/**
	 * Constructs a new edan record controller.
	 *
	 * @param \Drupal\edan_record\EdanRecordManagerInterface $edanRecordManager
	 *   The edan record manager.
	 */

	public function __construct(EdanRecordManagerInterface $edanRecordManager) {
		$this->edanRecordManager = $edanRecordManager;
	//	$this->config = $config_factory->get('edan_record.settings');
	}

	public static function create(ContainerInterface $container) {
		return new static(
			$container->get('edan_record.manager')
		);
	}

  /**
   * Returns a page title.
   */
  public function getTitle(EdanRecordInterface $entity, $edan_id, $page_id = NULL) {
    // will be replaced
	  $record = $this->getRecord($entity, $edan_id, $page_id);
	  return $record['title'];
  }

  /**
   * Provides /object/{edan_id} page.
   * @param string $edan_id EDAN Record Id extracted from the path.
   *
   * @return array Render array for record_page template.
   */
  public function view(EdanRecordInterface $entity, $edan_id, $page_id = NULL, $edan_title = FALSE) {
    $page_id = $edan_title ? NULL : $page_id;
	  $record = $this->getRecord($entity, $edan_id, $page_id);
	  if ($record) {
		  $build = $this->edanRecordManager->renderRecord($record);
			return $build;
		  //return $this->edanRecordManager->renderRecord($record);
	  }
	  else {
		  throw new NotFoundHttpException('Record not found: ' . $edan_id);
	  }
  }

  public function getRecord(EdanRecordInterface $entity, $edan_id, $page_id = NULL) {
	  $settings = $entity->getRecordSettings();
	  if (empty($this->edanRecord)) {
      if ($page_id) {
        $this->edanRecord = $this->edanRecordManager->getRecord($edan_id .':'. $page_id, $entity->get('path_key'), 'page', $settings);
      }
      else {
        $this->edanRecord = $this->edanRecordManager->getRecord($edan_id, $entity->get('path_key'), $entity->id(), $settings);
      }
    }
    return $this->edanRecord;

  }

	/**
	 * Returns a page title.
	 */
	public function getSystemTitle($edan_id, $ark = FALSE) {
    $record = $this->getSystemRecord($edan_id, $ark);

		return $record ? $record['title'] : '';
	}


	public function viewSystemPage($edan_id, $ark = FALSE) {
		$record = $this->getSystemRecord($edan_id, $ark);
		if ($record) {
			$build = $this->edanRecordManager->renderRecord($record);
			$build['#title'] = $record['title'];
			return $build;
		}
		else {
			throw new NotFoundHttpException('Record not found: ' . $edan_id);
		}
//		return [
//			'#type' => 'markup',
//			'#markup' => $this->t('Implement method: render')
//		];
	}

	private function getSystemRecord($edan_id, $ark = FALSE) {
	  if (empty($this->edanRecord)) {
      if ($ark) {
        $arkId = str_replace('-', '', $edan_id);
        // get the last portion of the guid
        $pos = strrpos($arkId, '/');
        $arkId = $pos ? substr($arkId, $pos + 1) : $arkId;

        $args =  array(
          'fqs' => [ 'guid:*'. $arkId .''],
        );
        $record = $this->edanRecordManager->getRecord($arkId, '[edan:url]', NULL, [], $args);
        if($record) {
          $entity = $this->edanRecordManager->entityLoad($record['type'], 'edan_record');
          $settings = $entity ? $entity->getRecordSettings() :  $this->edanRecordManager->getDefault();
          $this->edanRecordManager->processData($record, $settings);
          $this->edanRecord = $record;
        }
      }
      else {
        $parsed_value = explode(':', $edan_id);
        $type = array_shift($parsed_value);
        $type = $type === 'page' ? 'objectgroup' : $type;
        $entity = $this->edanRecordManager->entityLoad($type, 'edan_record');
        $settings = $entity ? $entity->getRecordSettings() :  $this->edanRecordManager->getDefault();
        $record = $this->edanRecordManager->getRecord( $edan_id, ['edan:url'], NULL, $settings);
        $this->edanRecord = $record;
      }
    }
    return $this->edanRecord;
  }


}
