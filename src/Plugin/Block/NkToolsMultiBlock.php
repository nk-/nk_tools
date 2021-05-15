<?php

namespace Drupal\nk_tools\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Render\Element;

use Drupal\Component\Utility\NestedArray;

use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;

use Drupal\nk_tools\Plugin\Block\NkToolsBlockBase;

/**
 * Provides an example block.
 *
 * @Block(
 *   id = "nk_tools_multi_block",
 *   admin_label = @Translation("Multi block"),
 *   category = @Translation("Nk tools")
 * )
 */
class NkToolsMultiBlock extends NkToolsBlockBase {
  
  protected $number = 1;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
     
      'node_current' => NULL,
      'node_current_bundle' => NULL,
   
      'node_reference' => NULL,
      'node_format' => NULL,
      'node_display' => NULL,
      'node_ui_label' => NULL,
      'node_single' => NULL,
      'node_delta' => NULL,
      
      'field_reference' => NULL,
      'field_format' => NULL,
      'field_display' => NULL,
      'image_style' => NULL,
      'field_ui_label' => NULL,
      'field_single' => NULL,
      'field_delta' => NULL,

      'view_id' => NULL,
      'display_id' => NULL,
      'argument' => NULL,
      'filter' => NULL,
      'view_ui_label' => NULL,
      'view_delta' => NULL,
      
      'paragraph_reference' => NULL,
      'paragraph_format' => NULL,
      'paragraph_single' => NULL,
      'paragraph_display' => NULL,
      'paragraph_ui_label' => NULL,
      'paragraph_delta' => NULL,
      
      'webform_reference' => NULL, 
      'webform_ui_label' => NULL,
      'webform_delta' => NULL,
     
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
  
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $values = $this->getCurrentFormState($form_state);

    // Disable caching on this form.
    $form_state->setCached(FALSE);

    // Nodes
    $this->nodeElement($form, $form_state, $values, $config);
   
    // Fields
    $this->fieldElement($form, $form_state, $values, $config);

    // Views
    $this->viewElement($form, $form_state, $values, $config);
    foreach (Element::children($form['view']) as $view_delta) {
      
      if (is_numeric($view_delta)) {
        $view_entity_data = [
          'key' => 'view',
          'displays' => FALSE,
          'label' =>  $this->t('View'),
        ];

        $view_children = $this->generateChildren($view_entity_data, $config, $view_delta);
        $form['view'][$view_delta] += $view_children;
      }
    }
  
    // Paragraph
    $this->paragraphElement($form, $form_state, $values, $config);

    // A Webform reference
    $this->webformElement($form, $form_state, $values, $config);

