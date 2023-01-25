<?php

namespace Drupal\edan_tabs\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\edan_search\EdanSearchManagerInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\path_alias\Entity\PathAlias;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EdanTabEntityForm.
 */
class EdanTabEntityForm extends EntityForm {
  /**
   * The edan search manager.
   *
   * @var Drupal\edan_search\EdanSearchManager;
   */
  protected $edanSearchManager;

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
   * The path alias storage helper.
   *
   * @var Drupal\path_alias\Entity\PathAlias
   */
  protected $pathAlias;

  /**
   * Constructs a new search form.
   *
   * @param \Drupal\edan_search\EdanSearchManagerInterface $edanSearchManager
   *   The search page repository.
   */
  public function __construct(EdanSearchManagerInterface $edanSearchManager, $pathValidator, $requestContext, $pathAlias) {
    $this->edanSearchManager = $edanSearchManager;
    $this->pathValidator = $pathValidator;
    $this->requestContext = $requestContext;
    $this->pathAlias = $pathAlias;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('edan_search.manager'),  $container->get('path.validator'), $container->get('router.request_context'), \Drupal::entityTypeManager()->getStorage('path_alias'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $edan_tab = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search Page Title'),
      '#maxlength' => 255,
      '#default_value' => $edan_tab->label(),
      '#description' => $this->t("Label for the Edan tab."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $edan_tab->id(),
      '#machine_name' => [
        'exists' => '\Drupal\edan_tabs\Entity\EdanTab::load',
      ],
      '#disabled' => !$edan_tab->isNew(),
    ];


    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#field_prefix' => $this->requestContext->getCompleteBaseUrl(),
      '#default_value' => $this->entity->getPath(),
      '#maxlength' => '255',
      '#required' => TRUE,
    ];

    $form['old_path'] = [
      '#type' => 'value',
      '#value' => $form_state->getValue('old_path') ?? $this->entity->getPath(),
    ];
    $form['landing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create landing page'),
      '#default_value' => $this->entity->get('landing'),
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'edan-tabs-wrapper',
      ],
    ];

    $form['landing_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label for landing tab'),
      '#default_value' => $this->entity->get('landing_label'),
      '#states' => [
        // The state being affected is "invisible".
        'invisible' => [
          // Drupal will only apply this state when the element that satisfies
          // the selector input[name="needs_accommodation"] is un-checked.
          ':input[name="landing"]' => ['checked' => FALSE],
        ],
        'required' => [
          // Drupal will only apply this state when the element that satisfies
          // the selector input[name="needs_accommodation"] is un-checked.
          ':input[name="landing"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $options = [];

    $custom_searches = $this->edanSearchManager->entityLoadMultiple('edan_search');
    foreach ($custom_searches as  $entity_id => $search) {
      $options[$entity_id] = $search->label();
    }
    $tab_state = $form_state->get('edan_tabs');

    // Store field information in $form_state.
    if (!$tab_state) {
      $tabs = $edan_tab->get('searches');
      $tab_state = [
        'items_count' => count($tabs),
        'searches' => $tabs,
      ];
      $form_state->set('edan_tabs', $tab_state);
    }
    $form['searches'] = [
      '#type' => 'table',
      '#prefix' => '<div id="edan-tab-wrapper">',
      '#suffix' => '</div>',
      '#header' => [
        $this->t('Custom Search'),
        $this->t('Tab Title'),
        $this->t('Landing Page Column'),
        $this->t('# Results/Landing Page'),
        $this->t('Landing Display Mode'),
        $this->t('Weight'),
      ],
      // TableDrag: Each array value is a list of callback arguments for
      // drupal_add_tabledrag(). The #id of the table is automatically
      // prepended; if there is none, an HTML ID is auto-generated.
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'table-sort-weight',
        ],
      ],
    ];
