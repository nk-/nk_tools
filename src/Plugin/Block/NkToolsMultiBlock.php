<?php

namespace Drupal\nk_tools\Plugin\Block;

//use Drupal\Core\Block\BlockBase;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
//use Drupal\Core\Entity\Entity\EntityViewDisplay;

use Drupal\Core\Entity\EntityInterface;

use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Render\Element;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\NestedArray;

use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\block_content\Entity\BlockContent;
use Drupal\block\Entity\Block;



use Drupal\views\Views;
use Drupal\views\Entity\View;
use Drupal\views\ViewExecutable;

use Drupal\paragraphs\ParagraphInterface;

use Drupal\nk_tools\Form\DiploViewsReferenceForm;

use Symfony\Component\DependencyInjection\ContainerInterface;

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
      'target' => NULL,
      'entities' => NULL,
      'node_reference' => NULL,
      'node_display' => NULL,
      'field_reference' => NULL,
      'field_display' => NULL,
      'image_style' => NULL,
      'field_labels' => NULL,
      'paragraph_reference' => NULL,
      'block_reference' => NULL,
      'view_id' => NULL,
      'display_id' => NULL,
      'argument' => NULL,
      'filter' => NULL,
      'webform_id' => NULL, 

    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
  
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
  
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
      '#options' => [
        'none' => $this->t('Unformatted'),
        'tabs' => $this->t('Tabs'),
        'panel' => $this->t('Collapsible Panel'),
      ],
    ];

    // Choose and entity or fields to load as a primary content of this block
    /*
    $form['entity_load'] = [
      '#type' => 'details',
      '#title' => $this->t('Entity or entities to render'),
      '#description' => $this->t('Choose a target entity, or fields, to load as a primary content of this block.'),
      '#open' => TRUE, 
    ];

    $form['entity_load']['entities'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select entity'),
      '#description' => $this->t('Choose a target entity'),
      '#default_value' => '', // $config->get('entity_load')['entities'],
      '#options' => [
        'node' => $this->t('Node'),
        'fields' => $this->t('Fields'),
        'block' => $this->t('Block'),
        'view' => $this->t('View'), 
      ],
      '#attributes' => [
        'data-target' => 'widget-next',
        'data-class' => 'visually-hidden',
        'data-value' => 'fields',
        'class' => [
          'trigger',
          'widget-trigger'
        ],
      ], 

    ];
     */

    
    $node = NULL;
    $node_params = [
     'id' => $config['node_reference'],
     'validate' => TRUE,
     'caller' => '<em>Multi block node</em>',
    ];

    if ($node_params['id']) {
      $node = $this->nkToolsFactory->getNode($node_params);
    }
 
    $fields_data = $this->getBundle($config['field_reference']);
    // Obviously we cannot support multiple (comma separated) list of fields to have unique view mode set for each, hence we consider the first field set as relevant
    $field_data = reset($fields_data);

    // Nodes
    $form['node'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Reference node'),
      '#open' => TRUE,
     ];

    $form['node']['node_reference'] = [
      '#disabled' => TRUE,
      '#title'  => $this->t('Node title'),
      '#description' => $this->t('A title of a Node that we want to load as a content for this block.'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#default_value' => $node, // The #default_value can be either an entity object or an array of entity objects.
      '#attributes' => [
        'id' => 'nk-tools-node-reference'
      ], 
      //'#selection_settings' => [
        //'target_bundles' => [],
      //],
    ];

    $displays = $this->entityDisplayRepository->getViewModeOptionsByBundle($field_data['entity_type'], $field_data['bundle']);   

    $form['node']['node_display'] = [
      '#disabled' => TRUE,
      '#title'  => $this->t('Display mode'),
      '#description' => $this->t('Choose a display mode for this node.'),
      '#type' => 'select',
      '#options' => $displays,
      '#default_value' => $config['node_display'],
      '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
        'visible' => [
          ':input[id="nk-tools-node-reference"]' => ['filled' => TRUE],
        ],
      ],
    ];

    /*
    $fields = [];
    $field_names = $this->getBundle($config['field_reference']);
    if (!empty($field_names)) {
      $field_storage = $this->entityTypeManager->getStorage('field_config');
      foreach ($field_names as $field) {
        $fields[] = $field_storage->loadByName($field['entity_type'], $field['bundle'], $field['field_name']);
      }
    }
    */
    
    /*
    $displays = $this->getDisplays($config['field_reference'], 'issue');
    $view_modes = [];
    if (!empty($displays)) {
      foreach ($displays as $key => $display) {
        if ($key == 'node.issue.teaser') {
          $view_modes[$key] = $display->status();
        }
      }
     }
     */

    // Fields
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
      //'#selection_settings' => [
      //  'target_bundles' => [['node' => 'course']],
     // ],
      '#tags' => TRUE,
      '#default_value' => $fields, // The #default_value can be either an entity object or an array of entity objects.
      '#multiple' => TRUE,
      '#maxlength' => '256',
      '#attributes' => [
        'id' => 'nk-tools-field-reference'
      ] 
    ];
     
    //$field_renders  = $this->entityDisplayRepository->collectRenderDisplay($node, 'default')->getComponent('field_images');
    $field_displays = $this->entityDisplayRepository->getViewModeOptionsByBundle($field_data['entity_type'], $field_data['bundle']);    

    $form['field']['field_display'] = [
      '#title'  => $this->t('Display mode'),
      '#description' => $this->t('Choose a display mode for the fields parent node. Means what is set as formatting there for these fields will render.'),
      '#type' => 'select',
      '#options' => $field_displays,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $config['field_display'],
      '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
        'visible' => [
           ':input[id="nk-tools-field-reference"]' => ['filled' => TRUE],
        ],
      ],
    ];

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

    // Paragraph
    if ($this->moduleHandler->moduleExists('paragraphs')) {

      $form['paragraph'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Reference Paragraph(s)'),
      ];    

      /*
      $paragraph = BaseFieldDefinition::create('entity_reference_revisions_autocomplete') //'entity_reference_revisions')
        ->setLabel(t('Skill'))
        ->setDescription(t('The ID of the skill'))
        ->setRevisionable(TRUE)
        ->setSetting('target_type', ['paragraph'])
        ->setSetting('handler', 'default:paragraph')
        ->setSetting('handler_settings', ['target_bundles' => ['process' => 'trends_texts']])
        ->setSetting('handler_settings', ['negate' => 0])
        ->setTranslatable(TRUE)
        ->setDisplayOptions('form', [
          'type' => 'entity_reference_paragraphs',
          'title' => t('Test'),
          //'weight' => 5,
          'settings' => [
            'match_operator' => 'CONTAINS',
            'size' => '60',
            'autocomplete_type' => 'tags',
            //'placeholder' => '',
          ],
        ])
        ->setDisplayConfigurable('form', TRUE);

      $form['block_paragraph']['block_paragraph_reference'] = [
        '#type' => 'entity_reference_revisions',
        '#title' => t('Whatever'),  
        '#target_type' => 'paragraph',
        '#selection_handler' => 'default:paragraph',
        '#default_value' => NULL,
        '#selection_settings' => [
          // 'view' => [
          // 'view_name' => 'users_by_name',
          // 'display_name' => 'member',
          // 'arguments' => []
          // ], 
         'target_bundles' => [['process' => 'trends_texts']],
         'autocomplete_type' => 'tags',
         'match_operator' => 'CONTAINS',
         'size' => '60',     
        ]
      ];

      $form['paragraph']['paragraph_reference'] = $paragraph;
      */
 
    
    }
  
    // Relate a View 
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
      '#disabled' => TRUE,
      '#default_value' => [
        'view_id' => $view_id,
        'display' => [
          'display_id' => $config['display_id'],
          'argument' => $config['argument'],
          'filter' => $config['filter'],
        ]
      ],
    ];

    if ($this->moduleHandler->moduleExists('webform')) {
      // A Webform reference
      $form['webform'] = [ 
        '#type' => 'fieldset',
        '#title' => $this->t('Reference webform'),
        '#attributes' => []    
      ];

      $form['webform']['webform_id'] = [
        '#title' => $this->t('Webform'),
        '#type' => 'entity_autocomplete',
        '#target_type' => 'webform',
        //'#required' => TRUE,
        '#default_value' => $this->getWebform(),
      ];
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
   
    $this->configuration['target'] = $values['target_ui']['target'];

    $this->configuration['node_reference'] = $values['node']['node_reference'];

    $this->configuration['field_reference'] = $values['field']['field_reference'];
    $this->configuration['field_display'] = $values['field']['field_display'];
    $this->configuration['image_style'] = $values['field']['image_style'];

    $this->configuration['block_reference'] = $values['block']['block_reference'];
    // $this->configuration['block_content_reference'] = $values['block_block']['block_content_reference'];
    
    // View related values
    if (isset($values['view']) && isset($values['view']['view_id']) && !empty($values['view']['view_id'])) {

      $this->configuration['view_id'] = $values['view']['view_id'];

      if (isset($values['view']['display']) && !empty($values['view']['display'])) {
        $this->configuration['display_id'] = isset($values['view']['display']['display_id']) && !empty($values['view']['display']['display_id']) ? $values['view']['display']['display_id'] : NULL;
        $this->configuration['argument'] = isset($values['view']['display']['argument']) && !empty($values['view']['display']['argument']) ? $values['view']['display']['argument'] : NULL;
        $this->configuration['filter'] = $values['view']['display']['filter'];
      }
      else {
        $this->configuration['display_id'] = NULL;
        $this->configuration['argument'] = NULL;  
        $this->configuration['filter'] = NULL;
      } 
    }
    else {
      $this->configuration['view_id'] = NULL;
      $this->configuration['display_id'] = NULL;
      $this->configuration['argument'] = NULL;  
      $this->configuration['filter'] = NULL; 
    }

    $this->configuration['webform_id'] = $values['webform']['webform_id'];

  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $config = $this->getConfiguration();
    $node = NULL;

    $entity_params = [
      // 'bundle' => 'issue',
      'validate' => TRUE,
      'caller' => '<em>Multi block</em> block', 
    ];
    $node = $this->nkToolsFactory->getNode($entity_params);

    $fields = $this->getBundle($config['field_reference']);
    
    $field_data = [];
    $items = [];

    if (!empty($fields) && $node instanceof NodeInterface) {
      $entity_fields = [];
      
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
     
      $field_data = $this->fields($node, $entity_fields, $config); //$this->fields($node, $fields, $config);  
      $items = $field_data['items'];
    } 
   
    
    if (isset($config['webform_id']) && !empty($config['webform_id'])) {
      $items[] = [
        '#type' => 'webform',
        '#webform' => $this->getWebform(),
        '#default_data' => [],
      ];
    }

    //ksm($items);

    //ksm($config['block_views']);
   /*
    if (isset($config['block_views']['view_id']) && !empty($config['block_views']['view_id'])) {
      //buildView($view_id, $display_id, array $args
    }
    */

    $build = [
      '#list_type' => 'ul',
      '#list_title' => isset($config['block_label']['value']) && !empty($config['block_label']['value']) ? Markup::create($config['block_label']['value']) : $config['label'],
      '#node' => $node,
      '#config' => $config,
      '#items' => $items,
      '#attributes' => [
        'class' => [
          'nk-tools-multi-block-item'
        ],
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
       
       case 'tabs':
         $build['#theme'] = 'nk_tools_collapsible_tabs';
       break;
       
       case 'panel':
         $build['#theme'] = 'nk_tools_collapsible_pane';
       break;

       default:
         $build['#theme'] = 'nk_tools_multi_block';
       break;

    }

    if (isset($field_data['context']) && !empty($field_data['context'])) {
      foreach ($field_data['context'] as $type => $c) {
        $build['#context'][$type] = $c; 
      } 
    }
    else {
      $build['#context'][] = ['list_style' => 'multi-block'];
    } 

/*
    if ($config['block_disable_sheet']) {
      //$build['#attributes']['class'][] = 'diplo-bottom-sheet';
      //$build['#attributes']['class'][] = 'fadeIn';
    }
    else {
      //$build['#attributes']['class'][] = 'diplo-bottom-sheet';
      //$build['#attributes']['class'][] = 'bounceInUp';
    }
*/

/*
    foreach ($items as $key => $item) {
      if (!is_numeric($key) && is_array($item)) {
        foreach ($item as $field_name => $field) {
          $build['#items'][] = Markup::create($field);
        }
      
      }
      else {
        $build['#items'][] =  Markup::create($item);
      }
    }  
*/

    

    //$machine_name = 'block-' . $this->getMachineNameSuggestion();
 
  /*
  if ($config['label_display'] != 'visible') {
      $build['#title'] = isset($config['block_close_icon']) && isset($config['block_close_icon']['value']) && !empty($config['block_close_icon']['value']) ? Markup::create($config['block_close_icon']['value']) : ''; 
    }
    else {
      if (isset($config['block_label']) && isset($config['block_label']['value']) && !empty($config['block_label']['value'])) {
        $build['#title'] = Markup::create($config['block_label']['value']);
      }
    }
*/

   
 /*
   $blur = isset($config['block_blur_under']) && !empty($config['block_blur_under']) ? explode(', ', $config['block_blur_under']) : NULL;
    $build['#attached']['drupalSettings']['diplo_sheet'] = [];
    $build['#attached']['drupalSettings']['diplo_sheet'][$machine_name] = ['blur' => $blur];
*/

    return parent::build() + $build;
  }

  /**
   * Get this block instance webform.
   *
   * @return \Drupal\webform\WebformInterface
   *   A webform or NULL.
   */
  protected function getWebform() {
    return $this->entityTypeManager->getStorage('webform')->load($this->configuration['webform_id']);
  }

  protected function paragraphField(NodeInterface $node, array $entity_info, array $config) {
    $values = [];
    foreach ($node->getFields() as $key => $field) {
      $field_type = $field->getFieldDefinition()->getType();
      $field_settings = $field->getSettings();
      if ($field_type == 'entity_reference_revisions' && $field_settings['target_type'] == 'paragraph') {

        foreach ($field as $delta => $item) {
  
          if ($item->entity instanceof ParagraphInterface && $item->entity->getType() == $entity_info['bundle']) {

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
    return $values; 
  }


  protected function node(NodeInterface $node, array $field, array $config) {
    $context = [
              'entity' => $field['entity_type'],
              'list_type' => 'link',
              'field_type' =>'references',
              'field_name' => $field['field_name']
            ];

            //$items[$field['field_name']] = [];

            $format = [
              'format' => 'links',
              'links_attributes' => [
                'class' => [
                  'nk-tools-multi-block-link',
                  'nk-tools-references-link',
                ]
              ],
              'heading' => [
                'text' => $field['field_name'], 
                'level' => 'h2',
                'attributes' => [
                  'class' => []
                ],
              ],
                    
              'attributes' => [
                'data-list' => $field['field_name'], //Essential
                'class' => [
                  'nk-tools-multi-block-list',
                  'nk-tools-multi-block-references-list',
                  'nk-tools-multi-block-' . str_replace('_', '-', $field['field_name']),
                ]
              ],
              'wrapper_attributes' => [
                'class' => [
                  'nk-tools-multi-block',
                  'nk-tools-multi-block-references'
                ]
              ],
            ];
                  
            if ($config['label_display'] == 'visible') {
              $format['title'] = isset($config['block_label']['value']) && !empty($config['block_label']['value']) ? Markup::create($config['block_label']['value']) : $config['label'];
            }
            
            $target_ids = [];
            foreach($node->get($field['field_name'])->getValue() as $value) {
              $target_ids[] = $value['target_id'];
            }

            $references = []; //$this->nkToolsFactory->getReferencedNodes($field['field_name'], $node->toArray(), FALSE, $format, $target_ids);
            $items = $this->renderer->render($references);
         
        return ['context' => $context, 'items' => $items];

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


              case 'entityreference':
                
                // So far this is only entity reference field to other node
                $nodes = $this->node($item['entity'], $item['field'], $config);

                if (is_array($nodes['items'])) {
                  foreach ($nodes['items'] as $item) {
                    $items[] = $item;
                  }
                }
                else {
                  $items[] = $nodes['items'];
                }

                $context = $nodes['context']; 
 
              break;

              default:
                 
                if (isset($item[0]) && !empty($item[0])) {
                  $context = [
                    'entity' => $field['entity_type'],
                    'list_type' => 'fields',
                  ];

                   foreach ($item as $i => $f) {
                     
                     //if (is_numeric($f['value'])) {
                     //  $target_ids = [$f['value']];
                     //  $f['field']['entity_reference'] = 'node';
                    //   ksm($config['field_display']);
                    //   $items[$delta] = $this->nkToolsFactory->getReferencedNodes($f['entity'], $f['field'], $config['field_display'], $target_ids);
                    // }
                   //  else {
                       $items[$delta] = $this->nkToolsFactory->fieldRender($f['entity'], $f['field'], $config['field_display']);
                   //  }

                     $context['field_name'] = $f['field']['field_name'];
                     //$context['field_name'] .= $i == (count($item) - 1) ? $f['field']['field_name'] : '_' . $f['field']['field_name'];
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
    
    return ['context' => $context, 'items' => $items];
  }
  
}