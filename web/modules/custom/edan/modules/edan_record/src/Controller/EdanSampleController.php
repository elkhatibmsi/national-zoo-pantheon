<?php

namespace Drupal\edan_record\Controller;


use Drupal\edan\Connector\EdanConnectorService;
use Drupal\edan_record\EdanRecordProcess;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EdanSampleController extends ControllerBase {

	/**
	 * The edan record process service.
	 *
	 * @var \Drupal\Core\Config\ImmutableConfig
	 */
	private $config;

	/**
	 * The configuration object.
	 *
	 * @var \Drupal\edan_record\EdanRecordProcess
	 */
	private $edanRecordProcess;

	/**
	 * The EDAN Connector Service
	 *
	 * @var \Drupal\edan\Connector\EdanConnectorService
	 */
	private $edanConnectorService;

	/**
	 * Constructs a new EdanSearchController object.
	 */
	public function __construct(EdanRecordProcess $edanRecordProcess, EdanConnectorService $edanConnectorService) {
		$this->edanRecordProcess = $edanRecordProcess;
		$this->edanConnectorService = $edanConnectorService;
	}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('edan_record.process'),
      $container->get('edan.connector')
    );
  }

	/**
	 * Provides /object/{edan_id} page.
	 * @param string $edan_id EDAN Record Id extracted from the path.
	 *
	 * @return array Render array for record_page template.
	 */
	public function view($edan_id) {

//  	$types = $this->edanConnectorService->getTypes();

//    // execute search and get a response
    $separator = strstr($edan_id, ':') ? ':' : '-';
    $type = substr($edan_id, 0, strpos($edan_id, ':'));
    $key = $separator === ':' ? 'url' : 'id';
    $endpoint = $type == 'edanlists' ? 'content/v2.0/admincontent/renderContent.htm' : '';
    try {
			$record = $this->edanConnectorService->getRecord($edan_id, $key, $type, $endpoint);
		} catch (\Exception $e) {
			watchdog_exception('edan_record', $e);
			$record = NULL;
		}

		if (!$record || !$record['data']) {
			throw new NotFoundHttpException('Record not found: ' . $edan_id);
		}
//
//    $build = [];
//    $build['#title'] = $record['data']['title'];
//    $build['record'] = [
//      '#theme' => 'record_page',
//      '#record' => $record,
//      '#settings' => [
//        'thumbnail' => [
//          'constraint' => $constraint,
//          'max_size' => $max,
//          'link' => 'content'
//        ]
//      ],
//    ];

		$doc = $record['data'];

		dump(['raw' => $record['data']]);
    $this->edanRecordProcess->setTeaserView(FALSE);
		$this->edanRecordProcess->constructData($doc);
		$processor = $this->edanRecordProcess;
		if ($doc['type'] === 'location')  {
      $doc['content']['floor_plan'] = 'floor plan';
    }
		if ($doc['type'] === 'event') {
		  $doc['content']['record_link'] = 'Exhibition page on unit website';
		  $doc['content']['online_exhibit'] = 'Online exhibit link';
    }
		$fields = $processor::record_array_flatten($doc['content']);
		$default_fields = $this->sampleFields($doc['type']);

		dump(array_merge($default_fields, $fields));
		return [
			'#type' => 'markup',
			'#markup' => $this->t('Record sample for %type. Keep in mind that the summary and description fields are constructed based on the fields for the the summary configuration.' , ['%type' => $record['data']['type']])
		];
	}

	private function sampleFields($type = NULL) {
		$fields = array(
			'edanmdm' => array(
				'summary',
				'description',
				'freetext:notes',
				'freetext:creditLine',
				'freetext:culture',
				'freetext:dataSource',
				'freetext:date',
				'freetext:identifier',
				'freetext:name',
				'freetext:objectRights',
				'freetext:objectType',
				'freetext:physicalDescription',
				'freetext:place',
				'freetext:publisher',
				'freetext:setName',
				'freetext:taxonomicName',
				'freetext:title',
				'freetext:topic'
			),
			'ead_collection' => array(
				'summary',
				'description',
				'freetext:scopecontent',
				'freetext:abstract',
				'freetext:accessrestrict',
				'freetext:accruals',
				'freetext:acqinfo',
				'freetext:altformavail',
				'freetext:arrangement',
				'freetext:bibliography',
				'freetext:bioghist',
				'freetext:container',
				'freetext:creator',
				'freetext:culture',
				'freetext:custodhist',
				'freetext:dataSource',
				'freetext:genre_format',
				'freetext:identifier',
				'freetext:name',
				'freetext:objectType',
				'freetext:odd',
				'freetext:originalsloc',
				'freetext:otherfindaid',
				'freetext:physdesc',
				'freetext:physloc',
				'freetext:phystech',
				'freetext:place',
				'freetext:prefercite',
				'freetext:processinfo',
				'freetext:relatedmaterial',
				'freetext:separatedmaterial',
				'freetext:setName',
				'freetext:sponsor',
				'freetext:title',
				'freetext:topic',
				'freetext:unitdate',
				'freetext:userestrict'
			),
			'ead_component' => array(
				'summary',
				'description',
				'freetext:scopecontent',
				'freetext:abstract',
				'freetext:accessrestrict',
				'freetext:acqinfo',
				'freetext:container',
				'freetext:creator',
				'freetext:culture',
				'freetext:dataSource',
				'freetext:genre_format',
				'freetext:identifier',
				'freetext:name',
				'freetext:objectType',
				'freetext:odd',
				'freetext:physdesc',
				'freetext:place',
				'freetext:prefercite',
				'freetext:relatedmaterial',
				'freetext:setName',
				'freetext:sponsor',
				'freetext:topic',
				'freetext:unitdate',
				'freetext:userestrict'
			),
			'damsmdm' => array(
				'summary',
				'description',
				'alt_id_1',
				'alt_id_2',
				'alt_id_3',
				'born_digital',
				'box',
				'caption',
				'collection_id',
				'collection_title',
				'container_title',
				'content_type',
				'copyright_date',
				'credit',
				'department',
				'description',
				'ead_ref_id',
				'event',
				'filename',
				'folder',
				'geographic_restrictions',
				'group_title',
				'intellectual_content_creator',
				'IPTC_category',
				'IPTC_city',
				'IPTC_contact_address',
				'IPTC_contact_city',
				'IPTC_contact_country',
				'IPTC_contact_email',
				'IPTC_contact_phone',
				'IPTC_contact_postal',
				'IPTC_contact_state',
				'IPTC_contact_web_url',
				'IPTC_copyright',
				'IPTC_country_code',
				'IPTC_country_name',
				'IPTC_create_date',
				'IPTC_credit',
				'IPTC_headline',
				'IPTC_image_orientation',
				'IPTC_keywords',
				'IPTC_source',
				'IPTC_special_instructions',
				'IPTC_state',
				'IPTC_supplemental_category',
				'is_restricted',
				'keywords',
				'managing_unit',
				'max_ids_size',
				'named_person',
				'online_license_identifier',
				'order_number',
				'other_constraints',
				'path_string',
				'primary_creator',
				'public',
				'related_si_collection_objects',
				'repository_id',
				'rights_holder',
				'rights_summary_core',
				'series_title',
				'source_system_id',
				'terms_and_restrictions',
				'text_equiv',
				'title',
				'uan',
				'unit_code',
				'use_restrictions',
				'version',
				'work_creation_date',
			)
		);
		return isset($fields[$type]) ? array_combine($fields[$type], $fields[$type]) : ['summary' => 'summary', 'description' => 'description'];
	}

}
