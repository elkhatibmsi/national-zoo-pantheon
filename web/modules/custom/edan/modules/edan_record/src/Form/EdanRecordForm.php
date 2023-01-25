<?php

namespace Drupal\edan_record\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\edan\EdanFormHelper;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EdanRecordForm.
 */
class EdanRecordForm extends EntityForm {

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->pathValidator = $container->get('path.validator');
    $instance->requestContext = $container->get('router.request_context');
    return $instance;
  }



  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    // Change page title for the duplicate operation.
    if ($this->operation == 'duplicate') {
      $form['#title'] = $this->t('<em>Duplicate record configuration</em>: @label', ['@label' => $this->entity->label()]);
      $this->entity = $this->entity->createDuplicate();
    }
    $configuration = $this->entity;
    //	dump($configuration);
    $contentTypes = $configuration->getManager()->getEdanConnector()->getTypes();
    if (isset($contentTypes['page'])) unset($contentTypes['page']);
    $types = $configuration->getManager()->entityLoadMultiple();
    $type_keys = array_keys($types);
    $configTypes = $type_keys ? array_combine($type_keys, $type_keys) : [];
    $formHelper = \Drupal::service('edan.form_helper');

    // Change page title for the edit operation.
    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('<em>Edit record configuration</em>: @label', ['@label' => $this->entity->label()]);
    }
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $configuration->label(),
      '#description' => $this->t("Label for the Edan record."),
      '#access' => FALSE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $configuration->id(),
      '#machine_name' => [
        'exists' => '\Drupal\edan_record\Entity\EdanRecord::load',
      ],
      '#access' => FALSE,
      //	'#disabled' => !$configuration->isNew(),
    ];
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Allowed Record Types'),
      '#options' => $configuration->isNew() ? array_diff_key($contentTypes, $configTypes) : $contentTypes,
      '#disabled' => !$configuration->isNew(),
      '#default_value' => $configuration->isNew() && $form_state->getValue('type') ? $form_state->getValue('type') : $configuration->id(),
      '#required' => TRUE,
      '#ajax' => [
        'wrapper' => 'record-type-ajax-wrapper',
        'callback' => '::edanRecordCallback',
      ],
    ];

