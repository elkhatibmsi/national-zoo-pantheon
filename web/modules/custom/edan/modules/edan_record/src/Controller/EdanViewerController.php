<?php
namespace Drupal\edan_record\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\edan_record\EdanRecordManagerInterface;
use Drupal\edan_record\Entity\EdanRecordInterface;
use Drupal\edan_record\EdanRecordMediaInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Created by PhpStorm.
 * User: cdesai
 * Date: 12/19/2017
 * Time: 2:59 PM
 */

class EdanViewerController extends ControllerBase {

  /**
   * The processed edan record variable.
   *
   */
  private $edanRecord;

  /**
   * The EDAN Connector Service
   *
   * @var \Drupal\edan_record\EdanRecordManagerInterface
   */
  private $edanRecordManager;

  /**
   * The EDAN Connector Service
   *
   * @var \Drupal\edan_record\EdanRecordMediaInterface
   */
  private $edanRecordMedia;

	/**
	 * Constructs a new edan record controller.
	 *
	 * @param \Drupal\edan_record\EdanRecordManagerInterface $edanRecordManager
	 *   The edan record manager.
	 */

	public function __construct(EdanRecordManagerInterface $edanRecordManager, EdanRecordMediaInterface $edanRecordMedia) {
		$this->edanRecordManager = $edanRecordManager;
		$this->edanRecordMedia = $edanRecordMedia;
	//	$this->config = $config_factory->get('edan_record.settings');
	}

	public static function create(ContainerInterface $container) {
		return new static(
			$container->get('edan_record.manager'),
      $container->get('edan_record.media')
    );
	}

  public function setRecord($edan_id) {
	  $record = [];
	  if (empty($this->edanRecord)) {
      if (strstr($edan_id, ':')) {
        $parsed_value = explode(':', $edan_id);
        $type = array_shift($parsed_value);
        $type = $type === 'page' ? 'objectgroup' : $type;
        $entity = $this->edanRecordManager->entityLoad($type, 'edan_record');
        $settings = $entity ? $entity->getRecordSettings() :  $this->edanRecordManager->getDefault();
        $record = $this->edanRecordManager->getRecord( $edan_id, ['edan:url'], NULL, $settings);
      }
      else {
        $record = $this->edanRecordManager->getRecord( $edan_id, '[edan:id]');
          $entity = $this->edanRecordManager->entityLoad($record['type'], 'edan_record');
          $settings = $entity ? $entity->getRecordSettings() :  $this->edanRecordManager->getDefault();
          $this->edanRecordManager->processData($record, $settings);
        }
      $this->edanRecord = $record;
    }
  }

	/**
	 * Returns a page title.
	 */
	public function getTitle($edan_id) {
	  $this->setRecord($edan_id);
		return $this->edanRecord ? $this->edanRecord['title'] : '';
	}


	public function view(Request $request, $edan_id) {
    $idsId = $request->query->get('id');
    $this->setRecord($edan_id);

		if ($this->edanRecord) {
			return $this->edanRecordMedia->renderViewer($this->edanRecord, $idsId);
		}
		else {
			throw new NotFoundHttpException('Record not found: ' . $edan_id);
		}
	}




}
