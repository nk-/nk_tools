<?php

namespace Drupal\nk_tools\Plugin\Block;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;

use Drupal\Component\Utility\NestedArray;

use Drupal\filter\Render\FilteredMarkup;

use Drupal\nk_tools\NkToolsBase;
use Drupal\nk_tools\NkToolsBlockBaseInterface;

class NkToolsBlockBase extends BlockBase implements ContainerFactoryPluginInterface, NkToolsBlockBaseInterface {
  
  /**
   * Drupal\Core\Entity\EntityTypeManager definition
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Entity\EntityFieldManager definition
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Drupal\Core\Entity\EntityDisplay definition
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory; 

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;


  /**
   * The module handler.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRoute;


  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;
  
  /**
   * Drupal\nk_tools\NkToolsBase definition.
   *
   * @var \Drupal\nk_tools\NkToolsBase
   */
  protected $nkToolsFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, EntityDisplayRepositoryInterface $entity_display_repository, ConfigFactoryInterface $config_factory, RendererInterface $renderer, CurrentRouteMatch $current_route, ModuleHandlerInterface $module_handler, NkToolsBase $nk_tools_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->configFactory = $config_factory; 
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
    $this->currentRoute = $current_route;
    $this->nkToolsFactory = $nk_tools_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_display.repository'),
      $container->get('config.factory'),
      $container->get('renderer'),
      $container->get('current_route_match'),
      $container->get('module_handler'),
      $container->get('nk_tools.main_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'block_label' => [
        'value' => NULL,
        'format' => 'basic_html'
      ],
      'icon' => NULL,
      'icon_back' => NULL,
      'hide_mobile' => NULL,
      'hide_desktop' => NULL,
      'hide_init' => NULL,
      'additional_class' => NULL,
      'animation_in' => NULL,
      'animation_out' => NULL,
      'target' => NULL,
 
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    // Custom composite element
    $form['nk_tools_fields'] = [
      '#type' => 'nk_tools_block_fields',
      '#title' => $this->t('Nk tools base settings'),
      '#open' => TRUE,
      '#default_value' => $config
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    
    parent::blockSubmit($form, $form_state);
    
    $values = $form_state->getValues();
    
    if (isset($values['nk_tools_fields']) && !empty($values['nk_tools_fields'])) {
    
      foreach ($values['nk_tools_fields'] as $field_id => $field_value) {
        if (is_array($field_value)) {
          if (isset($field_value['value'])) {
            $this->configuration[$field_id]['value'] = !empty($field_value['value']) ? $field_value['value'] : NULL;
            if (isset($field_value['format'])) {
              $this->configuration[$field_id]['format'] = $field_value['format'];
            }
          }
          else {
            foreach ($field_value as $id => $value) {
              $this->configuration[$id] = $value ? $value : NULL;
            }
          }
        }
        else {
          $this->configuration[$field_id] = $field_value ? $field_value : NULL;
        } 
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
  
    $config = $this->getConfiguration();
    $nk_tools_config = $this->configFactory->get('nk_tools.settings')->getRawData();

    $build = [
      '#attributes' => [
        'class' => [
          'nk-tools-block'
        ]
      ],
    ]; 

    // Checkboxes
    if (isset($config['hide_init']) && $config['hide_init'] > 0) {
      $build['#attributes']['class'][] = isset($nk_tools_config['layout']['hidden_class']) ? $nk_tools_config['layout']['hidden_class'] : NULL;
    }
    
    if (isset($config['hide_mobile']) && $config['hide_mobile'] > 0) {
      $build['#attributes']['class'][] = isset($nk_tools_config['layout']['desktop_only_class']) ? $nk_tools_config['layout']['desktop_only_class'] : NULL;
    }
    else { // We do not want both classes, in case user checked both checkboxes
      if (isset($config['hide_desktop']) && $config['hide_desktop'] > 0) {
        $build['#attributes']['class'][] = isset($nk_tools_config['layout']['mobile_only_class']) ? $nk_tools_config['layout']['mobile_only_class'] : NULL;
      }
    }

    // Textfields
    if (isset($config['icon']) && !empty($config['icon'])) {
      $build['#attributes']['data-icon'] = $config['icon'];
    }

    if (isset($config['icon_back']) && !empty($config['icon_back'])) {
      $build['#attributes']['icon_back'] = $config['icon_back'];
    }

    if (isset($config['additional_class']) && !empty($config['additional_class'])) {
      $build['#attributes']['class'][] = $config['additional_class'];
    }
  
    $animation_in = isset($config['animation_in']) && !empty($config['animation_in']) ? $config['animation_in'] : NULL;
    $animation_out = isset($config['animation_out']) && !empty($config['animation_out']) ? $config['animation_out'] : NULL;

    if ($animation_in || $animation_out) {
      // First set parent animation classs, as set on my module's config, i.e. "animated"
      $build['#attributes']['class'][] = isset($nk_tools_config['layout']['animate_class']) ? $nk_tools_config['layout']['animate_class'] : NULL;
    
      // Set in and out animations
      if ($animation_in) {
        // Note that it goes to data-target-in/out attribute rather than setting "in" class directly
        $build['#attributes']['data-target-in'] = $animation_in;
        // However, if block is omt initially hidden, as per config checkbox then we should standard run the animation on init
        if (!isset($config['hide_init']) || $config['hide_init'] < 1) {
          $build['#attributes']['class'][] = $animation_in;
        }
      }
      // Animation "out" certainly do not get set on init
      if ($animation_out) {
        $build['#attributes']['data-target-out'] = $animation_out;
      }
    }

    // Block label, formatted textfield element
    if (isset($config['block_label']) && isset($config['block_label']['value']) && !empty($config['block_label']['value'])) {
      $build['#title'] = FilteredMarkup::create($config['block_label']['value']);
    }

    return $build;
  }

  /**
   * Check current form state values (if coming from ajax or not) 
   */
  protected function getCurrentFormState(object $form_state) {
    if ($form_state instanceof SubformStateInterface) {
      $values = $form_state->getCompleteFormState()->getValues();
      return isset($values['settings']) ? $values['settings'] : $values;
    }
    else {
      return $form_state->getValues();
    }
  }

  protected function getCurrentValues(array $values, string $key, array $config, string $subkey = 'reference', $delta = NULL) {

    $reference = NULL;
    $container_values = $delta !== NULL ? $values[$key][$delta] : $values[$key];
    $container_config = $delta !== NULL ? $config[$key . '_' . $subkey][$delta] : $config[$key . '_' . $subkey];

    if (isset($container_values[$key . '_' . $subkey]) && !empty($container_values[$key . '_' . $subkey])) {
      $reference = $container_values[$key . '_' . $subkey];
    }
    else {
      if ($container_config) {
        $reference = $container_config;
      }
    }

    if ($key == 'view' && $reference) {
      
      $references = [
        'view_id' => $reference
      ];
      
      if (isset($container_values['display']) && isset($container_values['display']['display_id']) && !empty($container_values['display']['display_id'])) {
         $references['display_id'] = $container_values['display']['display_id'];
         
      }
      else {
        if ($container_config) {
           $references['display_id'] = $config['display_id'];
        }
      }
      return $references;
    }

    return $reference;
  }

  protected function generateChildren(array $entity_data, array $config, $delta = NULL) {
    
    $children = [];
    $entity_key = strpos($entity_data['key'], 'node') !== FALSE ? 'node' : $entity_data['key'];

    $displays = isset($entity_data['displays']) ? $entity_data['displays'] : $this->entityDisplayRepository->getViewModeOptionsByBundle($entity_key, $entity_data['bundle']); 

    if ($displays !== FALSE) {
      $children[$entity_data['key'] . '_display'] = [
        '#title'  => $this->t('Display mode'),
        '#description' => $this->t('Choose a display mode for the @entity. Means what is set as formatting there for these fields will render. Set to "- None -" to print unformatted value.', ['@entity' => $entity_data['label']]),
        '#type' => 'select',
        '#options' => is_array($displays) && !empty($displays) ? $displays : [],
        '#weight' => 2,
        '#default_value' => $config[$entity_data['key'] . '_display'],
        '#states' => [ 
          'visible' => [
             ':input[id="nk-tools-' . $entity_data['key'] . '-reference"]' => ['filled' => TRUE],
          ],
        ],
      ];
    
      if ($entity_data['empty_option']) {
        $children[$entity_data['key'] . '_display']['#empty_option'] =  $entity_data['empty_option'];
      }
    }

    $children[$entity_data['key'] . '_ui_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label(s) for tabs or collapsible panes'),
      '#description' => $this->t('A comma separated label(s), one for each tab or collapsible toggle that entity selected here provides in render. Leave blank if "Unformatted" is selected as target UI.'),
      '#default_value' => $delta !== NULL && is_array($config[$entity_data['key'] . '_delta']) ? $config[$entity_data['key'] . '_ui_label'][$delta] : $config[$entity_data['key'] . '_ui_label'],
      '#states' => [
        'visible' => [
           ':input[id="nk-tools-' . $entity_data['key'] . '-reference"]' => ['filled' => TRUE],
        ],
      ],
      '#weight' => 10,

    ];

    $skip = ['webform', 'view'];

    if (!in_array($entity_data['key'], $skip)) {
      $children[$entity_data['key'] . '_single'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Single render'),
        '#description' => $this->t('By default each @key instance renders separate (i.e. separate tab / collapsible pane). Check this if you prefer it to load as one in the sequence.',['@key' => $entity_data['key'] .'\'s']),
        '#default_value' => $config[$entity_data['key'] . '_single'],
         '#weight' => 11,
      ];
    }

    $children[$entity_data['key'] . '_delta'] = [
      '#type' => 'number',
      '#title' => $this->t('Weight'),
      '#description' => $this->t('Set weight for this data in the render/template. Labels (for tabs or collapsible pane UI) above should follow. Note also that multiple values for one field renders into <strong>one tab/pane</strong>'),
      '#default_value' => $delta !== NULL && is_array($config[$entity_data['key'] . '_delta']) ? (int)$config[$entity_data['key'] . '_delta'][$delta] : (int)$config[$entity_data['key'] . '_delta'],  
      '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
        'visible' => [
           ':input[id="nk-tools-' . $entity_data['key'] . '-reference"]' => ['filled' => TRUE],
        ],
      ],
      '#weight' => 12,
    ];

    return $children;

  }

