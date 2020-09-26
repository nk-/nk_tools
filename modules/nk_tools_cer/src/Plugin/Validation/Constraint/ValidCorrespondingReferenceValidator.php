<?php

namespace Drupal\nk_tools_cer\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\ValidReferenceConstraintValidator; 

use Drupal\nk_tools_cer\Plugin\Validation\ValidCorrespondingReferenceTrait;

/**
 * Validates the ValidCorrespondingReferenceConstraint constraint.
 */
class ValidCorrespondingReferenceValidator extends ValidReferenceConstraintValidator {

  use ValidCorrespondingReferenceTrait;

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {

    parent::validate($value, $constraint);

    if (!isset($value) || !isset($value[0])) {
      return;
    }

    /*
    $entity = !empty($value->getParent()) ? $value->getEntity() : NULL;
    $entityTypeManager = $this->entityTypeManager;

    if ($errors = $this->integrity($value, $entity, $entityTypeManager)) {
      if (is_array($errors) && !empty($errors)) {
        $invalid = $this->context->buildViolation($constraint->invalidDestinationFieldError);
        if ($invalid) {
        $delta = 0;
        foreach ($errors as $token => $string) {
          if ($token != 'delta') {
            $invalid->setParameter($token, $string);
          }
          else {
            $delta = $string;
          } 
        }
        $invalid->atPath((string) $delta . '.entity');
        $invalid->setInvalidValue($entity);
        $invalid->addViolation();
        }
      }
    }
    */
  }
}