//		$form['sample'] = [
//			'#prefix'     => '<div id="record-type-ajax-wrapper">',
//			'#suffix'     => '</div>',
//			];

    $type = $form_state->getValue('type') ? $form_state->getValue('type') : $configuration->id();
    $form['settings'] = [
      '#type' => 'container',
      '#prefix'     => '<div id="record-type-ajax-wrapper">',
      '#suffix'     => '</div>',
    ];

    if ($type) {
      $url = $this->getSampleURL($type);
      $form['settings']['sample']= [
        '#markup' => $this->t('View a <a href="@link" target="_blank">sample :type page</a>. Keep in mind that the summary and description fields are constructed based on the fields for the the summary configuration.
																	Including the summary configuration fields and the the summary and/or description field will result in duplicate', ['@link' => $url->toString(), ':type' => $type])
      ];
    }

    $form['settings']['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Record Path'),
      '#default_value' => $configuration->get('path') ?? '[edan:url]',
//			'#description' => $this->t('The @type path for record pages. You can use the [edan:url], [edan:url_title], or [edan:id] in the path.  Use the url title or record id token if you are not setting paths for each content type.', ['@type' => $type]),
      '#description' => $this->t('The path for record pages. You can use the [edan:url], [edan:title], [edan:url_title], or [edan:id] in the path.  If you use [edan:title], then please add one of the other tokens. Only use [edan:url] for objectgroups.'),
      '#field_prefix' => $this->requestContext->getCompleteBaseUrl(),
    ];

    $form['old_path'] = [
      '#type' => 'value',
      '#value' => $form_state->getValue('old_path') ??  $configuration->get('path'),
    ];

    $form['settings']['summary_fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Summary field(s)'),
      '#required' => TRUE,
      '#default_value' => $form_state->getValue('summary_fields') ?  $formHelper->generateTextAreaValues($form_state->getValue('summary_fields') ) : $formHelper->generateTextAreaValues($configuration->get('summary_fields')),
      '#description' => $this->t('Specify the field(s) for the record\'s summary.  Separate each field with a comma.  Only include 1-3 fields. Replace "." with ":", i.e. freetext.setName should be freetext:setName.
			    The information will be used for the description token and set the description metadata.'),
      '#access' => $type !== 'objectgroup'
    ];
    $form['settings']['record_fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Record Page field(s)'),
      '#required' => TRUE,
      '#default_value' => $form_state->getValue('record_fields') ?  $formHelper->generateTextAreaValues($form_state->getValue('record_fields') ) : $formHelper->generateTextAreaValues($configuration->get('record_fields')),
      '#description' => $this->t('Fields to show on a record page. Each field should be on its own line and should be placed in the order that they should be displayed. Replace "." with ":", i.e. freetext.setName should be freetext:setName. Use * to show all fields.
			If you want to specify a set of fields and then show the remaining add an * as the last line.  3d package records will display the fields for the associate edanmdm.'),
      '#access' => $type !== 'objectgroup'
    ];
    $form['settings']['hidden_fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Hidden field(s)'),
      '#default_value' => $form_state->getValue('hidden_fields') ?  $formHelper->constructHiddenText($form_state->getValue('hidden_fields') ) : $formHelper->constructHiddenText($configuration->get('hidden_fields')),
      '#description' => $this->t('Metadata fields that are hidden on the record page. Each field should be on its own line. Replace "." with ":", i.e. freetext.setName should be freetext:setName
        If you want to hide a value within a field, then enter it using this format field name|Label. Ex. freetext:setName|See more items in'),
      '#access' => $type !== 'objectgroup'
    ];
    $form['settings']['teaser_fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Search field(s)'),
      '#default_value' => $formHelper->generateTextAreaValues($configuration->get('teaser_fields')),
      '#description' => $this->t('Search results will display title and image.  Replace "." with ":", i.e. freetext.setName should be freetext:setName.  List any fields that you want to display for the search results. Use * to show all fields.'),
    ];
    $form['settings']['label_replacements'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Label Replacement'),
      '#default_value' => $formHelper->generateTextAreaValues($configuration->get('label_replacements'), TRUE),
      '#description' => $this->t('Replace the labels shown with a different label. Replace "." with ":", i.e. freetext.setName should be freetext:setName. When making this list, do not list the "facet" name, but the "label." For example the metadata facet, physicalDescription, has a label "Physical Description" -- For this to appear on the object listing as "Phys. Descr." you enter the following line (without quotes) "Physical Description | Phys. Descr." -- notice the pipe "|" character between the label and desired replacement. Replacements are not case sensitive.'),
      '#access' => $type !== 'objectgroup'
    ];

    return parent::form($form, $form_state);
  }

  public function edanRecordCallback(array $form, FormStateInterface $form_state) {
    return $form['settings'];
  }

  protected function getSampleURL($type) {
    $examples = array(
      'edanmdm' => 'edanmdm:nmah_670130',
      'event' => 'event:event-exhib-6454',
      'location' => 'location:african-american-museum',
      '3d_package' => 'edanmdm:nasm_A19700102000',
      'si-unit' => 'si-unit:air-and-space-museum',
      'damsmdm' => 'damsmdm:NASM-NASM2016-04220',
      'ead_collection' => 'ead_collection:sova-acma-06-105',
      'ead_component' => 'ead_component:sova-acma-06-067-ref521'
    );
    if (isset($examples[$type])) {
      return Url::fromRoute('edan_record.sample_page', ['edan_id' => $examples[$type]]);
    }
    elseif ($type === 'edanlists') {
      $results = \Drupal::service('edan.connector')->runParamQuery(['rows' => 1], 'lists/v2.0/adminlists/getLists.htm');
      return isset($results['data']) ? Url::fromRoute('edan_record.sample_page', ['edan_id' => $results['data']['rows'][0]['url']]) : NULL;
    }
    else {
      $params = [
        'rows' => 1,
        'fqs' => ['type:'. $type]
      ];
      $results = \Drupal::service('edan.connector')->runParamQuery($params);
      return isset($results['data']) ? Url::fromRoute('edan_record.sample_page', ['edan_id' => $results['data']['rows'][0]['url']]) : NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $type = $form_state->getValue('type');
    $record =   $this->entity->getManager()->entityLoad($type, 'edan_record') ;
    $tokens = ['[edan:url]', '[edan:url_title]', '[edan:id]'];
    $path_token = NULL;
    $tokenized = $multiple = FALSE;
    $formHelper = \Drupal::service('edan.form_helper');
    $summary = $formHelper->extractTextAreaValues($form_state->getValue('summary_fields'));
//			if (in_array('description', $summary) && $type !=='objectgroup' ) {
//					$form_state->setErrorByName(
//						'summary_fields',
//						$this->t('You cannot include description field in the summary configuration as those fields are dependent on this configuration.')
//					);
//			}
//			elseif ( in_array('summary', $summary) && $type !=='event') {
//				$form_state->setErrorByName(
//					'summary_fields',
//					$this->t('You cannot include summary field in the summary configuration as those fields are dependent on this configuration.')
//				);
//			}
    if ($this->entity->isNew() && $record) {
      $form_state->setErrorByName(
        'type',
        $this->t('This record type has already been configured. Visit the <a href=":url">EDAN Records</a> page to edit the current configuration.', [':url' => Url::fromRoute('entity.edan_record.collection')->toString()])
      );
    }


    // Validate record page path.
    $path = trim($form_state->getValue('path'));
    $path_array = explode('/', $path);
    $path_array = array_map('trim', $path_array);
    $path = implode('/', $path_array);
    if ($path && $path[0] !== '/') {
      $form_state->setErrorByName(
        'path',
        $this->t(
          "The path '%path' has to start with a slash.",
          ['%path' => $path]
        )
      );
    }

    if ($path && $path[strlen($path) - 1] === '/') {
      $form_state->setErrorByName(
        'path',
        $this->t(
          "The path '%path' cannot end with a slash.",
          ['%path' => $path]
        )
      );
    }
    if (in_array($path_array[1], $tokens)) {
      $form_state->setErrorByName(
        'path',
        $this->t(
          "The path token cannot be the first part of the path."
        )
      );
    }

    foreach ($tokens as $token) {
      if (strstr($path, $token)) {
        $path_token = $token;
        if ($tokenized) {
          $form_state->setErrorByName(
            'path',
            $this->t(
              "You can only use one token for the path."
            )
          );
        }
        else {
          $tokenized = TRUE;
        }
      }
    }
    if (!$path_token) {
      $form_state->setErrorByName(
        'path',
        $this->t(
          "You must use one of the valid tokens in the path."
        )
      );
    }
    elseif ($type === 'objectgroup' && $path_token !== '[edan:url]') {
      $form_state->setErrorByName(
        'path',
        $this->t(
          "You can only use [edan:url] for objectgroups."
        )
      );
    }
    else {
      $form_state->setValue('path_token', $path_token);
    }
    if ($this->entity->isNew()) {
      if ($this->pathValidator->getUrlIfValidWithoutAccessCheck($path)) {
        $form_state->setErrorByName(
          'record_path',
          $this->t("The path '%path' already exists.", ['%path' => $path])
        );
      }

    }
    elseif ($form_state->getValue('old_path') != $path) {
      $edanManager = \Drupal::service('edan_record.process');
      $entities = $edanManager->entityLoadMultiple();
      $entityType = $form_state->getValue('type');
      $used = FALSE;
      foreach ($entities as $type => $entity) {
        if ($type != $entityType) {
          if ($path === $entity->get('path')) {
            $used = TRUE;
            break;
          }
        }
      }
      if ($used) {
        $form_state->setErrorByName(
          'record_path',
          $this->t("The path '%path' already exists.", ['%path' => $path])
        );
      }
    }
    parent::validateForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $configuration = $this->entity;
    $formHelper = \Drupal::service('edan.form_helper');

    // set label and id based on edan record type
    if ($this->entity->isNew()) {
      $type = $form_state->getValue('type');
      $configuration->set('id', $type)
        ->set('label', $type);
    }
    $textAreaKeys = ['summary_fields', 'record_fields', 'hidden_fields', 'teaser_fields', 'label_replacements'];
    foreach($textAreaKeys as $key) {
      if ($form_state->getValue($key)) {
        if ($key === 'hidden_fields') {
          $hide =  $formHelper->extractTextAreaValues($form_state->getValue($key));
          $value = $formHelper->constructHiddenArray($hide);
        }
        else {
          $value = $key === 'label_replacements' ? $formHelper->extractTextAreaValues($form_state->getValue($key), TRUE) : $formHelper->extractTextAreaValues($form_state->getValue($key));
        }
      }
      else {
        $value = [];
      }
      $configuration->set($key, $value);

    }
    //set record fields for objectgroup
    if ($form_state->getValue('type') === 'objectgroup') {
      $configuration->set('record_fields', ['description' => 'description', 'content' => 'content']);
    }
    $path = trim($form_state->getValue('path'));
    $path_array = explode('/', $path);
    $path_array = array_map('trim', $path_array);
    $path = implode('/', $path_array);
    $configuration->set('path', $path);
    $path_token = $form_state->getValue('path_token');
    if (!$path_token) {
      $tokens = ['[edan:url]', '[edan:url_title]', '[edan:id]'];
      foreach ($tokens as $token) {
        if (strstr($path, $token)) {
          $path_token = $token;
          break;
        }
      }
    }
    $configuration->set('path_key', $path_token);
//		dump($configuration);
    $form_state->setRebuild();
    $status = $configuration->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Edan record.', [
          '%label' => $configuration->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Edan record.', [
          '%label' => $configuration->label(),
        ]));
    }
    // $form_state->setRebuild();
    $form_state->setRedirectUrl($configuration->toUrl('collection'));
  }

}