  /**
   * Parse entity data, either by provided target_id or string (from autocomplete)
   */
  protected function getBundle(array $reference = [], $entity_type = NULL) {
    $entities = [];
    if (!empty($reference)) { 
      foreach ($reference as $index => $entity) {

        if (isset($entity['target_id']) && !empty($entity['target_id'])) {
          if (is_numeric($entity['target_id'])  && $entity_type) {
            $entity_object = $this->entityTypeManager->getStorage($entity_type)->load($entity['target_id']);
            if (is_object($entity_object)) {
              $entities[] = [
                'entity_type' => $entity_type,
                'entity_object' => $entity_object,
              ];
            }
          }
          else {
            $split = explode('.', $entity['target_id']);
            if (count($split) > 2) {
              //str_replace('node.issue.', '', $field['target_id']); // : NULL;
              $entities[] = isset($split[2]) && !empty($split[2]) ? ['entity_type' => $split[0], 'bundle' => $split[1], 'field_name' => $split[2]] : [];
            }
          }
        }
      }
    }
    return $entities;
  }

  /**
   * View element building method.
   */
  protected function viewElement(array &$form, FormStateInterface $form_state, array $values, array $config) {
  
    // Gather the number of referenced views in the form already.
    $num_views = $form_state->get('num_views');
    // We have to ensure that there is at least one widget
    if ($num_views === NULL) {
      if (isset($config['view_id']) && count($config['view_id']) > 1) {
        $num_views = count($config['view_id']);
      }
      else {
        $num_views = 1;
      }
    }

    $form_state->set('num_views', $num_views);

    // Container for out custom composite view reference element
    $form['view'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#attributes' => [
        'id' => 'nk-tools-ajax-wrapper-view'
      ] 
    ];
   
