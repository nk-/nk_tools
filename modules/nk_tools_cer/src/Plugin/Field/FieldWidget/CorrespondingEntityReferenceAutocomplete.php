<?php


namespace Drupal\nk_tools_cer\Plugin\Field\FieldWidget;


use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Query\Sql\Query;

use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;

use Drupal\Core\Render\Element;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\Core\Render\Markup;


use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Site\Settings;

use Drupal\node\Entity\NodeType;
use Drupal\field\Entity\FieldConfig;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Crypt;

use Drupal\nk_tools_cer\Plugin\Validation\ValidCorrespondingReferenceTrait;
use Drupal\nk_tools_cer\Plugin\Validation\Constraint\ValidCorrespondingReference;


/**
 * @FieldWidget(
 *   id = "corresponding_entity_reference_autocomplete",
 *   label = @Translation("Corresponding Autocomplete"),
 *   description = @Translation("An autocomplete text field with two-way utilisation of the values across parent nodes."),
 *   field_types = {
 *     "entity_reference",
       "corresponding_entity_reference"
 *   }
 * )
 */
class CorrespondingEntityReferenceAutocomplete extends EntityReferenceAutocompleteWidget {

  // Using this trait that is as such suitable and available for this plugin class and for matcher controller too
  use ValidCorrespondingReferenceTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'corresponding_type' => 'fields',
      'corresponding_content_types' => NULL,
      'corresponding_self' => NULL,
      'corresponding_other'=> NULL
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {

    $settings = $this->getSettings();

    // Set some essential values into $form_state storage
    $referenced_entities = $items->referencedEntities();
   
    $entity_type = $this->fieldDefinition->getSettings()['target_type'];
    $field_name = $this->fieldDefinition->getName();

    $target_ids = [];
    if (!empty($referenced_entities)) {
      foreach ($referenced_entities as $index => $entity) {
        $target_ids[$index] =  $entity->id();
      }
    }

    // Append the match operation to the selection settings.
    $selection_settings = $this->getFieldSetting('handler_settings') + [
      'match_operator' => $settings['match_operator'],
      'match_limit' => $settings['match_limit'],
      'corresponding_type' => $settings['corresponding_type'],
      'corresponding_content_types' => $settings['corresponding_content_types'],
      'corresponding_self' => $settings['corresponding_self'],
      'corresponding_other' => $settings['corresponding_other'],
    ];

    $valid = static::validFields($field_name, $entity_type, $selection_settings);
    
    $reference_params = [
      'selection_settings' => $selection_settings,
      'entity_type' => $entity_type, // aka node, or view or user etc.
      'entity' => $items->getEntity(), // Get current entity
      'field_name' => $field_name,
      'field_label' => $this->fieldDefinition->getLabel(),
      'target_types' => isset($this->getFieldSetting('handler_settings')['target_bundles']) && !empty($this->getFieldSetting('handler_settings')['target_bundles']) ? array_keys($this->getFieldSetting('handler_settings')['target_bundles']) : [], // Target bundles, content types selected for referencing on field configuration
      'error_delta' => 0,
      'referenced_entities' => $referenced_entities, // Default values for the field, already previously referenced from/by current entity
      'target_ids' => $target_ids,
      'corresponding_field_names' => !empty($valid) ? array_keys($valid) : [],
      'reverse_matches' => !$this->isDefaultValueWidget($form_state) && $items->getEntity()->id() ? $this->queryMatches($items, $entity_type, $field_name, $selection_settings, $target_ids) : [],
    ];

    $form_state->set('reference_params', $reference_params); 

    $elements = parent::form($items, $form, $form_state, $get_delta);
    
    //$form['#submit'][] = [static::class, 'validate'];
    return $elements;
  }
 

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
   
   $element = parent::settingsForm($form, $form_state);
   $settings = $this->getSettings();

   $content_types_references = !empty($settings['corresponding_content_types']) ? $this->getContentTypes($settings['corresponding_content_types']) : NULL;
   $field_references = !empty($settings['corresponding_other']) ? $this->getFieldEntities($settings['corresponding_other']) : NULL;
   
   $element['corresponding_type'] = [
     '#tree' => FALSE,
     '#title' => t('Corresponding type'),
     '#type' => 'radios',
     '#options' => [
       'content_types' => t('Content types'),
       'fields' => t('Fields'), 
     ],
     '#description' => t('Choose either to listen to corresponding nodes of a specified content types or to "listen" to relation between specified fields.'),
     '#default_value' =>  $settings['corresponding_type'],
   ];   

