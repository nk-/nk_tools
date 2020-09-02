<?php

namespace Drupal\nk_tools_cer\Plugin\Validation;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Field\FieldItemListInterface;

use Drupal\nk_tools_cer\Plugin\Validation\Constraint\ValidCorrespondingReference;

trait ValidCorrespondingReferenceTrait {

  use StringTranslationTrait;

  public function setMessage($message_type, $message, array $message_data = []) {  
    return [
      'prefix' => '<div class="messages messages--' . $message_type .'">',
      'suffix' => '</div>',
      'message' => $message instanceof TranslatableMarkup ? $message : $this->t($message),
      'message_type' => $message_type,
      'message_data' => $message_data
    ];
  }

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


  public static function compileMessage(array &$missing, EntityInterface $referenced_entity, EntityTypeManagerInterface $entityTypeManager, $index = 0, array &$message_data) {
  
    $referenced_entity_label = $referenced_entity->label(); 
    $referenced_entity_type = $referenced_entity->getType();
    $referenced_entity_type_label = $entityTypeManager->getStorage('node_type')->load($referenced_entity_type)->label();
    $prefix = '';
   
    $missing['type'] = $prefix . $referenced_entity_type;
    $missing['label'] = $prefix . $referenced_entity_type_label . ' (' . $referenced_entity_type .')';  
    $missing['title'] = $prefix . $referenced_entity->getTitle();
   
  }

  public function integrity(FieldItemListInterface $value, EntityInterface $entity, EntityTypeManagerInterface $entityTypeManager, $warning = NULL) {

    $handler_settings = $value[0]->getFieldDefinition()->getSetting('handler_settings');

    $entity_type = $value[0]->defaultStorageSettings()['target_type'];
    $storage = $entityTypeManager->getStorage($entity_type);
    
    $field_name = $value[0]->getFieldDefinition()->getName();
    $field_label = $value[0]->getFieldDefinition()->getLabel() . ' (' . $field_name  . ')';
     
    $target_types = isset($handler_settings['target_bundles']) ? array_keys($handler_settings['target_bundles']) : [];
  
    $self_type = $entity->getType();
    $self_type_label = $entityTypeManager->getStorage('node_type')->load($self_type)->label() . ' (' . $self_type .')';
         
    $message_data = [
      '@self_bundle' => $self_type,
      '@self_label' => \Drupal::service('entity_type.manager')->getStorage('node_type')->load($self_type)->label(), 
    ];

    if ($warning && !empty($target_types)) {
      
      $constraint = new ValidCorrespondingReference();
      $i = 0;
      $missing_labels = NULL;
      $missing_types = NULL;
      $valid_bundles_labels = NULL;
      $valid_bundles_types = NULL; 
      $messages = [];
      
      foreach ($target_types as $index => $target_type) {

        $target_entity_fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $target_type);
        $label = \Drupal::service('entity_type.manager')->getStorage('node_type')->load($target_type)->label();

        if ($i == 0) {
          $message_data['@url'] = '/admin/structure/types/manage/' . $target_type . '/fields/add-field';
          $message_data['@first_label'] = $label;
          $message_data['@first_type'] = $target_type;
          $prefix = '';
        }
        else {
          $prefix =  ', '; 
        }

        // Make sure referenced entity has the same field attached
        if (!isset($target_entity_fields[$field_name])) {
          $missing_labels .=  $prefix . $label . ' (' . $target_type .')'; 
          $missing_types .= $prefix . $target_type;
          $i++;
        }
        else {
          $valid_bundles_labels .= $prefix . $label . ' (' . $target_type .')';
          $valid_bundles_types .= $prefix . $target_type;
        }
      }

      if ($missing_labels && $missing_types) {
        $message_data['@types'] = $missing_types;
        $message_data['@labels'] = $missing_labels;
        $message = $this->t($constraint->invalidDestinationFieldWarning, $message_data);
        $messages['errors'] = $this->setMessage('warning', $message, $message_data);
      }

      if ($valid_bundles_labels && $valid_bundles_types) {
        $status_message_data = $message_data;
        $status_message_data['@types'] = $valid_bundles_types;
        $status_message_data['@labels'] = $valid_bundles_labels;
        $constraint = new ValidCorrespondingReference();
        $status_message = $this->t($constraint->valid_field, $status_message_data);
        $messages['status'] = $this->setMessage('status', $status_message, $status_message_data);
      }

      return $messages;
      
    }
    else {
      
      $i = 0;
      $labels = NULL; 
      $missing_labels = NULL;
      $missing_types = NULL;;
      $missing = [
        'types' => NULL,
        'labels' => NULL,
        'titles' => NULL, 
      ];  
      
      foreach ($value as $delta => $item) {
        $target_id = $item->target_id;
        if ($target_id) {
          $message_data['delta'] = $delta;
          $referenced_entity = $storage->load($target_id);
          $this->compileMessage($missing, $referenced_entity, $entityTypeManager, $i, $message_data);  
        }
      }

      if (!empty($missing['types']) && !empty($missing['labels']) && !empty($missing['titles'])) { 
        $message_data['@label'] = $missing['titles'];
        $message_data['@types'] = $missing['types'];
        $message_data['@labels'] = $missing['labels'];
        return $message_data;
      }
    
    }

    return FALSE; 

  }

}