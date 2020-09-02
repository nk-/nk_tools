<?php

namespace Drupal\nk_tools_cer\Plugin\Field\FieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\node\Entity\Node;
use Drupal\views\Views;

/**
 * @FieldType(
 *   id = "corresponding_entity_reference",
 *   label = @Translation("Corresponding Entity reference"),
 *   description = @Translation("An entity field cfor two-way utilising entity reference."),
 *   category = @Translation("Reference"),
 *   default_widget = "corresponding_entity_reference_autocomplete",
 *   default_formatter = "corresponding_entity_reference_view",
 *   list_class = "\Drupal\nk_tools_cer\Plugin\Field\CorrespondingEntityReferenceFieldItemList",
 * )
 */


//*   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
// *   constraints = {"ValidCorrespondingReference" = {}}

// 

class CorrespondingEntityReference extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  /*
  public static function defaultStorageSettings() {
    return [
      'target_type' => \Drupal::moduleHandler()->moduleExists('node') ? 'node' : 'user',
      'corresponding' => 1,
    ] + parent::defaultStorageSettings();
  }
  */

  /**
   * {@inheritdoc}
   */
  /*
  public static function defaultFieldSettings() {
    return [
      'handler' => 'corresponding_entity_reference_selection', //'default',
      'handler_settings' => [],
    ] + parent::defaultFieldSettings();
   }
   */

  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $corresponding = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Corresponding'));
      //->setRequired(TRUE);
    $properties['corresponding'] = $corresponding;
    return $properties;
  }

  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['corresponding'] = [
      'type' => 'int',
      'size' => 'tiny',
      //'unsigned' => TRUE,
    ];
    return $schema;
  }
  
  /**
   * Defines custom post-save behavior for field values.
   *
   * This method is called during the process of saving an entity, just after
   * values are written into storage. This is useful mostly when the business
   * logic to be implemented always requires the entity identifier, even when
   * storing a new entity. For instance, when implementing circular entity
   * references, the referenced entity will be created on pre-save with a dummy
   * value for the referring entity identifier, which will be updated with the
   * actual one on post-save.
   *
   * In the rare cases where item properties depend on the entity identifier,
   * massaging logic will have to be implemented on post-save and returning TRUE
   * will allow them to be rewritten to the storage with the updated values.
   *
   * @param bool $update
   *   Specifies whether the entity is being updated or created.
   *
   * @return bool
   *   Whether field items should be rewritten to the storage as a consequence
   *   of the logic implemented by the custom behavior.
   */
  /*
  public function postSave($update) {
    parent::postSave($update); 
    
    if ($this->target_id) {
      $field_name = $this->getFieldDefinition()->getName();
      $target_type = $this->defaultStorageSettings()['target_type'];
      $storage = \Drupal::service('entity_type.manager')->getStorage($target_type);
      $referenced_entity = $storage->load($this->target_id);
      ksm($referenced_entity->id());
      if (!$referenced_entity->hasField($field_name)) {
        //ksm($referenced_entity->getTitle());
      }
      else {
        
      }
    } 
     //ksm($this->defaultStorageSettings());
  }
  */

  public function correspondingReferences($entity_type, $entity, $field_name, $delta, $view_data = NULL, array $target_types = []) {
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
        //ksm($entity->id());
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

  public function processView($view_data, $entity_type, $entity) {
    $values = [];
    $args = $view_data['arguments'];
    $view_name = $view_data['view_name'];
    $display_name = $view_data['display_name'];
              
    // If cacheability metadata should not be bubbled then we need to pass in our own BubbleableMetadata which will prevent any metadata
    // generated from automatically bubbling to the render context.
    $bubbleable_metadata = new BubbleableMetadata();
        
    // Replace tokens for each argument.
    foreach ($args as $key => $arg) {
      $value =  \Drupal::service('token')->replace($arg, [$entity_type => $entity], ['clear' => TRUE], $bubbleable_metadata);
      $args[$key] = !empty($value) ? $value : NULL;
    }
    $results = $this->initializeView($view_name, $display_name, $args); 
    if (is_array($results) && !empty($results)) {
      $delta = 0;
      foreach ($results as $ref_nid => $result) {
        if (is_object($result['#row']) && $result['#row']->_entity instanceof Node) {
          $values[$delta] = [
            'entity' => $result['#row']->_entity,
            'target_type' =>  $result['#row']->_entity->getType(),
          ];
          $delta++;
        }
      }
    }
    return $values;
  }

  
  public function initializeView($view_name, $display_name, $arguments, $match = NULL, $match_operator = 'CONTAINS', $limit = 0, $ids = NULL) {
  
    // Check that the view is valid and the display still exists.
    $view = Views::getView($view_name);
    if (!$view || !$view->access($display_name)) {
      //\Drupal::messenger()->addWarning($this->t('The reference view %view_name cannot be found.', ['%view_name' => $view_name]));
      return FALSE;
    }
    $view->setDisplay($display_name);

    // Pass options to the display handler to make them available later.
    $entity_reference_options = [
      'match' => $match,
      'match_operator' => $match_operator,
      'limit' => $limit,
      'ids' => $ids,
    ];
    $view->displayHandlers->get($display_name)->setOption('entity_reference_options', $entity_reference_options);
    $results = $view->executeDisplay($display_name, $arguments);
    if (is_null($results))  {
      return (array)$results;
    }
    else {
      return $results;
    }
  
  }

  public static function checkRelations(array &$form, FormStateInterface $form_state) {
    
    if ($form_state->get('field_name') && $form_state->get('entity_type')) {
      
      $entity = $form_state->get('entity');
 
      $field_name = $form_state->get('field_name');
      $entity_type = $form_state->get('entity_type');
      
      $values = $form_state->getValues();
     
       if (isset($values[$field_name]) && !empty($values[$field_name])) {
        $storage = \Drupal::service('entity_type.manager')->getStorage($entity_type);
        foreach ($values[$field_name] as $delta => $value) {
          if (is_numeric($delta)) {
            $target_id = isset($value['target_id']) && !empty($value['target_id']) ? $value['target_id'] : NULL;
            if ($target_id) {
              
              $referenced_entity = $storage->load($target_id);
              
              // Make sure referenced entity has the same field attached
              if (!$referenced_entity->hasField($field_name)) {
                $messages = self::checkFieldIntegrity($entity, $entity_type, $form_state->get('target_types'), $field_name, $delta);
                $message = isset($messages['errors']) && !empty($messages['errors'][$delta]) ? new TranslatableMarkup(self::MESSAGES['missing_field'], $messages['errors'][$delta]['message_data']) : 'An error occured';
                //$form_state->setErrorByName($field_name, $message);
                $form_state->setError($form[$field_name]['widget'][$delta], $message);
                return FALSE;
              }
              else {
                //if (!empty($referenced_entity->get($field_name)->getValue())) {
                  return $referenced_entity->get($field_name)->getValue();
               // }
              }
            }
          }
        }
      }
    }
  }

  public static function validateRelations(array &$form, FormStateInterface $form_state) {
    self::checkRelations($form, $form_state);   
  }

  public function submitRelations(array &$form, FormStateInterface $form_state) {
    $existing_values = self::checkRelations($form, $form_state);   
  }

  public function queryCorresponding($entity_type, $target_types, $field_name, $id = NULL) {
    // Query with entity_type.manager (The way to go)
    $query = \Drupal::service('entity_type.manager')->getStorage($entity_type);
    $query_result = $query->getQuery()->condition('status', 1);
    if (!empty($target_types)) {
      $query->condition('type', $target_types, 'IN');
    }
    if ($id) {
      $query_result->condition($field_name, $id);
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
}