   $element['corresponding_content_types'] = [
     '#type' => 'entity_autocomplete',
     '#title' => t('Corresponding content types'),
     '#target_type' => 'node_type',
     '#tags' => TRUE,
     '#multiple' => TRUE,
     '#maxlength' => '1024',
     '#default_value' => $content_types_references, // The #default_value can be either an entity object or an array of entity objects.
     '#description' => t('Select Content types whose nodes will be automatically reverse referenced.'),
     '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
       'invisible' => [
         ':input[name="corresponding_type"]' => ['value' => 'fields'],
       ],
     ],

   ];

   $element['corresponding_self'] = [
     //'#tree' => FALSE,
     '#title' => t('Corresponding with same field'),
     '#type' => 'checkbox',
     '#description' => t('Check this to enable two way corresponding with the same field on another content type which is selected on this field config. Note: in case this is checked, value for a next field does not apply'),
     '#default_value' =>  $settings['corresponding_self'],
     '#states' => [
       'invisible' => [
         ':input[name="corresponding_type"]' => ['value' => 'content_types'],
         ':input[name="corresponding_other"]' => ['filled' => TRUE],
       ],
     ],

   ];

   $element['corresponding_other'] = [
     '#title' => t('Or select other corresponding field'),
     '#type' => 'entity_autocomplete',
     '#target_type' => 'field_config',
     '#tags' => TRUE,
     '#multiple' => TRUE,
     '#maxlength' => '1024',
     '#default_value' => $field_references && isset($field_references['entities']) ? $field_references['entities'] : NULL, // The #default_value can be either an entity object or an array of entity objects.
     '#description' => t('Select (other) entityreference field to correspond and act two way with this one.'),
     '#states' => [
       'visible' => [
         ':input[name="corresponding_type"]' => ['value' => 'fields'],
       ],
       'invisible' => [
         //':input[name="corresponding_type"]' => ['value' => 'content_types'],
         ':input[name="corresponding_self"]' => ['checked' => TRUE],
       ],
     ],

   ];

   return $element;
   
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
   
    $summary = parent::settingsSummary();
    $settings = $this->getSettings();

    if ($settings['corresponding_type'] == 'content_types') {
      if ($settings['corresponding_content_types'] && !empty($settings['corresponding_content_types'])) {
        $content_types_references = !empty($settings['corresponding_content_types']) ? $this->getContentTypes($settings['corresponding_content_types']) : NULL;
        if (is_array($content_types_references) && !empty($content_types_references)) {
          $labels = ''; 
          $i = 0;
          foreach ($content_types_references as $content_types_reference) {
            if ($content_types_reference) {
              $labels .= $i == 0 ? $content_types_reference->label() .' (' . $content_types_reference->id() .')' : ', ' . $content_types_reference->label() .' (' . $content_types_reference->id() .')';
              $i++;
            }
          }
          $summary[] = t('Corresponding with: <em>@corresponding</em>', ['@corresponding' => $labels]); 
        }
      }
    }
    else if ($settings['corresponding_type'] == 'fields') {
      if ($settings['corresponding_self']) {
        $summary[] = t('Corresponding two way with the same field');
      }
      else {
        if ($settings['corresponding_other'] && !empty($settings['corresponding_other'])) {
          $field_references = $this->getFieldEntities($settings['corresponding_other']);
          if ($field_references && isset($field_references['entities'])) {
            $labels = ''; 
            foreach ($field_references['entities'] as $i => $field_reference) {
              if ($field_reference) {
                $labels .= $i == 0 ? $field_reference->getLabel() .' (' . $field_reference->getName() .')' : ', ' . $field_reference->getLabel() .' (' . $field_reference->getName() .')';
              }
            }
            $summary[] = t('Corresponding with: <em>@corresponding</em>', ['@corresponding' => $labels]);  
          }
          else {
            $summary[] = t('No other field corresponding');
          }
        }
        else {
          $summary[] = t('Not corresponding'); 
        }
      }
    }

    return $summary;
  }

  /*
  public static function save(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $reference_params = $form_state->get('reference_params');
    $trigger = $form_state->getTriggeringElement();
  }
  */

  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);
    $reference_params = $form_state->get('reference_params');
    $trigger = $form_state->getTriggeringElement();
    $corresponding_type = $this->getCorrespondingType();

    if ($trigger && isset($trigger['#executes_submit_callback']) && $trigger['#executes_submit_callback'] == TRUE) {
    
      $corresponding_entities = [];

/*
      if (!empty($reference_params['referenced_entities'])) {
        $corresponding_field_names =  $reference_params['corresponding_field_names'];
        if (!empty($corresponding_field_names)) {
          foreach ($reference_params['referenced_entities'] as $referenced_entity) {
            if ($referenced_entity instanceof EntityInterface) {
              foreach ($corresponding_field_names as $corresponding_field_name) {
                if ($referenced_entity->hasField($corresponding_field_name)) {
                  $corresponding_entities[$referenced_entity->id()] = [ //[$reference_params['field_name']] = [
                    'entity' => $referenced_entity,
                    'field' => $referenced_entity->get($corresponding_field_name),
                    'field_name' => $corresponding_field_name,
                  ];
                }
              }
            }
          }  
        }
      }
*/

      //ksm(array_keys($corresponding_entities));
      if (!empty($values)) {

        ksm($form_state->getValues());

        $max = count($values);
        foreach ($values as $delta => $value) {
          if (isset($value['target_id']) && !empty($value['target_id'])) {
          //if (!in_array($value['target_id'], array_values($reference_params['target_ids']))) {
            
            $entityTypeManager = \Drupal::service('entity_type.manager');
            $storage = $entityTypeManager->getStorage($reference_params['entity_type']);
            $corresponding_entity = $storage->load($value['target_id']);
            //ksm($corresponding_entity->getTitle() . ' (' . $value['target_id'] .')');
            //ksm($reference_params['corresponding_field_names']);
            //ksm($reference_params['reverse_matches']);
           
            //ksm(array_keys($reference_params['reverse_matches']));

            if (!empty($reference_params['reverse_matches'])) {
              
              foreach ($reference_params['reverse_matches'] as $id => $corresponding) {
                //foreach ($corresponding as $bundle => $id) {
                //ksm($corresponding); 
                if ($corresponding_entity->getType() == $corresponding['bundle'] && $corresponding_entity->hasField($corresponding['field_name'])) {
                  //$corresponding_entities[$corresponding['field_name']] = ['entity' => $corresponding_entity, 'field' => $corresponding_entity->get($corresponding['field_name']), 'field_name' => $corresponding['field_name']];
                  $corresponding_entities[$corresponding_entity->id()] = [
                    'entity' => $corresponding_entity,
                    'field_name' => $corresponding['field_name'],
                    'field' => $corresponding_entity->get($corresponding['field_name']),
                  ];
                }
                
              }
            }
            else {
              $valid = static::validFields($reference_params['field_name'], $reference_params['entity_type'], $reference_params['selection_settings']);
              if (!empty($valid)) {
                foreach ($valid as $corresponding_field_name => $bundles) {
                  if ($corresponding_entity->hasField($corresponding_field_name)) {
                    $corresponding_entities[$corresponding_entity->id()] = [
                      'entity' => $corresponding_entity,
                      'field_name' => $corresponding_field_name,
                      'field' => $corresponding_entity->get($corresponding_field_name),
                    ];
                  }            
                }
              }
            }
          }
          else {
             if ($delta < $max) {
               //ksm($delta);
               //ksm($reference_params['reverse_matches']);
             }
          }
            
         // }
        }
      }

      ksm(array_keys($corresponding_entities));
     // ksm($values);
      //ksm(array_keys($reference_params['referenced_entities']));


      if (!empty($corresponding_entities)) {

        $target_ids = [];

        foreach ($corresponding_entities as $id  => $entity) {

          if (isset($entity['entity']) && $entity['entity'] instanceof EntityInterface && isset($entity['field']) && $entity['field'] instanceof FieldItemListInterface) {

            $field_values[$id] = $entity['field']->getValue();  

            if (!empty($field_values[$id])) {
              $target_ids[$id] = []; 

              $count = count($field_values[$id]) - 1; // It is -1 because field item list for multiple values always adds additional empty field instance 
              
              foreach ($field_values[$id] as $index => $field_value) {
                if (isset($field_value['target_id']) && !empty($field_value['target_id'])) {

                 
    
                  if ($field_value['target_id'] != $reference_params['entity']->id() && !isset($target_ids[$id][$reference_params['entity']->id()])) {
                    $target_ids[$id][$reference_params['entity']->id()] = ['target_id' => $reference_params['entity']->id(), 'delta' => $count]; 
                  } 


                  if (!isset($target_ids[$id][$field_value['target_id']]) && $reference_params['entity']->id() == $field_value['target_id']) {
                    $current_index = $count == $index ? $index + 1 : $index;
                    $target_ids[$id][$field_value['target_id']] = ['target_id' => $field_value['target_id'], 'delta' => $current_index];
                  }



                }
                else {
                  if ($field_value['target_id'] != $reference_params['entity']->id() && !isset($target_ids[$id][$reference_params['entity']->id()])) {
                    $target_ids[$id][$reference_params['entity']->id()] = ['target_id' => $reference_params['entity']->id(), 'delta' => $index];
                  }  
                }
              }
            }
            
            // Target entity does not have any values for this field yet 
            else {
              $target_ids[$reference_params['entity']->id()] = ['target_id' => $reference_params['entity']->id() , 'delta' => 0];
            }   
      
            // It is safe to assume that this very enitty now can be programmatically saved at its destination reference entity
            if (isset($target_ids[$id])) { // && in_array($reference_params['entity']->id(), array_keys($field_values))) {
              //ksm($target_ids);
              
              //
              $sorted_value = array_values($target_ids[$id]);
              usort($sorted_value, function($a, $b) {
                 return $a['delta'] <=> $b['delta'];
              });

              $dev = TRUE;
              //ksm($id);
              //ksm($sorted_value);
              if (!$dev) {
               
                $entity['field']->setValue($sorted_value);
                $entity['entity']->save();
              
              }

            }

          }
        }
      }

    }      

    return $values;
  }
 

  

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {   
  
    //$element['#submit'] = [[static::class, 'submit']];
    //$element['#process'][] = [static::class, 'processParent'];
    $settings = $this->getSettings();
   
    $widget = parent::formElement($items, $delta, $element, $form, $form_state);   
    $widget['target_id']['#selection_settings'] += [
      'corresponding_type' => $settings['corresponding_type'],
      'corresponding_content_types' => $settings['corresponding_content_types'],
      'corresponding_self' => $settings['corresponding_self'],
      'corresponding_other' => $settings['corresponding_other'],  
    ];
   
    // Store the selection settings in the key/value store and pass a hashed key in the route parameters.
    $selection_settings = isset($widget['target_id']['#selection_settings']) ? $widget['target_id']['#selection_settings'] : [];
    $data = serialize($selection_settings) . $widget['target_id']['#target_type'] . $widget['target_id']['#selection_handler'];
    $selection_settings_key = Crypt::hmacBase64($data, Settings::getHashSalt());

    $widget['#element_validate'] = [[static::class, 'validate']];
    

    $widget['target_id']['#process'][] = [static::class, 'processAutocomplete'];
    
    $reference_params = $form_state->get('reference_params');

    $widget['target_id']['#autocomplete_route_name'] = 'nk_tools_cer.entity_autocomplete';
    $widget['target_id']['#autocomplete_route_parameters'] = [
      //'bundle' => $reference_params['entity']->getType(),
      'field_name' => $reference_params['field_name'],
      'target_type' => $widget['target_id']['#target_type'],
      'selection_handler' => $widget['target_id']['#selection_handler'],
      'selection_settings_key' => $selection_settings_key,
    ];
  
       
/*
    $widget['corresponding'] = [
      '#type' => 'hidden',
      '#delta' => $delta,
      //'#value' => NULL,
      // '#weight' => 10,
      //'#process' => [[ static::class, 'processCorresponding' ]]
    ];
*/

/*
    $widget['messages'] = [
       '#type' => 'markup',
       '#markup' => '',
       '#weight' => 10, 
    ];
*/


    \Drupal::service('keyvalue')->get('entity_autocomplete')->set($selection_settings_key, $selection_settings); //get keyValue->get($selection_settings_key, FALSE))-

    /*
    // Display information (#description) about vallue's origin, either it is #default_Value for this entity or somes as pre-populated match, other entity having this one in its reference field
    $reference_params = $form_state->get('reference_params');
    if (!empty($reference_params['reverse_matches'])) {
      $reverse_matches = $reference_params['reverse_matches']; //static::processReverseMatches($reference_params['reverse_matches'], $form_state);
      if (!empty($reverse_matches)) {
        foreach ($reverse_matches as $id => $corresponding) {
          if (!in_array($id, $reference_params['target_ids'])) {
                  $destination_title = '';
             $widget['messages']['#markup'] =  t('This is <strong>pre-populated value</strong>, destination entity <em>@destination_title</em> has reference to this entity.', ['@destination_title' => $destination_title]);
                  $widget['corresponding']['#value'] =  $reference_params['entity_type'] . '.' . $corresponding['bundle'] . '.' . $corresponding['field_name']; 
                     
    
              }
              else {
                $widget['messages']['#markup'] =  t('This is a <strong>default value</strong> for this field on this entity. It was previously saved');
              }
         }
       }
       else {
         $widget['messages']['#markup'] =  t('This is a <strong>default value</strong> for this field on this entity. It was previously saved');
       }
       
     }
     else {
       $widget['messages']['#markup'] =  t('This is a <strong>default value</strong> for this field on this entity. It was previously saved');
     }
     */


    /*
      $view_data = !empty($widget['target_id']['#selection_settings']) && isset($widget['target_id']['#selection_settings']['view']) ? $widget['target_id']['#selection_settings']['view'] : NULL;
      if ($view_data) {
        $target_types = [];
      }
      else {
        $target_types = !empty($widget['target_id']['#selection_settings']) && isset($widget['target_id']['#selection_settings']['target_bundles']) ? array_keys($widget['target_id']['#selection_settings']['target_bundles']) : [];
      }
    */

    // $element['#element_validate'] = [[static::class, 'validate']];

    return $widget;
  }


  /*
  public static function processCorresponding(&$element, FormStateInterface $form_state, &$complete_form) {
 
    $reference_params = $form_state->get('reference_params');
    if (!empty($reference_params['reverse_matches'])) {

      
      //$reverse_matches = static::processReverseMatches($reference_params['reverse_matches'], $form_state);

      if (!empty($reference_params['reverse_matches'])) {
       // $i = 0;
        foreach ($reference_params['reverse_matches'] as $id => $corresponding) {
          if (!in_array($id, $reference_params['target_ids'])) {
            $parents = array_slice($element['#array_parents'], 0, -1);
            $up_parents = $parents + ['target_id'];
            $parent = NestedArray::getValue($complete_form, $up_parents); 

            //$element['#value'] = $reference_params['entity_type'] . '.' . $corresponding['bundle'] . '.' . $corresponding['field_name'];
           // $i++; 
          }
        }
      }
      else {
     //  $element['#checked'] = TRUE;
    //   $element['#default_value'] =  1;
     }
    
    }

    
    return $element;
  
  }
  */

  public static function processAutocomplete(&$element, FormStateInterface $form_state, &$complete_form) {
   
    $url = NULL;
    $access = FALSE;

    if (!empty($element['#autocomplete_route_name'])) {
      $parameters = isset($element['#autocomplete_route_parameters']) ? $element['#autocomplete_route_parameters'] : []; 
      $url = Url::fromRoute($element['#autocomplete_route_name'], $parameters)->toString(TRUE);
      /** @var \Drupal\Core\Access\AccessManagerInterface $access_manager */
      $access_manager = \Drupal::service('access_manager');
      $access = $access_manager->checkNamedRoute($element['#autocomplete_route_name'], $parameters, \Drupal::currentUser(), TRUE);
    }

    if ($access) {
      $metadata = BubbleableMetadata::createFromRenderArray($element);
      if ($access->isAllowed()) {
        $element['#attributes']['class'][] = 'form-autocomplete';
        $metadata->addAttachments(['library' => ['core/drupal.autocomplete']]);
        // Provide a data attribute for the JavaScript behavior to bind to.
        $element['#attributes']['data-autocomplete-path'] = $url->getGeneratedUrl();
        $metadata = $metadata->merge($url);
      }
      $metadata
        ->merge(BubbleableMetadata::createFromObject($access))
        ->applyTo($element);
    }

    // Display information (#description) about value's origin, either it is #default_Value for this entity or somes as pre-populated match, other entity having this one in its reference field
    $reference_params = $form_state->get(['reference_params']);
  
    if (!empty($reference_params['reverse_matches'])) {
      foreach ($reference_params['reverse_matches'] as $id => $corresponding) {
        //if (!in_array($id, $reference_params['target_ids'])) {
          
          // Element's default value array can be consfusing, associates on initial default values
          // Yet, this process callback comes after multiple items were created, or say for each item created
          // @see $itmes->appandItem() in formMultipleElements
          if (is_array($element['#default_value']) && !empty($element['#default_value'])) {
            foreach ($element['#default_value'] as $delta => $default_value) {  
              if ($default_value instanceof EntityInterface) {
                if (!in_array($default_value->id(), $reference_params['target_ids'])) {
                  $destination_title = $default_value->getTitle() . ' (' . $default_value->id() .')';
                  $element['#description'] =  t('This is <strong>pre-populated value</strong>, destination entity <em>@destination_title</em> has reference to this entity.', ['@destination_title' => $destination_title]);
                }
                else {
                  if (isset($element['#default_value'])) {
                    $element['#description'] =  t('This is a <strong>default value</strong> for this field on this entity. It was previously saved');
                  }
                }
              }
            } 
          }
          else {
            if (isset($element['#default_value'])) {
              $element['#description'] =  t('This is a <strong>default value</strong> for this field on this entity. It was previously saved');
            }
          }
         
        /*
        }
        // Note, this means we are on node that does not have references yet in its field
        else {
           // $corresponding_field_names = [];
           $referencing_node = \Drupal::service('entity_type.manager')->getStorage($element['#target_type'])->load($id);
           //ksm($referencing_node->getTitle() . ' (' . $referencing_node->id() .')'); 
           if ($referencing_node instanceof EntityInterface) {// && !in_array($referencing_node->id(), $reference_params['target_ids'])) {
             $destination_title = $referencing_node->getTitle() . ' (' . $referencing_node->id() .')';
             ksm($destination_title);
             $element['#description'] =  t('This is <strong>pre-populated value</strong>, destination entity <em>@destination_title</em> has reference to this entity.', ['@destination_title' => $destination_title]);
           }
           //ksm($reference_params['target_ids']);   
        }
        */
      }
      //$form_state->set(['reference_params', 'corresponding_field_names'], $corresponding_field_names);
    
    // No reverse matches, set default information to field instances       
    }
    else {
      if (isset($element['#default_value'])) {
        $element['#description'] =  t('This is a <strong>default value</strong> for this field on this entity. It was previously saved');
      }
    }
       
     //}
     //else {
     //  if ($element['#default_value']) {
     //    $element['#description'] =  t('This is a <strong>default value</strong> for this field on this entity. It was previously saved');
     //  }
     //} 
   
     //$element['#element_validate'] = [[static::class, 'validate']];
     return $element;

  }