    $input = $form_state->getUserInput();

    for ($delta = 1; $delta <= $num_views; $delta++) {

      $view_id_default_value = NULL;
   
      $index = $delta - 1;

      $view_id = NULL;
      $display_id = NULL;

      if (isset($input['settings']['view']) && isset($input['settings']['view'][$index]) && isset($input['settings']['view'][$index]['view_id'])) {
        if (!empty($input['settings']['view'][$index]['view_id'])) {
          $view_id = strpos($input['settings']['view'][$index]['view_id'], '(') !== FALSE ? EntityAutocomplete::extractEntityIdFromAutocompleteInput($input['settings']['view'][$index]['view_id']) : $input['settings']['view'][$index]['view_id'];
        }
        $display_id = isset($input['settings']['view'][$index]['display']['display_id']) && !empty($input['settings']['view'][$index]['display']['display_id']) ? $input['settings']['view'][$index]['display']['display_id'] : NULL;
      }
      else {
        $view_data = $this->getCurrentValues($values, 'view', $config, 'id', $index);
        $view_id = $view_data['view_id'];
        $display_id = $view_data['display_id'];
      }

      // Custom composite element
      $form['view'][$index] = [ 
        '#type' => 'nk_tools_views_reference',
        '#title' => $this->t('Reference a View'),
        '#default_value' => [
          'view_id' => $view_id,
          'display' => [
            'display_id' => $display_id ? $display_id : $config['display_id'],
            'argument' => $config['argument'],
            'filter' => $config['filter'],
          ]
        ],
        '#attributes' => [
          'id' => 'nk-tools-view-reference-' . $index
        ], 
      ];
    }