//    dump(['tabs' => $tab_state]);
    $max = $tab_state['items_count'] == 0 ? 0 : $tab_state['items_count'] - 1;
    // Build the table rows and columns.
    //
    // TableDrag: Mark the table row as draggable.
    for ($delta = 0; $delta <= $max; $delta++) {
      $form['searches'][$delta]['#attributes']['class'][] = 'draggable';

      $tab = $tab_state['searches'][$delta] ?? [];
      // TableDrag: Sort the table row according to its existing/configured
      // weight.
      $form['searches'][$delta]['#weight'] = $tab['weight'] ?? $delta;

      // Some table columns containing raw markup.
      $form['searches'][$delta]['search'] = [
        '#type' => 'select',
        '#options' => $options,
        '#required' => TRUE,
        '#default_value' => $tab['search'] ?? NULL,
        '#disabled' => isset($tab['search'])
      ];
      $label = $tab['label'] ?? '';
      $form['searches'][$delta]['label'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#default_value' => $label,
      ];
      $form['searches'][$delta]['column'] = [
        '#type' => 'number',
        '#min' => 1,
        '#max' => 2,
        '#default_value' => $tab['column'] ?? 1,
        '#states' => [
          // The state being affected is "invisible".
          'invisible' => [
            // Drupal will only apply this state when the element that satisfies
            // the selector input[name="needs_accommodation"] is un-checked.
            ':input[name="landing"]' => ['checked' => FALSE],
          ],
        ],
      ];

      $form['searches'][$delta]['resultCount'] = [
        '#type' => 'number',
        '#min' => 0,
        '#max' => 50,
        '#default_value' => $tab['resultCount'] ?? 8,
        '#states' => [
          // The state being affected is "invisible".
          'invisible' => [
            // Drupal will only apply this state when the element that satisfies
            // the selector input[name="needs_accommodation"] is un-checked.
            ':input[name="landing"]' => ['checked' => FALSE],
          ],
        ],
      ];
      $display = [
        'grid' => 'Grid',
        'list' => 'List'
      ];
      if (\Drupal::service('module_handler')->moduleExists('masonry')) {
        $display['masonry'] = 'Masonry Grid';
      }
      $form['searches'][$delta]['display_mode'] = [
        '#type' => 'radios',
        '#options' => $display,
        '#default_value' => $tab['display_mode'] ?? 'grid',
        '#states' => [
          // The state being affected is "invisible".
          'invisible' => [
            // Drupal will only apply this state when the element that satisfies
            // the selector input[name="needs_accommodation"] is un-checked.
            ':input[name="landing"]' => ['checked' => FALSE],
          ],
        ],
      ];
      // TableDrag: Weight column element.
      $form['searches'][$delta]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $label]),
        '#title_display' => 'invisible',
        '#default_value' =>  $tab['weight'] ?? $delta,
        // Classify the weight element for #tabledrag.
        '#attributes' => ['class' => ['table-sort-weight']],
      ];
      if ($tab) {
        $form['searches'][$delta]['remove'] = [
          '#type' => 'submit',
          '#name' => $delta. '_remove_button',
          '#value' => $this->t('Remove'),
          '#delta' => $delta,
          '#submit' => ['::removeTab'],
          '#ajax' => [
            'callback' => '::ajaxCallback',
            'wrapper' => 'edan-tab-wrapper',
          ],
        ];

      }
    }
    $form['add_name'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add one more'),
      '#submit' => ['::addOne'],
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'edan-tab-wrapper',
      ],
    ];

    return $form;
  }

  public function afterBuild(array $element, FormStateInterface $form_state) {
    parent::afterBuild($element, $form_state);
    if ($form_state->isProcessingInput()) {
      $has_landing = $this->entity->hasLanding() ?  TRUE : FALSE;
      foreach ($this->entity->get('searches') as $delta => $search) {
        $element['searches'][$delta]['column']['#access'] = $has_landing;
        $element['searches'][$delta]['resultCount']['#access'] = $has_landing;
        $element['searches'][$delta]['grid']['#access'] = $has_landing;
      }
    }
    return $element;
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    return $form['searches'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $tab_state = $form_state->get('edan_tabs');
    $tab_state['items_count'] = $tab_state['items_count'] + 1;
    $searches = $form_state->getValue('searches');
    $tab_state['searches'] = $this::sortSearch($searches);
    $form_state->set('edan_tabs', $tab_state);
    // Since our buildForm() method relies on the value of 'num_names' to
    // generate 'name' form elements, we have to tell the form to rebuild. If we
    // don't do this, the form builder will not call buildForm().
   $form_state->setRebuild();
  }

  /**
   * Submit handler for the "Remove" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function removeTab(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $tab_state = $form_state->get('edan_tabs');
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $weight = $element['#weight'];
    $searches = $form_state->getValue('searches');
    if (isset($searches[$weight])) {
      unset($searches[$weight]);
    }
    $searches = array_values($searches);
    $tab_state['searches'] = $this::sortSearch($searches);
    $tab_state['items_count'] = $tab_state['items_count'] - 1;
    $form_state->set('edan_tabs', $tab_state);
    // Update both $form_state->getValues() and FormState::$input to reflect
    // that the file has been removed, so that the form is rebuilt correctly.
    // $form_state->getValues() must be updated in case additional submit
    // handlers run, and for form building functions that run during the
    // rebuild, such as when the managed_file element is part of a field widget.
    // FormState::$input must be updated so that
    // \Drupal\file\Element\ManagedFile::valueCallback() has correct information
    // during the rebuild.
    $form_state->setValueForElement($form['searches'], $searches);
    NestedArray::setValue($form_state->getUserInput(), ['searches'], $searches);

    $form_state->setRebuild();
  }
  public function validateForm(array &$form, FormStateInterface $form_state) {

    parent::validateForm($form, $form_state);

    // Validate record page path.
    $path = trim($form_state->getValue('path'));
    $alias = $this->pathAlias->loadByProperties(['alias' => $path]);
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

    if ($this->entity->isNew()) {
      if ($this->pathValidator->getUrlIfValidWithoutAccessCheck($path) || $alias) {
        $form_state->setErrorByName(
          'path',
          $this->t("The path '%path' is already in use.  Please use a unique path.", ['%path' => $path])
        );
      }
    }
    elseif ($form_state->getValue('old_path') != $path) {
      if ($this->pathValidator->getUrlIfValidWithoutAccessCheck($path) || $alias) {
        $form_state->setErrorByName(
          'path',
          $this->t("The path '%path' is already in use.  Please use a unique path.", ['%path' => $path])
        );
      }
    }
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $searches = $form_state->getValue('searches');
    $searches = $this::sortSearch($searches);
      if ($form_state->getValue('landing') == FALSE) {
        foreach($searches as $delta => $search) {

          unset($searches[$delta]['resultCount']);
        unset($searches[$delta]['column']);
        unset($searches[$delta]['display_mode']);
      }
    }
    $this->entity->set('searches', $searches);
  }

  public static function sortSearch($searches) {
    $items = [];
    $delta = 0;
    foreach($searches as $search) {
      $search['weight'] = $delta;
      $items[$delta] = $search;
      $delta++;
    }
    return $items;
  }
  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $edan_tab = $this->entity;
    $status = $edan_tab->save();
    $searches = $edan_tab->get('searches');
    foreach($searches as $delta => $search) {
      $edanSearch = $this->edanSearchManager->entityLoad($search['search'], 'edan_search');
      //$edanSearch->set('status', FALSE);

      $edanSearch->disable();
      $edanSearch->save();

    }
      drupal_flush_all_caches();
    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Edan tab.', [
          '%label' => $edan_tab->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Edan tab.', [
          '%label' => $edan_tab->label(),
        ]));
    }
//    $form_state->setRebuild();

     $form_state->setRedirectUrl($edan_tab->toUrl('collection'));
  }

}