/*
  public static function processReverseMatches(&$element, FormStateInterface $form_state) {
    
    $reverse_matches = [];
      
    $reference_params = $form_state->get('reference_params');
    if (!empty($reference_params['reverse_matches'])) {
      foreach ($reference_params['reverse_matches'] as $corresponding_field_name => $corresponding_bundles) { //$reverse_matches[$corresponding_field_name][$bundle][$id] = $id;
        foreach ($corresponding_bundles as $bundle => $corresponding_data) {
          foreach ($corresponding_data as $id) {
            $reverse_matches[$id] = [
              'field_name' => $corresponding_field_name,
              'bundle' => $bundle,
            ];
          }
        }
      }
    }
    return $reverse_matches;
  }
*/
  
  public function getCorrespondingType() {
  
    $settings = $this->getSettings();
      
    $corresponding_type = NULL;
      
    if ($settings['corresponding_type'] == 'fields') {
      if ($settings['corresponding_self']) {
       $corresponding_type = 'corresponding_self';
      }
      else {
        if ($settings['corresponding_other'] && !empty($settings['corresponding_other'])) {
          $field_references = $this->getFieldEntities($settings['corresponding_other']);
          if ($field_references && isset($field_references['entities']) && !empty($field_references['entities'])) {
            $corresponding_type = 'corresponding_other';
          }
        }
      }
    }
    return $corresponding_type;
  }

