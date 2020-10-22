<?php

namespace Drupal\nk_tools\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Cache\Cache;

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


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      
      'icon_back' => NULL,
      'target' => NULL,
     
      'node_reference' => NULL,
      'node_display' => NULL,
      'node_ui_label' => NULL,
      'node_single' => NULL,
      'node_delta' => NULL,
      
      'field_reference' => NULL,
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

    $form['nk_tools_fields']['icon_back'] = [
      '#type' => 'textfield',
      '#title' => t('Icon close'),
      '#description' => t('Any icon you could use in twig template for toggle-off like state, to hide-again when toggle content is visible. Provided the same same as for the above, main icon.'),
      '#default_value' => $config['icon_back'],
      '#weight' => 10
    ];

    $form['target_ui'] = [
      '#type' => 'details',
      '#title' => $this->t('Entity or entities to render'),
      '#description' => $this->t('Choose a target entity, or fields, to load as a primary content of this block.'),
      '#open' => TRUE, 
    ];

    $form['target_ui']['target'] = [
      '#type' => 'radios',
      '#title' => $this->t('Target UI'),
      '#description' => $this->t('<em>Unformatted</em> is just linear, <em>Tabs</em> are minimal lines in CSS, <em>Collapsible Panel</em> is based on main theme\'s implementation'),
      '#default_value' => $config['target'], // $config->get('entity_load')['entities'],
      '#required' => TRUE,
      '#attributes' => [
        'id' => 'target-ui-target'
      ], 
      '#options' => [
        'none' => $this->t('Unformatted'),
        'tabs' => $this->t('Tabs'),
        'panel' => $this->t('Collapsible Panel'),
      ],
    ];

    $referenced_nodes = [];
   
    if (is_array($config['node_reference']) && !empty($config['node_reference'][0])) {
      foreach ($config['node_reference'] as $delta => $reference) {
        if (isset($reference['target_id']) && !empty($reference['target_id'])) {
           $referenced_nodes[$delta] = $this->nkToolsFactory->getNode(['id' => $reference['target_id']]);
        }
      }
    }

    // Nodes
    $form['node'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Reference node'),
      '#open' => TRUE,
     ];

    $form['node']['node_reference'] = [
      //'#disabled' => TRUE,
      '#tags' => TRUE,
      '#multiple' => TRUE,
      '#maxlength' => '2048',
      '#title'  => $this->t('Node title'),
      '#description' => $this->t('A title of a Node that we want to load as a content for this block.'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#default_value' => $referenced_nodes, // The #default_value can be either an entity object or an array of entity objects.
      '#attributes' => [
        'id' => 'nk-tools-node-reference'
      ], 
      //'#selection_settings' => [
        //'target_bundles' => [],
      //],
    ];

    if (isset($referenced_nodes[0]) && $referenced_nodes[0] instanceof NodeInterface) {

      $node_entity_data = [
        'key' => 'node',
        'bundle' => $referenced_nodes[0]->getType(),
        'label' =>  $this->t('Node'),
      ];

      $node_children = $this->generateChildren($node_entity_data, $config);
      $form['node'] += $node_children;
    }

    // Fields
    $fields_data = $this->getBundle($config['field_reference']);
    // Obviously we cannot support multiple (comma separated) list of fields to have unique view mode set for each, hence we consider the first field set as relevant
    $field_data = reset($fields_data);

    $form['field'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Reference field(s)'),
      '#open' => TRUE,
      '#attributes' => []    
    ];

    $fields = $this->nkToolsFactory->elementFieldReference($config['field_reference']);
     
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
      ] 
    ];
     
    //$field_renders  = $this->entityDisplayRepository->collectRenderDisplay($node, 'default')->getComponent('field_images');
    $field_displays = $this->entityDisplayRepository->getViewModeOptionsByBundle($field_data['entity_type'], $field_data['bundle']);    

    $field_entity_data = [
      'key' => 'field',
      'bundle' => $field_data['bundle'],
      'label' =>  $this->t('Field'),
      'empty_option' => $this->t('- None -'),
      'displays' => $field_displays
    ];

    $field_children = $this->generateChildren($field_entity_data, $config);

    $form['field'] += $field_children;

    // This is sort of special addition to fields (in case of image - stil to test)
    $form['field']['image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Image style'),
      '#description' => $this->t('Choose an image style preset if any of selected fields is Image. Note this applies <strong>only</strong> if Display mode is not selected above.'), 
      '#options' => image_style_options(),
      '#default_value' => $config['image_style'], 
      '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
        'visible' => [
          ':input[id="nk-tools-field-reference"]' => ['filled' => TRUE],
        ],
      ],
    ];

    // View 
    $view_id = NULL;
    $view = NULL;
    $view_storage = $this->entityTypeManager->getStorage('view');
 
    // Check current form state values (if coming from ajax or not) 
    if ($form_state instanceof SubformStateInterface) {
      $values = $form_state->getCompleteFormState()->getValues();
    }
    else {
      $values = $form_state->getValues();
    }

    if (isset($values['settings']) && isset($values['settings']['view']) && !empty($values['settings']['view'])) {
      $view_id =  isset($values['settings']['view']['view_id']) ? $values['settings']['view']['view_id'] : NULL;  
    }
    else {
      if (isset($values['view']) && !empty($values['view'])) {
        $view_id = isset($values['view']['view_id']) ? $values['view']['view_id'] : NULL;
      } 
    }

    if ($view_id) {
      $view = is_object($view_id) ? $view_id : $view_storage->load($view_id);
    }
    else {
      $view_id = isset($config['view_id']) && !empty($config['view_id']) ? $config['view_id'] : NULL;
      $view = $view_id ? $view_storage->load($view_id) : NULL;
    }
  
    // Custom composite element
    $form['view'] = [ 
      '#type' => 'nk_tools_views_reference',
      '#title' => $this->t('Reference a View'),
      //'#description' =>  $this->t('Here we choose a View that will serve a route with search result'),
      //'#disabled' => TRUE,
      '#default_value' => [
        'view_id' => $view_id,
        'display' => [
          'display_id' => $config['display_id'],
          'argument' => $config['argument'],
          'filter' => $config['filter'],
        ]
      ],
    ];
    
    $view_entity_data = [
      'key' => 'view',
      'displays' => FALSE,
      'label' =>  $this->t('View'),
    ];

    $view_children = $this->generateChildren($view_entity_data, $config);
    $form['view'] += $view_children;

    // Paragraph
    $form['paragraph'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Reference Paragraph(s)'),
    ];  
    
    if ($this->moduleHandler->moduleExists('paragraphs')) {

      $referenced_paragraphs = [];
      if (is_array($config['paragraph_reference']) && !empty($config['paragraph_reference'][0])) {
        foreach ($config['paragraph_reference'] as $delta => $reference) {
          if (isset($reference['target_id']) && !empty($reference['target_id'])) {
           $referenced_paragraphs[$delta] = $this->entityTypeManager->getStorage('paragraph')->load($reference['target_id']);
          }
        }
      }

      $form['paragraph']['paragraph_reference'] = [
        '#type' => 'entity_autocomplete',
        '#title' => t('Paragraph label'),  
        '#target_type' => 'paragraph',
       // '#selection_handler' => 'default:paragraph',
        '#maxlength' => '2048',
        '#default_value' => NULL,
        '#tags' => TRUE,
        '#default_value' => $referenced_paragraphs, // The #default_value can be either an entity object or an array of entity objects.
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
      
      if (isset($referenced_paragraphs[0]) && $referenced_paragraphs[0] instanceof ParagraphInterface) {
        $paragraph_entity_data = [
          'key' => 'paragraph',
          'bundle' => $referenced_paragraphs[0]->getType(),
          'label' =>  $this->t('Paragraph'),
        ];

        $paragraph_children = $this->generateChildren($paragraph_entity_data, $config);
        $form['paragraph'] += $paragraph_children;
      }

    }
    else {
      $form['paragraph']['#description'] = Markup::create('<div class="messages messages--warning">Paragraps module is not enabled</div>');
    }

    // A Webform reference
    $form['webform'] = [ 
      '#type' => 'fieldset',
      '#title' => $this->t('Reference webform'),
      '#attributes' => []    
    ];
    
    if ($this->moduleHandler->moduleExists('webform')) {
      $form['webform']['webform_reference'] = [
        '#title' => $this->t('Webform'),
        '#type' => 'entity_autocomplete',
        '#target_type' => 'webform',
        //'#required' => TRUE,
        '#default_value' => $this->getWebform(),
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
 
    // $form_state->setCached(FALSE);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    
    parent::blockSubmit($form, $form_state);

    $values = $form_state->getValues();
   
    $this->configuration['icon_back'] = $values['nk_tools_fields']['icon_back'];
    $this->configuration['target'] = $values['target_ui']['target'];

    // Nodes
    if (isset($values['node']) && isset($values['node']['node_reference']) && !empty($values['node']['node_reference'])) {
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
    if (isset($values['view']) && isset($values['view']['view_id']) && !empty($values['view']['view_id'])) {
      foreach ($values['view'] as $view_key => $view_value) {
        if (is_array($view_value) && !empty($view_value)) {
          foreach ($view_value as $property_id => $property) {
            $this->configuration[$property_id] = $property;
          } 
        }
        else {
          $this->configuration[$view_key] = $view_value;
        }
      }   
    }
    else {
      foreach ($values['view'] as $view_key => $view_value) {
        if (is_array($view_value) && !empty($view_value)) {
          foreach ($view_value as $property_id => $property) {
            $this->configuration[$property_id] = NULL;
          } 
        }
        else {
          $this->configuration[$view_key] = NULL;
        }
      }
    }
 
    // Paragraph
    if (isset($values['paragraph']) && isset($values['paragraph']['paragraph_reference']) && !empty($values['paragraph']['paragraph_reference'])) {
      foreach ($values['paragraph'] as $paragraph_key => $paragraph) {
        $this->configuration[$paragraph_key] = $paragraph;
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
     
    // Fields
    $fields = $this->getBundle($config['field_reference']);
    
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
      $views_delta = isset($config['view_delta']) && is_numeric($config['view_delta']) ? $config['view_delta'] : count($items);
      $arguments = !empty($config['argument']) ? [$config['argument']] : [];
      $items[$views_delta] = $this->nkToolsFactory->getView($config['view_id'], $config['display_id'], $arguments, TRUE);
      $labels[$views_delta] = $config['view_ui_label'];
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
        
        foreach ($children as $index => $child) {
        
          if ($config['paragraph_single']) {
            $paragraph_render .= $this->renderer->render($child)->__toString();
          }
          else {
            $items[$paragraphs_delta] = $this->renderer->render($child);
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
        'tags' => ['node:' . $node->id()],
      ],
      '#wrapper_attributes' => [
        'class' => [
          $config['label_display'] == 'visible' ? 'with-title' : 'no-title', 
          'nk-tools-multi-block-items-wrapper',
        ]
      ],
    ];

    if (!empty($config['icon'])) {
      $build['#attributes']['data-icon'] = $config['icon'];
    }

    switch ($config['target']) {
       
       // Tabs front end UI
       case 'tabs':
         
         $build['#theme'] = 'nk_tools_tabs';
         $build['#attached']['library'][] = 'nk_tools/tabs';

       break;
       
       // Collapsible toggle front end UI
       case 'panel':
         
         $build['#theme'] = 'nk_tools_collapsible_pane';
         
         foreach ($build['#items'] as $delta => &$item) {
           $item = [
             'label' => isset($build['#labels'][$delta]) && !empty($build['#labels'][$delta]) ? $build['#labels'][$delta] : 'Toggle',
             'content' => $item,  
             'target' => 'panel-' . $delta
           ];
         }
         
         $toggle_attributes = [ 
          'data-icon' =>  !empty($config['icon']) ? $config['icon'] : NULL,
          'data-icon-back' => !empty($config['icon_back']) ? $config['icon_back'] : NULL,
          'data-target-in' => 'fadeIn',
          'data-target-out' => 'fadeOut', 
          'class' => [
            'text-default-color',
            'pl-0',
            'mb-16', 
          ]
        ];
        $build['#toggle_attributes'] = new Attribute($toggle_attributes);  

        $pane_wrapper = [
          'class'=> [
            'nk-tools-multi-block',
            'mt-16',
          ]
        ];
        $build['#pane_wrapper_attributes'] = new Attribute($pane_wrapper);

       break;

       // "Unformatted", default list of items
       default:
         $build['#theme'] = 'nk_tools_multi_block';
       break;

    }

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
   * {@inheritdoc}
   */
/*
  public function getCacheTags() {
    $node = $this->currentRoute->getParameter('node');
    if ($node) {
      return Cache::mergeTags(parent::getCacheTags(), ["node:{$node->id()}"]);
    }
  }
*/


  protected function generateChildren(array $entity_data, array $config) {
    
    $children = [];
   
    $displays = isset($entity_data['displays']) ? $entity_data['displays'] : $this->entityDisplayRepository->getViewModeOptionsByBundle($entity_data['key'], $entity_data['bundle']); 
    
    if ($displays !== FALSE) {
      $children[$entity_data['key'] . '_display'] = [
        '#title'  => $this->t('Display mode'),
        '#description' => $this->t('Choose a display mode for the @entity. Means what is set as formatting there for these fields will render.', ['@entity' => $entity_data['label']]),
        '#type' => 'select',
        '#options' => is_array($displays) && !empty($displays) ? $displays : [],
        '#default_value' => $config[$entity_data['key'] . '_display'],
        '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
          'visible' => [
             ':input[id="nk-tools-' . $entity_data['key'] . '-reference"]' => ['filled' => TRUE],
          ],
        ],
      ];
    
      if ($entity_data['empty_option']) {
        $children[$entity_data['key'] . '_display']['#empty_option'] =  $entity_data['empty_option'];// $this->t('- None -'),
      }
    }

    $children[$entity_data['key'] . '_ui_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label(s) for tabs or collapsible panes'),
      '#description' => $this->t('A comma separated label(s), one for each tab or collapsible toggle that entity selected here provides in render. Leave blank if "Unformatted" is selected as target UI.'),
      '#default_value' => $config[$entity_data['key'] . '_ui_label'],
      '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
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
      '#default_value' => $config[$entity_data['key'] . '_delta'],  
      '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
        'visible' => [
           ':input[id="nk-tools-' . $entity_data['key'] . '-reference"]' => ['filled' => TRUE],
        ],
      ],
       '#weight' => 12,
    ];

    return $children;

  }

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
            //$paragraph = is_array($paragraphs) ? reset($paragraphs) : $paragraphs;
    
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
          if ($field['field_name'] == 'field_faculty') {
             //ksm($values);
          } 
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
          //ksm($field_name);
          foreach ($value as $delta => $item) {
          
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
    
    //ksm($items);
   // ksm($node->getTitle());

    return ['context' => $context, 'items' => $items];
  }

  
  protected function paragraphField(NodeInterface $node, array $entity_info, array $config) {
   
    $values = [];
  
    // $ff = $this->nkToolsFactory->getEntityFields('node', $node->getType());

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


/*
  protected function getLabels($config) {
    $label_string = str_replace(', ', ',', $config['tabs']);
    $labels = explode(',', $label_string);
    return $labels;
  }
*/

  /**
   * Get this block instance webform.
   *
   * @return \Drupal\webform\WebformInterface
   *   A webform or NULL.
   */
  protected function getWebform() {
    return $this->entityTypeManager->getStorage('webform')->load($this->configuration['webform_reference']);
  }

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