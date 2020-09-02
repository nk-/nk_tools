<?php

namespace Drupal\nk_tools_cer\Plugin\EntityReferenceSelection;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Component\Utility\Html;
use Drupal\node\NodeInterface;
use Drupal\node\Plugin\EntityReferenceSelection\NodeSelection;

use Drupal\nk_tools_cer\Plugin\Validation\ValidCorrespondingReferenceTrait;

/**
 * Provides specific access control for the node entity type.
 *
 * @EntityReferenceSelection(
 *   id = "corresponding_entity_reference_selection",
 *   label = @Translation("Node selection"),
 *   entity_types = {"node"},
 *   group = "default",
 *   weight = 1
 * )
 */
class CorrespondingReferenceSelection extends NodeSelection {

  use ValidCorrespondingReferenceTrait;

  /**
   * {@inheritdoc}
   */
  /*
  public function defaultConfiguration() {
    return [
      'field_name' => NULL
    ] + parent::defaultConfiguration();
  }
  */

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    // Adding the 'node_access' tag is sadly insufficient for nodes: core
    // requires us to also know about the concept of 'published' and
    // 'unpublished'. We need to do that as long as there are no access control
    // modules in use on the site. As long as one access control module is there,
    // it is supposed to handle this check.
    //if (!$this->currentUser->hasPermission('bypass node access') && !count($this->moduleHandler->getImplementations('node_grants'))) {
    //  $query->condition('status', NodeInterface::PUBLISHED);
    //}
    return $query;
  }

  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {

    $target_type = $this->getConfiguration()['target_type'];

    $query = $this->buildEntityQuery($match, $match_operator);
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    $options = [];
    $entities = $this->entityTypeManager->getStorage($target_type)->loadMultiple($result); 
 
    $parent_entity = $this->getConfiguration()['entity'];
    $parent_entity_fields = $this->entityFieldManager->getFieldDefinitions($parent_entity->getEntityType()->id(), $parent_entity->getType());
    $field_name = NULL;
    //$allowed_types = ['entity_reference', 'corresponding_entity_reference'];
    foreach ($parent_entity_fields as $parent_entity_field_name => $parent_entity_field) {
      if ($parent_entity_field->getFieldStorageDefinition()->getType() == 'corresponding_entity_reference') {
        $field_name = $parent_entity_field->getFieldStorageDefinition()->getName();
      }
    }
   
    //$field_values = $field_name ? $parent_entity->get($field_name)->getValue() : [];
    if ($field_name) {
      //$form =  $this->entityTypeManager->getFormObject('node', 'default')->setEntity($parent_entity);
      $form = \Drupal::service('entity.form_builder')->getForm($parent_entity);
      $corresponding = !empty($form[$field_name]['widget']) && isset($form[$field_name]['widget'][0]['corresponding']) ? $form[$field_name]['widget'][0]['corresponding']['#default_value'] : NULL;
     // \Drupal::logger('Fvalues')->notice('<pre>' . print_r($corresponding, 1) . '<pre>');
    }

    foreach ($entities as $entity_id => $entity) {
      $bundle = $entity->bundle();
      $options[$bundle][$entity_id] = Html::escape($this->entityRepository->getTranslationFromContext($entity)->label());
    }
  
    return $options;
  }
  /**
   * {@inheritdoc}
   */
  public function validateReferenceableNewEntities(array $entities) { 
    $entities = parent::validateReferenceableNewEntities($entities);
    // Mirror the conditions checked in buildEntityQuery().
    //if (!$this->currentUser->hasPermission('bypass node access') && !count($this->moduleHandler->getImplementations('node_grants'))) {
    //  $entities = array_filter($entities, function ($node) {
    //    return $node->isPublished();
    //  });
   // }
    return $entities;
  }

}
