<?php

namespace Drupal\edan\Controller;

use Drupal\blazy\Blazy;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\edan\EdanIDS;
use Drupal\edan_record\EdanRecordManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\Routing\RouteCollection;
  use Drupal\slick\SlickDefault;
  use Drupal\slick\Entity\Slick;
  use Drupal\file\Entity\File;
use Drupal\blazy\BlazyUtil;

/**
 * Class TestController.
 */
class TestController extends ControllerBase {

	protected $edanRecord;
	/**
	 * @var \Drupal\Core\Config\ConfigFactoryInterface
	 */
	protected $config;
	/**
	 * The path validator.
	 *
	 * @var \Drupal\Core\Path\PathValidatorInterface
	 */
	protected $pathValidator;

	/**
	 * TestController constructor.
	 */
	public function __construct(EdanRecordManagerInterface $test, PathValidatorInterface $pathValidator ) {
		$this->edanRecord = $test;
		$this->config = $this->edanRecord->configLoad('','edan_record.settings');
		$this->pathValidator = $pathValidator;
	}

	public static function create(ContainerInterface $container) {
		return new static(
			$container->get('edan_record.manager'),
			$container->get('path.validator')
		);
	}
	/**
   * Render.
   *
   * @return string
   *   Return Hello string.
   */
  public function render() {
		$items = [];
	  $record = $this->edanRecord->entityLoad('edanmdm','edan_record');

	  $mediaSettings = $record->getManager()->configLoad('viewer', 'edan_record.settings');
	  dump($mediaSettings);
	 // dump($record);
	    $settings = $record->getRecordSettings();
//  	$hide = $record->get('hidden_fields');
//  	dump($hide);
	  $object = $record->getManager()->getRecord('edanmdm-nasm_A20050452000', '[edan:id]', 'edanmdm', $settings);
	//  dump($object);
//	  $search = $this->edanRecord->entityLoad('default', 'edan_search');
//	  dump($search);
//	  $search->setRows(10);
      $object2 = $record->getManager()->getRecord('edanmdm-sova_nmaahc.sc.0001', '[edan:id]', 'edanmdm', $settings);
   // $object2 = $record->getManager()->getRecord('edanmdm-siris_arc_222142', '[edan:id]', 'edanmdm', $settings);
      dump($object2);
			$blazy_manager = \Drupal::service('blazy.manager');
			$slick_manager = \Drupal::service('slick.manager');
			$settings = SlickDefault::imageSettings();
			$settings['namespace'] = 'slick';
			$settings['optionset'] = 'edan_nav';
			$settings['id'] = \Drupal\blazy\Blazy::getHtmlId('slick', $object['id']);
			$settings['blazy'] = TRUE;
			$settings['lazy'] = 'blazy';
			$settings['_lazy'] = TRUE;
			$optionset = Slick::loadWithFallback('edan_nav');
			$edanMedia = \Drupal::service('edan_record.media');
//			$ext = pathinfo('https://mads.si.edu/assets/player.html?name=https://mads.si.edu/NMAAHC/Collections/NMAAHC-SC_0001_20161216_Swygert_S8_01-31', PATHINFO_EXTENSION);
//    dump( !$ext);

 //   return $edanMedia->buildSlideshow($object);


	  //	dump($search->getRoutes());
//	  $search_manager = \Drupal::service('edan_search.manager');
//	  dump($search_manager->entityLoad('ogmt_results', 'edan_search'));
//	  dump($search_manager->getEdanConnector()->runParamQuery($params));
	  //	dump(\Drupal::service('path.current')->getPath());
	  //$results = $search_manager->executeSearch($search);
	  //$render = $search_manager->renderSearch($search, $results);
//  	dump($render['#facets']);
//	  dump($render['#active_facets']);
//	  dump($results['rows']);
//	  dump(UrlHelper::parse('https://ids.si.edu/ids/deliveryService?max=1200&id=https://collections.nmnh.si.edu/media/?irn=9103787'));
//
//	  $objectgroup = $record->getManager()->getRecord('jdi-1477399204466-1477399204467-0', '[edan:id]', 'objectgroup', $settings);
//	  $pages =$search_manager->getEdanConnector()->getLinkedIdContent('jdi-1477399204466-1477399204467-0');
//	  $keys = array_flip($objectgroup['content']['menu']);
//	  dump($objectgroup);
//	  dump($pages);
//	  foreach($pages as $page) {
//		  if (isset($keys[$page['id']])) {
//			  $items[$keys[$page['id']]] = $page;
//		  }
//	  }
//	  dump(array_flip($objectgroup['content']['menu']));
//	  ksort($items);
//	  dump($items);
//	  drupal_set_installed_schema_version('edan', '8000');

	 // dump($record->getManager()->getRecord('jdi-1477399204466-1477399204467-0', '[edan:id]', 'objectgroup', $settings));
	//  dump($record->getManager()->getRecord('jdi-1477399211022-1477399211023-0','[edan:id]', 'page', $settings));
	 // dump($search_manager->getEdanConnector()->getLinkedIdContent('jdi-1477399204466-1477399204467-0'));
//  	dump($search_manager->getParameters());

	//dump($search_manager->getEdanConnector()->get_museums('si-unit'));
//    $alias = \Drupal::service('path.alias_storage');
//    $url =  Url::fromRoute('entity.edan_search.canonical', ['edan_search' => $search->id()]);
//    dump($url->toString());
//  	dump($alias->save('/edan/search/default', '/collection/search', 'und', $render['pid']));
//  	foreach($records as $entity_id => $entity) {
//  		dump($entity->get('path'));
//		  dump(str_replace($entity->get('path_key'), '{edan_id}', $entity->get('path')));
//	  }
//	  $path = '/object/{edan_id}';
//		$path_validation =  $this->pathValidator->getUrlIfValidWithoutAccessCheck($path);
//		$url = 'https://www.youtube.com/watch?v=6dYGQUsoPUg';
////   // $resource_url = \Drupal::service('media.oembed.url_resolver')->getResourceUrl($url);
//////    dump(\Drupal::service('media.oembed.provider_repository')->getAll());
////		//dump(\Drupal::service('media.oembed.resource_fetcher')->fetchResource($path));
//		dump(UrlHelper::parse($url));
//		$path = 'https://ids.si.edu/ids/deliveryService?max_w=100&max_h=100&id=ark:/65665/m36fbe13d290574876947d24ad8a892f12';
//		dump(UrlHelper::parse($path));
//    $path = 'https://ids.si.edu/ids/deliveryService';
//    dump(UrlHelper::parse($path));
//
//    dump($this->edanRepository->getConfigFactory()->get('edan_search'));
//  	dump($records);
//  	dump(\Drupal::service('edan.connector')->getTypes());
//    $doc = $record->getRecord('health-hygiene-and-beauty:cure-alls-and-salves', 'url', 'page', TRUE);
//    dump($doc);
//    $processor = \Drupal::service('edan_record.process');
////    $processor->getMedia($doc);
////    dump($doc);
//    $processor->processData($doc, $settings);
//    dump($doc);

//
//    $fields = $processor::record_array_flatten($doc['content']);
//    dump($fields);
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: render')
    ];
	//	dump(parse_url('/ids.si.edu/ids/deliveryService?id=NASM-A20050452000-NASM2018-10514-000001&max=150'));
//	  $item = File::create([
//		  'uri' => $object['media'][0]['thumbnail'],
//		  'uid' => 1,
//		  'status' => FILE_STATUS_PERMANENT,
//		  'filename' => $object['media'][0]['idsId'],
//	  ]);

  }

}