    $form['view']['add_view'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add View'),
      '#limit_validation_errors' => [],
      //'#executes_submit_callback' => FALSE,
      //'#description' => $this->t('May become future feature, to load more than one view in this way, sequentially, currently disabled'),
      //'#disabled' => TRUE,
      '#submit' => [
        [get_class($this), 'addViewSubmit'],
      ],
      '#validate' => [
        [get_class($this), 'addViewValidate'],
      ],
      '#weight' => 20, 
      '#ajax' => [
        //'method' => 'append',
        'callback' => [get_class($this), 'ajaxCallback'],
        'wrapper' => 'nk-tools-ajax-wrapper-view',
      ],
    ];

    if ($num_views > 1) {
    
      $form['view']['remove_view'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove view'),
        '#limit_validation_errors' => [],
        '#submit' => [
          [get_class($this), 'removeViewSubmit'],
        ],
        '#weight' => 20, 
        '#ajax' => [
          'callback' => [get_class($this), 'ajaxCallback'],
          'wrapper' => 'nk-tools-ajax-wrapper-view',
        ],
      ];
    }

  }

  /**
   * View element submit method.
   */
  protected function viewElementSubmit(array $values) {
   
    if (isset($values['view']) && !empty($values['view'])) {
      foreach ($values['view'] as $delta => $view_value) {
        if (is_array($view_value) && !empty($view_value)) {
          foreach ($view_value as $property_id => $property) {
            if ($property_id == 'display') {
              foreach ($property as $prop_id => $prop) {
                if ($prop !== NULL || !empty($prop)) {
                  $this->configuration[$prop_id][$delta] = $prop;
                }
                else {
                  if (isset($this->configuration[$prop_id][$delta])) {
                    unset($this->configuration[$prop_id][$delta]);
                  }
                }
              }
            }
            else {
              if ($property !== NULL || !empty($property)) {
                $this->configuration[$property_id][$delta] = $property;
              }
              else {
                if (isset($this->configuration[$property_id][$delta])) {
                  unset($this->configuration[$property_id][$delta]);
                }
              }
            }
          } 
        }
        else {
          if ($view_value) {
            $this->configuration[$view_key][$delta] = $view_value;
          }
          else {
            if (isset($this->configuration[$view_key][$delta])) {
              unset($this->configuration[$view_key][$delta]);
            }
          }
        }
      }   
    }
    else {
      foreach ($values['view'] as $delta => $view_value) {
        if (is_array($view_value) && !empty($view_value)) {
          foreach ($view_value as $property_id => $property) {
            $this->configuration[$property_id][$delta] = NULL;
          } 
        }
        else {
          $this->configuration[$view_key][$delta] = NULL;
        }
      }
    }
  }
  
  /**
   * Callback for all ajax actions.
   *
   * Returns parent container element for each group
   */
  public static function ajaxCallback(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $parents = array_slice($trigger['#parents'], 0, -1);
    $element = NestedArray::getValue($form, $parents);
    return $element; 
  }

  /**
   * "Add View" Submit callback
   */
  public function addViewSubmit(array &$form, FormStateInterface $form_state) {
    $num_views = $form_state->get('num_views');
    $current = $num_views - 1;
    $delta = $num_views + 1;
    $form_state->set('num_views', $delta);
    $form_state->setRebuild(TRUE);
  }

  /**
   * "Remove View" Submit callback
   */
  public function removeViewSubmit(array &$form, FormStateInterface $form_state) {
    $num_views = $form_state->get('num_views');
    $delta = $num_views - 1;
    $form_state->set('num_views', $delta);
    $form_state->setRebuild(TRUE);
  }

}