/*
  public static function validFields($field_name, $target_type, $selection_settings) {  

    $corresponding_fields = []; 
    $errors = [];
    $valid = [];
    $target_bundles = !empty($selection_settings['target_bundles']) ? array_keys($selection_settings['target_bundles']) : NULL; 

    if ($target_bundles) {
      if ($selection_settings['corresponding_self']) { 
        foreach ($target_bundles as $d => $target_bundle) {
          $target_entity_fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($target_type, $target_bundle);
          if (in_array($field_name, array_keys($target_entity_fields))) {
            $valid[$field_name][$target_bundle] = $target_bundle;
          }
        }
      }
      else if (!$selection_settings['corresponding_self'] && !empty($selection_settings['corresponding_other'])) {
        foreach ($selection_settings['corresponding_other'] as $delta => $value) {
          if (isset($value['target_id']) && strpos($value['target_id'], '.') !== FALSE) {
            $values = explode('.', $value['target_id']);
             foreach ($target_bundles as $d => $target_bundle) {
              $target_entity_fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($target_type, $target_bundle);
              if (in_array($values[2], array_keys($target_entity_fields))) {
                $valid[$values[2]][$target_bundle] = $target_bundle;
              }
            }
          }
        }
      }
    }
    return $valid;
  }
*/



  protected function queryMatches(FieldItemListInterface $items, $entity_type, $field_name, $selection_settings, array $target_ids = [], $delta = 0) { 
 
    $entity = $items->getEntity();
    //ksm($items->getValue());

    $valid = static::validFields($field_name, $entity_type, $selection_settings);
    $reverse_matches = [];

    if (!empty($valid)) {
      
      $corresponding_type = $this->getCorrespondingType();
     
      foreach ($valid as $corresponding_field_name => $bundles) {
        
        //$reverse_matches[$corresponding_field_name] = [];
 
        foreach($bundles as $bundle) {
          
          //$reverse_matches[$corresponding_field_name][$bundle] = [];

          $target_entity_fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $bundle);
          if (in_array($corresponding_field_name, array_keys($target_entity_fields))) {
            $query = $this->queryCorresponding($entity_type, $corresponding_field_name, $entity->id(), [$bundle]);
            if ($query) { // && !empty($this->getSetting('corresponding_other'))) {
              $query_values = $query->execute();
              //ksm($query_values);
              if (!empty($query_values)) { 

                foreach (array_values($query_values) as $ii => $id) {
                  if ($corresponding_type == 'corresponding_self') {
                    if (!in_array($id, $target_ids)) {// && $corresponding_field_name != $field_name) { 
                      //$reverse_matches[$corresponding_field_name][$bundle][$id] = $id;
                      $reverse_matches[$id] = [
                        'field_name' => $corresponding_field_name,
                       'bundle' => $bundle,
                      ];
                    }
                  }
                  else if ($corresponding_type == 'corresponding_other') {
                   if (!in_array($id, $target_ids)) {// ksm($id);
                    $reverse_matches[$id] = [
                      'field_name' => $corresponding_field_name,
                      'bundle' => $bundle,
                    ];
                   }
                  }
                }
              }
            } 
          }
        }
        //$form_state->set(['reference_params', 'reverse_matches'], $reverse_matches);

      }
   // }
    }

    
    //ksm(array_unique($reverse_matches));
    return $reverse_matches; //$reverse_matches;
  }


  /**
   * Special handling to create form elements for multiple values.
   *
   * Handles generic features for multiple fields:
   * - number of widgets
   * - ajax 'add more' button
   * - table display and drag-n-drop value reordering
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    
    $field_name = $this->fieldDefinition->getName();
    $add_more = is_array($form_state->getTriggeringElement()) && $form_state->getTriggeringElement()['#executes_submit_callback'] ? TRUE : FALSE;

    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $parents = $form['#parents'];

    // Now check for any "light" references, reverse check for content types and fields configured as corresponding
    // If such have reverse reference to this very entity. In case yes we'd like to pre-populate field items' values
    $reference_params = $form_state->get('reference_params'); 

    // Determine the number of widgets to display.
    switch ($cardinality) {
      case FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED:

        $field_state = static::getWidgetState($parents, $field_name, $form_state);

        // Check for possible "offers" via reverse reference, so add number of it to a $max value
        if (!empty($reference_params['reverse_matches']) && !$add_more) {
          $items_count = $items->first() ? (int)$field_state['items_count'] : 0;
          $field_state['items_count'] = $items_count + count(array_keys($reference_params['reverse_matches']));
          static::setWidgetState($parents, $field_name, $form_state, $field_state);
        } 

        $max = $field_state['items_count']; 
        $is_multiple = TRUE;
        break;

      default:
        $max = $cardinality - 1;
        $is_multiple = ($cardinality > 1);
        break;
    }

    $title = $this->fieldDefinition->getLabel();
    $description = FieldFilteredMarkup::create(\Drupal::token()->replace($this->fieldDefinition->getDescription()));

    $elements = [];

    for ($delta = 0; $delta <= $max; $delta++) {
      
      if (!isset($items[$delta])) {
        if (!empty($reference_params['reverse_matches'])) { //  && isset($reverse_matches[$delta])) {
          if (!$add_more) {
            foreach (array_keys($reference_params['reverse_matches']) as $id) {
              $items->appendItem(['target_id' => $id]);
            }
          }
          //else {
          //  ksm($add_more);
          //$items->appendItem();
          //}
        }
        
        // Add a new empty item if it doesn't exist yet at this delta.
        //else {
          //$items->appendItem();
        //}
        $items->appendItem();
      }
      //else {
      //  if (empty($reference_params['reverse_matches'])) {
      //    if (!$add_more) {
      //      foreach (array_keys($reference_params['reverse_matches']) as $id) {
            //  $items->appendItem(['target_id' => $id]);
      //      }
           // $items->appendItem();
       //   }
       // }
      //}


      /*
      if (!isset($items[$delta]) && !$trigger) {
        if (!empty($reverse_matches)  && isset($reverse_matches[$delta])) {
           
           foreach ($reverse_matches as $d => $reverse_match) {
              foreach (array_values($reverse_match) as $inject) {
                $reverse_matches_values[$delta] = $inject;               
                $items->appendItem(['target_id' => $inject]); 
              }
            }
          // }
           //$form_state->set(['reference_params', 'reverse_matches'], $reverse_matches_values); 
         }
        // Add a new empty item if it doesn't exist yet at this delta.
        else {
          $items->appendItem();
        }
      }
      else {
        if (!$trigger && !empty($reverse_matches)  && isset($reverse_matches[$delta])) {
          foreach ($reverse_matches as $reverse_match) {
            foreach (array_values($reverse_match) as $inject) {
              //$reverse_matches_values[$delta] = $inject; 
              $items->appendItem(['target_id' => $inject]);
             // $fs = static::getWidgetState($parents, $field_name, $form_state);
              //$fs['items_count'] = (int)$fs['items_count'] + 1;
              //static::setWidgetState($parents, $field_name, $form_state, $field_state);
 
            }
          }
          $items->appendItem();
          //$form_state->set(['reference_params', 'reverse_matches'], $reverse_matches_values); 
        }
      }
*/


      // For multiple fields, title and description are handled by the wrapping
      // table.
      if ($is_multiple) {
        $element = [
          '#title' => $this->t('@title (value @number)', ['@title' => $title, '@number' => $delta + 1]),
          '#title_display' => 'invisible',
          '#description' => '',
        ];
      }
      else {
        $element = [
          '#title' => $title,
          '#title_display' => 'before',
          '#description' => $description,
        ];
      }

      $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);

      if ($element) {
        // Input field for the delta (drag-n-drop reordering).
        if ($is_multiple) {
          // We name the element '_weight' to avoid clashing with elements
          // defined by widget.
          $element['_weight'] = [
            '#type' => 'weight',
            '#title' => $this->t('Weight for row @number', ['@number' => $delta + 1]),
            '#title_display' => 'invisible',
            // Note: this 'delta' is the FAPI #type 'weight' element's property.
            '#delta' => $max,
            '#default_value' => $items[$delta]->_weight ?: $delta,
            '#weight' => 100,
          ];
        }

        $elements[$delta] = $element;
      }
    }


    //$items->filterEmptyItems();
    //ksm($items->list);

    if ($elements) {
      $elements += [
        '#theme' => 'field_multiple_value_form',
        '#field_name' => $field_name,
        '#cardinality' => $cardinality,
        '#cardinality_multiple' => $this->fieldDefinition->getFieldStorageDefinition()->isMultiple(),
        '#required' => $this->fieldDefinition->isRequired(),
        '#title' => $title,
        '#description' => $description,
        '#max_delta' => $max,
      ];

      // Add 'add more' button, if not working with a programmed form.
      if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && !$form_state->isProgrammed()) {
        $id_prefix = implode('-', array_merge($parents, [$field_name]));
        $wrapper_id = Html::getUniqueId($id_prefix . '-add-more-wrapper');
        $elements['#prefix'] = '<div id="' . $wrapper_id . '">';
        $elements['#suffix'] = '</div>';

        $elements['add_more'] = [
          '#type' => 'submit',
          '#name' => strtr($id_prefix, '-', '_') . '_add_more',
          '#value' => t('Add another item'),
          '#attributes' => ['class' => ['field-add-more-submit']],
          '#limit_validation_errors' => [array_merge($parents, [$field_name])],
          '#submit' => [[get_class($this), 'addMoreSubmit']],
          '#ajax' => [
            'callback' => [get_class($this), 'addMoreAjax'],
            'wrapper' => $wrapper_id,
            'effect' => 'fade',
          ],
        ];
      }
    }

    return $elements;
  }

  /**
   * Validate the field.
   */
  public static function validate(array &$element, FormStateInterface $form_state) {
  
    if (isset($element['#value']) && !empty($element['#value'])) { 
 
      $target_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($element['#value']);
      if ($target_id && isset($element['#target_type']) && !empty($element['#target_type'])) {
  
        $reference_params = $form_state->get('reference_params');
        //$field_state = static::getWidgetState($element['#parents'], $reference_params['field_name'], $form_state);
      
        $entityTypeManager = \Drupal::service('entity_type.manager');
        $storage = $entityTypeManager->getStorage($element['#target_type']);
        $corresponding_entity = $storage->load($target_id);
        
        if ($corresponding_entity instanceof EntityInterface) {
          $corresponding_entity_label = $corresponding_entity->label(); 
          $corresponding_entity_type = $corresponding_entity->getType();
          $corresponding_entity_type_label = $entityTypeManager->getStorage('node_type')->load($corresponding_entity_type)->label();
           
          $corresponding_fields = static::validFields($reference_params['field_name'], $reference_params['entity_type'], $reference_params['selection_settings']);
         
          if (!empty($corresponding_fields)) {
         
            $constraint = new ValidCorrespondingReference();


            foreach ($corresponding_fields as $corresponding_field_name => $corresponding_field) {
              foreach (array_keys($corresponding_field) as $corresponding_bundle) {
              
                if ($corresponding_entity_type == $corresponding_bundle && !$corresponding_entity->hasField($corresponding_field_name)) {

                  $target_field = $element['#target_type'] .'.' . $corresponding_bundle .'.' . $corresponding_field_name;
                  $field_label = \Drupal::service('entity_type.manager')->getStorage('field_config')->load($target_field);
                  $error_message = t($constraint->invalidDestinationFields, [
                    '@field_label' => $field_label ? $field_label->label() . ' (' . $corresponding_field_name . ')' : $corresponding_field_name,
                    '@label' => $corresponding_entity_label,
                    '@type' =>  $corresponding_entity_type,
                    '@type_label' => $corresponding_entity_type_label . ' (' . $corresponding_entity_type .')' 
                  ]);
                   
                  //\Drupal::logger('ErrorMsg')->notice('<pre>' . print_r($error_message, 1) . '<pre>');
                  //$field_state['items_count']
                  //$errorElement = NestedArray:getValue('#array_parents')
                  $form_state->setError($element, $error_message);
                  $plus_one = (int)$reference_params['error_delta'] + 1;
                  $form_state->set(['reference_params', 'error_delta'], $plus_one); 

                }
              }
            }
          }
        }
      }

    }
  
  }

  public static function submit(array &$form, FormStateInterface $form_state) {
  //public function synchronizeCorrespondingField(FieldableEntityInterface $entity, FieldableEntityInterface $correspondingEntity, $correspondingFieldName, $operation = NULL) {

    $reference_params = $form_state->get('reference_params');
    $values = $form_state->getValues();


    /*
    if (is_null($operation)) {
      $operation = CorrespondingReferenceOperations::ADD;
    }

    if (!$correspondingEntity->hasField($correspondingFieldName)) {
      return;
    }

    $field = $correspondingEntity->get($correspondingFieldName);

    $values = $field->getValue();

    $index = NULL;

    foreach ($values as $idx => $value) {
      if ($value['target_id'] == $entity->id()) {
        if ($operation == CorrespondingReferenceOperations::ADD) {
          return;
        }

        $index = $idx;
      }
    }

    $set = FALSE;

    switch ($operation) {
      case CorrespondingReferenceOperations::REMOVE:
        if (!is_null($index)) {
          unset($values[$index]);
          $set = TRUE;
        }
        break;
      case CorrespondingReferenceOperations::ADD:
        $values[] = ['target_id' => $entity->id()];
        $set = TRUE;
        break;
    }

    if ($set) {
      $field->setValue($values);
      $correspondingEntity->save();
    }
*/
  
  }

  public function queryCorresponding($entity_type, $field_name, $id = NULL, array $target_types = [] ) {
    // Query with entity_type.manager (The way to go)
    $query = \Drupal::service('entity_type.manager')->getStorage($entity_type);
   
   // $this->entityTypeManager->getStorage('node');
    $query_result = $query->getQuery()->condition('status', 1);
   
    if ($id) {
      $query_result->condition($field_name, $id);
    }
    
    if (!empty($target_types)) {
      $query_result->condition('type', $target_types, 'IN');
    }
    //$query_result->execute();

    //$query = \Drupal::entityQuery($entity_type);
    //$query->condition('status', NODE_PUBLISHED)
    //->condition('type', $target_type)
    //->condition($field_name, $id);
    //->condition('custom_taxonomy', [2, 8], 'IN')
    //->condition('custom_taxonomy.%delta', 2, '=')
    //->condition('id', $id);
    //->sort('field_last_name', DESC);
    //$or = $query->orConditionGroup();
    //$or->condition('custom_taxonomy.0.target_id', 2);
    //$or->condition('custom_taxonomy.0.target_id', 8);
    //$query->condition($or);
    return $query_result;
  }


  public function getContentTypes($values) {
    $ids = [];
    foreach ($values as $delta => $value) {
      if (isset($value['target_id']) && !empty($value['target_id'])) {
        $ids[$value['target_id']] = $value['target_id']; 
      }
    }
    return !empty($ids) ? NodeType::loadMultiple(array_keys($ids)) : NULL;
  }


  public function getFieldEntities($values) {
    $entities = [
      'entities' => [],
      'definition' => [],
    ];
    foreach ($values as $delta => $value) {
      if (isset($value['target_id']) && strpos($value['target_id'], '.') !== FALSE) {
        $entities['entities'][$delta] = \Drupal::service('entity_type.manager')->getStorage('field_config')->load($value['target_id']);
        $entities['definition'][$delta] = explode('.', $value['target_id']);
         //FieldConfig::loadByName($field_name_parts[0], $$field_name_parts[1], $field_name_parts[2]);    
      }
    }
    return !empty($entities['entities']) ? $entities : NULL;
  }

  /*
  public static function submitInput(&$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }
 
  public static function ajaxInput(&$form, FormStateInterface $form_state) {
    
     //$form_state->setRebuild();
     $field_name = $form_state->get('field_name');

     $trigger = $form_state->getTriggeringElement();
     //array_pop($trigger['#parents']);
     $keys = array_filter($trigger['#parents'], function($var) {
      if (is_int($var)) {
        return $var;
      } 
    });

    if (is_array($keys)) {
      $delta = empty($keys) ? 0 : reset($keys);
    }
    else {
      $delta = 0;
    }

    // $parents = array_fill_keys($trigger['#parents'], $trigger['#parents']);

    // $parents = array_slice($trigger['#parents'], 0, -1);
    // $parents[] = 'display';
    // $parents[] = 'display_id';
    //$element = NestedArray::getValue($form, $trigger['#parents']);
    $element = array_pop($trigger['#parents']);
    //\Drupal::logger('Trigger')->notice('<pre>' . print_r($element, 1) . '<pre>');
     //return $element;
    $form[$field_name]['widget'][$delta][$element]['#default_value'] = NULL;
    return $form[$field_name]['widget'][$delta][$element];

  }
  */
    
   /**
   * Submission handler for the "Add another item" button.
   */
  /*
  public static function addMoreSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    // Increment the items count.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $field_state['items_count']++;
    static::setWidgetState($parents, $field_name, $form_state, $field_state);
    \Drupal::logger('Trigger')->notice('<pre>' . print_r($field_state, 1) . '<pre>');
    $form_state->setRebuild();
  }
*/
 /*
 public function defaultValues($entity_type, EntityInterface $entity, $field_name, $delta, $view_data = NULL, array $target_types = []) {
    $values = NULL;
    if ($entity->get($field_name) instanceof EntityReferenceFieldItemList) { // This field has some values
      // First check on existing values saved in entity previously
      if (!empty($entity->get($field_name)->getValue())) {
        $values = [];
        foreach ($entity->get($field_name)->getValue() as $index => $value) {
          $target_id = isset($value['target_id']) && !empty($value['target_id']) ? $value['target_id'] : NULL;
          if ($target_id && $index == $delta) {
            $values[$delta] = [
              'entity' => Node::load($target_id)
            ];
          }
        }
      }
    }
    else { // No any values for this field
      if ($view_data) {
        $values = $this->processView($view_data, $entity_type, $entity);
      }
      else {
        $query = $this->queryCorresponding($entity_type, $target_types, $field_name, $entity->id()); 
        if ($query) {
          $query_values = $query->execute();
          foreach (array_values($query_values) as $nid) {
            $values[$delta] = [
              'entity' => Node::load($nid)
            ];
          } 
        }
      }
    }
    return $values;
  } 
*/


  
}