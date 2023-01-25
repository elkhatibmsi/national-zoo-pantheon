<?php

namespace Drupal\edan_record\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Xss;
use Drupal\edan\Connector\EdanConnectorService;
use Drupal\edan_record\EdanRecordManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a route controller for entity autocomplete form elements.
 */
class EdanRecordAutocompleteController extends ControllerBase {

	/**
	 * EDAN connector Service.
	 *
	 * @var \Drupal\edan\Connector\EdanConnectorService
	 */
	protected $edanConnector;

	/**
	 * EdanRecord Process to get title.
	 *
	 * @var \Drupal\edan_record\EdanRecordManagerInterface
	 */
	protected $edanRecordManager;

  /**
   * Constructs a EdanRecordAutocompleteController object.
   *
   * @param \Drupal\edan\Connector\EdanConnectorService
   * @param \Drupal\edan_record\EdanRecordProcess
   *
   */
  public function __construct(EdanConnectorService $edanConnectorService, EdanRecordManagerInterface $edanRecordManager) {
    $this->edanConnector = $edanConnectorService;
    $this->edanRecordManager = $edanRecordManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('edan.connector'),
      $container->get('edan_record.manager')
    );
  }

  /**
   * Autocomplete the label of an entity.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object that contains the typed tags.
   * @param string $entity_type
   *   The ID of the target entity type.
   * @param string $selection_handler
   *   The plugin ID of the entity reference selection handler.
   * @param string $selection_settings_key
   *   The hashed key of the key/value entry that holds the selection handler
   *   settings.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The matched entity labels as a JSON response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown if the selection settings key is not found in the key/value store
   *   or if it does not match the stored data.
   */
  public function handleAutocomplete(Request $request, $target_type, $entity_bundle, $field_name, $match_limit) {
    $matches = [];
    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $type = NULL;
      $typed_string = mb_strtolower(array_pop($typed_string));
      if (preg_match("/.+\s\(([^\)]+)\)/", $typed_string, $test)) {
        $type = $test[1];
        $typed_string = trim(str_replace('('. $type .')', '', $typed_string));
      }
	    $args = [
	    	'q' => $typed_string .' OR '. $typed_string .'*',
		    'rows' => $match_limit
	    ];
      $recordSettings =  $this->edanRecordManager->configLoad('', 'edan_record.settings');
	    $field = \Drupal::entityTypeManager()->getStorage('field_config')->load($target_type .'.'. $entity_bundle .'.'. $field_name);
			$content_types = $field->getSetting('content_types');
			$local_units = $field->getSetting('local_units') || $recordSettings['limit_to_local'];
			if ($type) {
        $args['fqs'] = [
          'type:'. $type
        ];
      }
			elseif ($content_types) {
        if (isset($content_types['objectgroup'])) {
          $content_types['page'] = 'page';
        }
        $args['fqs'] = [
          'type:'. implode(' OR type:', $content_types),
        ];
      }

			if ($local_units && !empty($recordSettings['unit_codes'])) {
			  $args['fqs'][] = 'unit_code:"' . implode('" OR unit_code:"', $recordSettings['unit_codes']) .'"';
			}

      $args['fqs'][] = '-status:"-1"';
     // $args['includePrivate'] = 'true';
      // search admincontent to get private content and get results for unit codes first
      $results = $this->edanConnector->runParamQuery($args, '/content/%s/admincontent/search.htm');
      $items = $results['data'] ? $results['data']['rows'] : [];
      $results = $this->edanConnector->runParamQuery($args, '/metadata/%s/metadata/search.htm');
      if ($results['data']) {
        $items = array_merge($items, $results['data']['rows']);
				foreach($items as $doc) {
          if ($doc['type'] == 'page') {
            if (isset($doc['content']['page'])) {
              $objectgroup = $doc;
              $objectgroup['type'] = 'obectgroup';
              $objectgroup['url'] = $doc['content']['url'];
              $objectgroup['id'] = $doc['linkedId'];
            }
            elseif(isset($doc['linkedId'])) {
              $objectgroup = $this->edanRecordManager->getRecord($doc['linkedId'], '[edan:id]');
            }
            if (isset($objectgroup)) {
              $matches[$objectgroup['id']] = $this->getMatch($objectgroup);

            }
            if (!isset($doc['content']['defaultPageId']) || ($doc['id'] != $doc['content']['defaultPageId'])) {
              $matches[$doc['id']] = $this->getMatch($doc);
            }
          }
          else {
            $matches[$doc['id']] = $this->getMatch($doc);
          }
				}
			}
    }
//    return 'Test';
    return new JsonResponse(array_values($matches));
  }

  private function getMatch($doc) {
    $title = $this->edanRecordManager->getTitle($doc);
    $id =  $doc['url'];
    return [
      'value' => $title ." ($id)",
      'label' => "$id: $title"
    ];
  }

}