    return $form;
  }
 
  /**
   * Node element building method.
   */
  protected function nodeElement(array &$form, FormStateInterface $form_state, array $values, array $config) {
  
    $form['node'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Reference node'),
      '#attributes' => [
        'id' => 'nk-tools-ajax-wrapper-node'
      ] 
    ];

    $node_current_default_value = isset($values['node']['node_current']) && $values['node']['node_current'] !== NULL ? $values['node']['node_current'] : $config['node_current'];
    
    $form['node']['node_current'] = [
      '#type' => 'checkbox', 
      '#title' => $this->t('Render current node. From current route (page)'),
      '#description' => $this->t('May be a weird case when we want to repeat the current node on which page we are, but can be with a different view mode'),
      '#default_value' => $node_current_default_value,
      '#ajax' => [
        'event' => 'change',
        'callback' => [get_class($this), 'ajaxCallback'],
        'effect' => 'fade',
        'wrapper' => 'nk-tools-ajax-wrapper-node',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Verifying entry...'),
        ],
      ],
    ];
    
    if ($node_current_default_value) {

      $node_bundles = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
      $node_bundles_options = [];
      foreach ($node_bundles as $node_bundle_key => $node_bundle) {
        $node_bundles_options[$node_bundle_key] = $node_bundle->label();
      }

      $node_current_bundle_default_value = $this->getCurrentValues($values, 'node', $config, 'current_bundle');

      $form['node']['node_current_bundle'] = [
        '#title'  => $this->t('Current node bundle'),
        '#description' => $this->t('Define a bundle (content type) for such current node.'),
        '#type' => 'select',
        '#options' => $node_bundles_options,
        //'#weight' => 1,
        '#empty_option' => t('- Select bundle -'),
        '#default_value' => $node_current_bundle_default_value,
        '#ajax' => [
          'event' => 'change',
          'callback' => [get_class($this), 'ajaxCallback'],
          'effect' => 'fade',
          'wrapper' => 'nk-tools-ajax-wrapper-node',
          'progress' => [
            'type' => 'throbber',
            'message' => t('Verifying entry...'),
          ],
        ],
      ];

      if ($node_current_bundle_default_value) {
        $node_current_entity_data = [
          'key' => 'node',
          'bundle' => $node_current_bundle_default_value,
          'label' =>  $this->t('Node'),
        ];
        $node_current_children = $this->generateChildren($node_current_entity_data, $config);
        $form['node'] += $node_current_children;
      }
    }

    else {
      $node_default_value = $this->processDefaultValue($form, $values, 'node', $config);

      $form['node']['node_reference'] = [
        //'#disabled' => TRUE,
        '#tags' => TRUE,
        '#multiple' => TRUE,
        '#maxlength' => '2048',
        '#title'  => $this->t('Node title'),
        '#description' => $this->t('A title of a specific Node that we want to load as a content for this block. Do NOT combine with a previous checkbox.'),
        '#type' => 'entity_autocomplete',
        '#target_type' => 'node',
        '#default_value' => $node_default_value, // The #default_value can be either an entity object or an array of entity objects.
        '#attributes' => [
          'id' => 'nk-tools-node-reference' 
        ], 
        //'#selection_settings' => [
          //'target_bundles' => [],
        //],
      ];
    }
  }

  /**
   * Field element building method.
   */
  protected function fieldElement(array &$form, FormStateInterface $form_state, array $values, array $config) {

    $form['field'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Reference field(s)'),
      '#open' => TRUE,
      '#attributes' => [
        'id' => 'nk-tools-ajax-wrapper-field'
      ]    
    ];

    $field_reference = $this->getCurrentValues($values, 'field', $config);
    $fields = $this->nkToolsFactory->elementFieldReference($field_reference);
     
    $form['field']['field_reference'] = [
      '#title' => t('Field(s) to render'),
      '#description' => t('A machine name of the fields whose values show content for this block.'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'field_config',
      '#tags' => TRUE,
      '#default_value' => $fields, // The #default_value can be either an entity object or an array of entity objects.
      '#multiple' => TRUE,
      '#maxlength' => '2048',
      '#attributes' => [
        'id' => 'nk-tools-field-reference'
      ],
    ];
     
    $form['field']['field_format'] = [
      '#type' => 'radios',
      '#title' => t('Display format'),
      '#description' => t('Select output formatting for this field. Obviously "Image style" or similar option would apply only on image type of field. However, for image type of field you can also opt for View mode instead of image style and set things there on view mode config for that entity type'),
      '#default_value' => $config['field_format'],
      '#options' => [
        'display' => t('Entity View display'),
        'image' => t('Image style') 
      ], 
      '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
        'visible' => [
          ':input[id="nk-tools-field-reference"]' => ['filled' => TRUE],
        ],
      ],
      '#ajax' => [
        'event' => 'change', //'autocompleteclose',
        'callback' => [get_class($this), 'ajaxCallback'],
        'effect' => 'fade',
        'wrapper' => 'nk-tools-ajax-wrapper-field',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Verifying entry...'),
        ],
      ],
    ];

    $fields_data = $field_reference ? $this->getBundle($field_reference) : NULL;

    // Obviously we cannot support multiple (comma separated) list of fields to have unique view mode set for each, hence we consider the first field set as relevant
    $field_data = is_array($fields_data) && !empty($fields_data) ? reset($fields_data) : [];

    $field_displays = !empty($field_data) ? $this->entityDisplayRepository->getViewModeOptionsByBundle($field_data['entity_type'], $field_data['bundle']) : [];    

    if (is_array($field_data) && isset($field_data['bundle'])) {

      $field_entity_data = [
        'key' => 'field',
        'bundle' => isset($field_data['bundle']) ? $field_data['bundle'] : NULL,
        'label' =>  $this->t('Field'),
        'empty_option' => $this->t('- None -'),
        'displays' => $field_displays
      ];

      if ($field_entity_data['bundle']) {
        
        $format = isset($values['field']['field_format']) && !empty($values['field']['field_format']) ? $values['field']['field_format'] : NULL;
        $field_children = $this->generateChildren($field_entity_data, $config);

        // This is sort of special addition to fields (in case of image - stil to test)
        if ($format == 'image') {
          unset($field_children['field_display']);

          $form['field']['image_style'] = [
            '#type' => 'select',
            '#title' => $this->t('Image style'),
            '#description' => $this->t('Choose an image style preset if any of selected fields is Image. Note this applies <strong>only</strong> if Display mode is not selected above.'), 
            '#options' => image_style_options(),
            '#default_value' => $config['image_style'], 
          ];
        }

        $form['field'] += $field_children;
      } 
    }

  }

  /**
   * Paragraph element building method.
   */
  protected function paragraphElement(array &$form, FormStateInterface $form_state, array $values, array $config) {
    

    $form['paragraph'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Reference Paragraph(s)'),
      '#attributes' => [
        'id' => 'nk-tools-ajax-wrapper-paragraph'
      ]   
    ];  
    
    if ($this->moduleHandler->moduleExists('paragraphs')) {

      $paragraph_default_value = $this->processDefaultValue($form, $values, 'paragraph', $config);

      $form['paragraph']['paragraph_reference'] = [
        '#type' => 'entity_autocomplete',
        '#title' => t('Paragraph label'),  
        '#target_type' => 'paragraph',
       // '#selection_handler' => 'default:paragraph',
        '#maxlength' => '2048',
        '#tags' => TRUE,
        // The #default_value can be either an entity object or an array of entity objects.
        '#default_value' => $paragraph_default_value,
        '#multiple' => TRUE,
        '#attributes' => [
          'id' => 'nk-tools-paragraph-reference'
        ], 

        /*
        '#selection_settings' => [
          // 'view' => [
          // 'view_name' => 'users_by_name',
          // 'display_name' => 'member',
          // 'arguments' => []
          // ], 
         //'target_bundles' => [['process' => 'trends_texts']],
         'autocomplete_type' => 'tags',
         'match_operator' => 'CONTAINS',
         'size' => '60',     
        ]
        */
      ];

    }
    else {
      $form['paragraph']['#description'] = Markup::create('<div class="messages messages--warning">Paragraps module is not enabled</div>');
    }
  }
  
  /**
   * Webform element building method.
   */
  protected function webformElement(array &$form, FormStateInterface $form_state, array $values, array $config) {

    $form['webform'] = [ 
      '#type' => 'fieldset',
      '#title' => $this->t('Reference webform'),
      '#attributes' => []    
    ];
    
    if ($this->moduleHandler->moduleExists('webform')) {
      $webform_default_value = $this->entityTypeManager->getStorage('webform')->load($this->configuration['webform_reference']);
      $form['webform']['webform_reference'] = [
        '#title' => $this->t('Webform'),
        '#type' => 'entity_autocomplete',
        '#target_type' => 'webform',
        //'#required' => TRUE,
        '#default_value' => $webform_default_value, //$this->getWebform(),
      ];

      $webform_entity_data = [
        'key' => 'webform',
        'displays' => FALSE,
        'label' =>  $this->t('Webform'),
      ];

      $webform_children = $this->generateChildren($webform_entity_data, $config);
      $form['webform'] += $webform_children;
    }
    else {
      $form['webform']['#description'] = Markup::create('<div class="messages messages--warning">Webform module is not enabled</div>');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    
    parent::blockSubmit($form, $form_state);

    $values = $form_state->getValues();

    //$this->configuration['icon_back'] = $values['nk_tools_fields']['icon_back'];
    //$this->configuration['target'] = $values['target_ui']['target'];

    // Nodes
    if (isset($values['node'])) { // && isset($values['node']['node_reference']) && !empty($values['node']['node_reference'])) {
      foreach ($values['node'] as $node_key => $node_value) {
        $this->configuration[$node_key] = $node_value;
      }
    }

    // Fields
    if (isset($values['field']) && isset($values['field']['field_reference']) && !empty($values['field']['field_reference'])) {
      foreach ($values['field'] as $field_key => $field_value) {
        $this->configuration[$field_key] = $field_value;
      }   
    }

    // View
    $this->viewElementSubmit($values);
 
    // Paragraph
    if (isset($values['paragraph']) && isset($values['paragraph']['paragraph_reference']) && !empty($values['paragraph']['paragraph_reference'])) {
      foreach ($values['paragraph'] as $paragraph_key => $paragraph) {
        $this->configuration[$paragraph_key] = $paragraph;
      }   
    }
    else {
      foreach ($values['paragraph'] as $paragraph_key => $paragraph) {
        $this->configuration[$paragraph_key] = NULL;
      } 
    }
    
     // Webform
    if (isset($values['webform']) && isset($values['webform']['webform_reference']) && !empty($values['webform']['webform_reference'])) {
      foreach ($values['webform'] as $webform_key => $webform) {
        $this->configuration[$webform_key] = $webform;
      }   
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Define our array of things
    $items = [];
    $labels = [];

    $config = $this->getConfiguration();
       
    $node = $this->nkToolsFactory->getNode([]);

    // Nodes
    if ($config['node_current']) {
      if ($node instanceof NodeInterface) {
        $view_mode = isset($config['node_display']) ? $config['node_display'] : 'default';
        $items[] = $this->entityTypeManager->getViewBuilder('node')->view($node, $view_mode);
        $labels[] = isset($config['node_ui_label']) && !empty($config['node_ui_label']) ? $config['node_ui_label'] : $this->t('Current node');  
      }
      else {
        \Drupal::service('messenger')->addWarning($this->t('You have "Render current node" set in block "Multi block (Nk tools)" config. However, this is not a node page so no item can be shown.'));
      }
    }
    else {
  
      $referenced_nodes = [];
      $node_context = [];
      if (is_array($config['node_reference']) && !empty($config['node_reference'][0])) {
     
        $nodes_delta = isset($config['node_delta']) && is_numeric($config['node_delta']) ? $config['node_delta'] : 0;

        $entity_data = [
          'key' => 'node',
          'label' =>  $this->t('Node'),
        ];
        $node_children = $this->renderChildren($entity_data, $config);
        if (is_array($node_children) && !empty($node_children)) {
        
          $node_render = NULL;
          $ui_labels = isset($config['node_ui_label']) ? $this->prepareLabels($config['node_ui_label']) : ['Node tab'];
        
          foreach ($node_children as $index => $child) {
            if ($config['node_single'] && $child instanceof Markup) {
              $node_render .= $child->__toString();
            }
            else {
              $items[$nodes_delta] = $child;
              $labels[$nodes_delta] = isset($ui_labels[$index]) ? $ui_labels[$index] : $ui_labels[0];
              $nodes_delta++;
            }
          }

          if ($node_render) {
            $items[$nodes_delta] = Markup::create($node_render);
            $labels[$nodes_delta] = $ui_labels[0];
          }
        }
      }
    }

    // Fields
    $fields = $config['field_reference'] ? $this->getBundle($config['field_reference']) : [];

    if (!empty($fields)) {
      
      $fields_delta = isset($config['field_delta']) && is_numeric($config['field_delta']) ? $config['field_delta'] : count($items);
      
      if ($node instanceof NodeInterface) {
        $entity_fields = [];
        //$field_data = [];
        foreach ($fields as $index => $field) {
          switch ($field['entity_type']) {
            case 'paragraph':
              $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');
              $paragraphs = $this->paragraphField($node, $field, $config);
              $paragraph = is_array($paragraphs) ? reset($paragraphs) : $paragraphs;
              if (isset($paragraph[0]) && isset($paragraph[0]['entity']) && $paragraph[0]['entity'] instanceof ParagraphInterface) {
                $parent_node = $paragraph[0]['entity']->getParentEntity();
                $filter = $parent_node instanceof NodeInterface && $parent_node->hasField($paragraph[0]['node_field']) && $parent_node->getType() == $paragraph[0]['node_bundle'];
              }
            break;

            case 'node':
              $filter = $field['bundle'] == $node->getType();
            break;
          }

          if ($filter) {
            $entity_fields[] = $field;
          }
        }
     
        $field_data = $this->fields($node, $entity_fields, $config);
        $field_render = NULL;
        foreach($field_data['items'] as $field_index => $field_item) {
          
          $ui_labels = isset($config['field_ui_label']) ? $this->prepareLabels($config['field_ui_label']) : ['Field tab'];
          

          if ($field_item instanceof Markup) {
            $field_render .= $field_item->__toString();
          }
          else {
            $items[$fields_delta] = $field_item;
            $labels[$fields_delta] = isset($ui_labels[$field_index]) ? $ui_labels[$field_index] : $ui_labels[0];
            $fields_delta++;
          } 
         
        }
        
        if ($field_render) {
          $items[$fields_delta] = Markup::create($field_render);
          $labels[$fields_delta] = $ui_labels[0]; 
        }
      }
    } 
   
    // View
    if (!empty($config['view_id']) && !empty($config['display_id'])) {
      $view_items = [];
      $view_labels = [];
      foreach ($config['view_id'] as $delta => $view_id) {
        $views_delta = isset($config['view_delta']) && isset($config['view_delta'][$delta]) && is_numeric($config['view_delta'][$delta]) ? $config['view_delta'][$delta] : count($items);
        $arguments = isset($config['argument']) && isset($config['argument'][$delta]) && !empty($config['argument'][$delta]) ? [$config['argument'][$delta]] : [];
        $items[$views_delta] = $this->nkToolsFactory->getView($view_id, $config['display_id'][$delta], $arguments, TRUE);
        $labels[$views_delta] = isset($config['view_ui_label'][$delta]) && !empty($config['view_ui_label'][$delta]) ? $config['view_ui_label'][$delta] : 'View';
      }
    }

    // Paragraph 
    if (!empty($config['paragraph_reference'])) {
      
      $paragraphs_delta = isset($config['paragraph_delta']) && is_numeric($config['paragraph_delta']) ? $config['paragraph_delta'] : count($items);

      $entity_data = [
        'key' => 'paragraph',
        'label' =>  $this->t('Paragraph'),
      ];
      $children = $this->renderChildren($entity_data, $config, $node);
      if (is_array($children) && !empty($children)) {
        
        $paragraph_render = NULL;
        $ui_labels = isset($config['paragraph_ui_label']) ? $this->prepareLabels($config['paragraph_ui_label']) : ['Paragraph tab'];

        $view_mode = isset($children[0]['#view_mode']) ? $children[0]['#view_mode'] : 'default';
        $field_data['context'][] = ['list_type' => 'paragraph', 'view_mode' => $view_mode];
         
        foreach ($children as $index => $child) {
        
          if ($config['paragraph_single']) {
            $paragraph_render .= $this->renderer->render($child)->__toString();
          }
          else {
            $items[$paragraphs_delta] = $child; //$this->renderer->render($child);
            $labels[$paragraphs_delta] = isset($ui_labels[$index]) ? $ui_labels[$index] : $ui_labels[0]; 
            $paragraphs_delta++;
          }
        }

        if ($paragraph_render) {
          $items[$paragraphs_delta] = Markup::create($paragraph_render);
          $labels[$paragraphs_delta] = $ui_labels[0];
        }
      }
    }
    
    // Webform    
    if (isset($config['webform_reference']) && !empty($config['webform_reference'])) {
      $webform_delta = isset($config['webform_delta']) && is_numeric($config['webform_delta']) ? $config['webform_delta'] : count($items);
      $items[$webform_delta] = [
        '#type' => 'webform',
        '#webform' => $this->getWebform(),
        '#default_data' => [],
      ];

      $labels[$webform_delta] = 'Webform';

    }

    // Essential, usage of "Weight" field
    ksort($items);
    ksort($labels);

     // Finally, now that we have our items fetched, make a build array
    $build = [
      '#list_type' => 'ul',
      '#list_title' => isset($config['block_label']['value']) && !empty($config['block_label']['value']) ? Markup::create($config['block_label']['value']) : $config['label'],
      '#node' => $node,
      '#config' => $config,
      '#items' => array_values($items),
      '#labels' => array_values($labels),
      '#attributes' => [
        'class' => [
          'nk-tools-multi-block-item'
        ],
      ],
      //'#cache' => ['max-age' => 0],
      '#cache' => [
        'contexts' => ['url.path', 'url.query_args'], //['route.entity.node.canonical'],
        //'tags' => ['node:' . $node->id()],
      ],
      '#wrapper_attributes' => [
        'class' => [
          $config['label_display'] == 'visible' ? 'with-title' : 'no-title', 
          'nk-tools-multi-block-items-wrapper',
        ]
      ],
    ];

    if ($node instanceof NodeInterface) {
      $build['#cache' ]['tags'] = ['node:' . $node->id()];
    }

    if (!empty($config['icon'])) {
      $build['#attributes']['data-icon'] = $config['icon'];
    }

    $toggle_attributes = [
      /*
      'class' => [
        'pl-0',
        'mb-16', 
      ]
      */
    ];
    
    $pane_wrapper_attributes = [
      'class'=> [
        'nk-tools-multi-block',
        // 'mt-16',
      ]
    ];

    $this->nkToolsFactory->renderTargetUi($build, $config, [], $pane_wrapper_attributes, 'nk_tools_multi_block');

    if (isset($field_data['context']) && !empty($field_data['context'])) {
      foreach ($field_data['context'] as $type => $c) {
        $build['#context'][$type] = $c; 
      } 
    }
    
    if (!empty($node_context)) {
      $build['#context'][] = $node_context;
    }

    $build['#context'][] = ['list_style' => 'multi-block'];
   
    return parent::build() + $build;
  }

  
  /**
   * Custom method, generates View modes form element (select) for various entities that implement
   */
  public function formatTrigger(array &$form, string $key, array $config, array $options = [], $description = NULL) {
    
    $radio_options = !empty($options) ? $options : ['none' => t('Unformatted'), 'display' => t('Entity View display')];
    
    $form[$key][$key .'_format'] = [
      '#type' => 'radios',
      '#title' => t('Display format'),
      '#description' => $description ? $description : t('Select output formatting for this @key.', ['@key' => $key]),
      '#default_value' => $config[$key .'_format'],
      '#options' => $radio_options, 
      '#weight' => 1,
      '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
        'visible' => [
          ':input[id="nk-tools-' . $key . '-reference"]' => ['filled' => TRUE],
        ],
      ],
      '#ajax' => [
        'event' => 'change', //'autocompleteclose',
        'callback' => [get_class($this), 'ajaxCallback'],
        'effect' => 'fade',
        'wrapper' => 'nk-tools-ajax-wrapper-' . $key,
        'progress' => [
          'type' => 'throbber',
          'message' => t('Verifying entry...'),
        ],
      ],
    ];
  }

  protected function processDefaultValue(array &$form, array $values, string $key, array $config) {

    $reference = $this->getCurrentValues($values, $key, $config);
    $references_data = $reference ? $this->getBundle($reference, $key) : NULL;

    // Obviously we cannot support multiple (comma separated) list of fields to have unique view mode set for each, hence we consider the first field set as relevant
    $reference_data = is_array($references_data) && !empty($references_data) ? reset($references_data) : [];
    $default_value = isset($reference_data['entity_object']) && is_object($reference_data['entity_object']) ? $reference_data['entity_object'] : NULL;
 
    if ($default_value) {

      $label = ucfirst($key);

      $entity_data = [
        'key' => $key,
        'bundle' => $default_value->getType(),
        'label' =>  $this->t('@label', ['@label' => $label]),
      ];

      $children = $this->generateChildren($entity_data, $config);
      
      if (!empty($children)) {

        $format = isset($values[$key][$key . '_format']) && !empty($values[$key][$key . '_format']) ? $values[$key][$key . '_format'] : NULL;
        
        if ($format == 'none' && isset($children[$key . '_display'])) {
          unset($children[$key . '_display']);
        }
        
        $form[$key] += $children;
      } 
    }

    // Set the actual ajax trigger here (radios) as a form element
    $this->formatTrigger($form, $key, $config);

    return $default_value;
  }

  /**
   * Render (append) set of child elements
   */
  protected function renderChildren(array $entity_data, array $config, $node = NULL) {
    $entities = [];
    $context = [];
    $key = $entity_data['key'];

    if (is_array($config[$key . '_reference']) && !empty($config[$key . '_reference'][0])) {
      
      if (isset($entity_data['delta'])) {
        $delta = $entity_data['delta'];
      }
      else {
        $delta = isset($config[$key . '_delta']) && is_numeric($config[$key. '_delta']) ? $config[$key. '_delta'] : 0;
      }
 
      $context = [
        'entity' => $key,
        'list_type' => 'link',
        'field_type' =>'references',
      ];

      foreach ($config[$key . '_reference'] as $delta => $reference) {
        if (isset($reference['target_id']) && !empty($reference['target_id'])) {
          
          $referenced_entity = $this->entityTypeManager->getStorage($key)->load($reference['target_id']);

          if ($key == 'paragraph' && $node instanceof NodeInterface) {
    
            $field_data = [
              'bundle' => $referenced_entity->getType(),
              'rendered_entity' => TRUE,
            ];
    
            $paragraphs = $this->paragraphField($node, $field_data, $config);

            if (!empty($paragraphs)) {
              foreach ($paragraphs as $index => $paragraph) {
                $entities[$index] = $this->entityTypeManager->getViewBuilder($key)->view($paragraph, $config['paragraph_display']);
              }
            }
          }
          else if ($key == 'node' && $referenced_entity instanceof NodeInterface) {
            $context['bundle'] = $referenced_entity->getType();
            $entity_array = $this->nkToolsFactory->getReferencedNodes($referenced_entity, ['entity_reference' => $key], $config[$key . '_display'], [$reference['target_id']]);
            $render = reset($entity_array);
            $string = NULL;
            if ($render  && !empty($config[$key . '_display'])) {
              if ($render instanceof Markup) {
                $string .= $render ->__toString();
              }
              else {
                $entities[$delta] = $this->renderer->render($render);
                $delta++;
              }
            }
            if ($string) {
              $entities[$delta] = Markup::create($string);
            }
          }
        }
      }
    }
    return $entities;
  }

  protected function fields(NodeInterface $node, $fields, $config) {
 
    $context = NULL; 
    $items = [];
    $values = [];

    foreach ($fields as $index => $field) {
      
      switch ($field['entity_type']) {

        case 'paragraph':
        
          $values[$field['field_name']] = $this->paragraphField($node, $field, $config);
      
        break;

        case 'node':
         

          if ($node->getType() == $field['bundle'] && $node->hasField($field['field_name']) && !empty($node->get($field['field_name'])->getValue())) {
            
            $item_values = $node->get($field['field_name'])->getValue();

            // Check if this is any kind of reference field (i.e. entityreference or file/image reference)
            $value_key = isset($item_values[0]['target_id']) && !empty($item_values[0]['target_id']) ? 'target_id' : 'value';
            $is_image = $value_key == 'target_id' && isset($item_values[0]['width']) && !empty($item_values[0]['width']);
            
            if ($is_image) { // TODO: Make a support for file (to render link for download)
              $type = 'image';
            } 
            else {
              $type = $value_key == 'target_id' ? 'entityreference' : 'default'; 
            }
           
            
            $values[$field['field_name']][] = [
              'type' => $type,
              'field' => $field,   
              'value_key' => $value_key,
              'values' => $node->get($field['field_name'])->getValue(),
              'entity' => $node,
            ];
             
          }

        break; 

      }

      if (!empty($values)) {

        foreach ($values as $field_name => $value) {

          foreach ($value as $delta => $item) {
          
            if (isset($item['type'])) {

              
            switch ($item['type']) {

              // Check if it is an image
              // TODO: Make a support for file (to render link for download)
              case 'image':
               
                $image = $this->image($item['entity'], $item['field'], $config);
                
                if (is_array($image['items'])) {
                  foreach ($image['items'] as $item) {
                   $items[] = $item;
                  } 
                }
                else {
                  if (!in_array($image['items'], $items)) {
                    $items[] = $image['items'];
                  }
                }
              
                $context = $image['context']; 
                
              break;

              default:
                 
                if (isset($item[0]) && !empty($item[0])) { // An array of returned items

                  $context = [
                    'entity' => $field['entity_type'],
                    'list_type' => 'fields',
                  ];

                  foreach ($item as $i => $f) {
                    $items[$delta] = $this->nkToolsFactory->fieldRender($f['entity'], $f['field'], $config['field_display']);
                    $context['field_name'] = $f['field']['field_name'];
                  }
                }
                else { 

                  $render_fields = $this->nkToolsFactory->fieldRender($item['entity'], $item['field'], $config['field_display']); //'node.issue.teaser', 
                  if (is_array($render_fields)) {
                    foreach ($render_fields as $render_field) {
                      $items[] = $render_field;
                    }
                  }
                  else {
                    $items[] = $render_fields;
                  }

                  $context = [
                    'entity' => $field['entity_type'],
                    'list_type' => 'fields',
                    'field_name' => $item['field']['field_name'],
                  ];

                } 

              break;
           
            }

            }
         
          }
        }
      }
    }
 
    return ['context' => $context, 'items' => $items];
  }

  
  protected function paragraphField(NodeInterface $node, array $entity_info, array $config) {
   
    $values = [];


    foreach ($node->getFields() as $key => $field) {
      $field_type = $field->getFieldDefinition()->getType();
      $field_settings = $field->getSettings();
      if ($field_type == 'entity_reference_revisions' && $field_settings['target_type'] == 'paragraph') {

        foreach ($field as $delta => $item) {
  
          if ($item->entity instanceof ParagraphInterface && $item->entity->getType() == $entity_info['bundle']) {

            if (isset($entity_info['rendered_entity'])) { // We need only paragraph entities so return that
               $values[$delta] = $item->entity;
            }

            else { // We want particular field from the paragraph

              if ($item->entity->hasField($entity_info['field_name']) && !empty($item->entity->get($entity_info['field_name'])->getValue())) {

                $values[$delta] = [];
                $item_values = $item->entity->get($entity_info['field_name'])->getValue();
                
                // Check if this is any kind of reference field (i.e. entityreference or file/image reference)
                $value_key = isset($item_values[0]['target_id']) && !empty($item_values[0]['target_id']) ? 'target_id' : 'value';
           
                $is_image = $value_key == 'target_id' && isset($item_values[0]['width']) && !empty($item_values[0]['width']);
          
                if ($is_image) { // TODO: Make a support for file (to render link for download)
                  $type = 'image';
                } 
                else {
                  $type = $value_key == 'target_id' ? 'entityreference' : 'default'; 
                }
           
                foreach ($item_values as $index => $item_value) {
                  $values[$delta][$index] = [
                    'node_bundle' => $node->getType(),
                    'node_field' => $key,
                    'type' => $type,
                    'field' => $entity_info,
                    'value_key' => $value_key,
                    'value' => $item_value[$value_key],
                    'entity' => $item->entity,
                  ]; 
                }
              }
            }
          }
        }
      }
    }
    
    return $values; 
  }

  protected function prepareLabels($ui_label) {
    $label_string = str_replace(', ', ',', $ui_label);
    $labels = explode(',', $label_string);
    return $labels;
  }

  /**
   * Image handling
   *
   * @return array with context data and rendered image itself
   */
  protected function image(EntityInterface $entity, array $field, array $config) {

    $params = [
      'alt' => $entity->getTitle(),
      'title' => $entity->getTitle(),
      'attributes' => [
        'class' => [
          'img-responsive',
        ],
      ] 
    ];
    
    // Image style field has the advantage, if that one is set image will be rendered so let's render it our custom programmatic way
    if (isset($config['image_style']) && !empty($config['image_style'])) {
      $params['image_preset'] = $config['image_style'];
      $image_field = $this->nkToolsFactory->renderFileField($field['entity_type'], $entity, $field['field_name'], $params, [], TRUE); 
      $image = $this->renderer->render($image_field);
    }
    
    else {
      if (!empty($config['field_display'])) { // Else, if we have Display mode set render image according to it
        $image = $this->nkToolsFactory->fieldRender($entity, $field, $config['field_display'], 'image');
      }
      else { // Or finally fallback to programmatic render in original size (nom preset and no display mode available case)
        $image_field = $this->nkToolsFactory->renderFileField($field['entity_type'], $entity, $field['field_name'], $params, [], TRUE); 
        $image = $this->renderer->render($image_field);
      }
    }
    
    $context = [
      'entity' => $field['entity_type'],
      'list_type' => 'image',
      'field_name' => $field['field_name']
    ];
            
    return ['context' => $context, 'items' => $image];

  }

}