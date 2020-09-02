<?php

namespace Drupal\nk_tools_cer\Plugin\Field;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;

class CorrespondingEntityReferenceFieldItemList extends EntityReferenceFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraint_manager = $this->getTypedDataManager()->getValidationConstraintManager(); 
    $constraints = parent::getConstraints();
    $constraints[] = $constraint_manager->create('ValidCorrespondingReference', []); 
    return $constraints;
  } 

  /**
   * {@inheritdoc}
   */
  /*
  public function defaultValuesForm(array &$form, FormStateInterface $form_state) {
    if (empty($this->getFieldDefinition()->getDefaultValueCallback())) {
      if ($widget = $this->defaultValueWidget($form_state)) {
        // Place the input in a separate place in the submitted values tree.
        $element = ['#parents' => ['default_value_input']];
        $element += $widget->form($this, $element, $form_state);

        return $element;
      }
      else {
        return ['#markup' => $this->t('No widget available for: %type.', ['%type' => $this->getFieldDefinition()->getType()])];
      }
    }
  }
  */

  /**
   * {@inheritdoc}
   */
  public static function processDefaultValue($default_value, FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {
    $default_value = parent::processDefaultValue($default_value, $entity, $definition);
    return $default_value;
  }

}