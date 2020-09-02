<?php

namespace Drupal\nk_tools_cer\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * @FieldFormatter(
 *   id = "corresponding_entity_reference_view",
 *   label = @Translation("Entity label"),
 *   description = @Translation("Display the referenced entities’ label with info about corresponding parents."),
 *   field_types = {
 *     "entity_reference",
       "corresponding_entity_reference"
 *   }
 * )
 */
class CorrespondingEntityReferenceFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $values = $items->getValue();

    foreach ($elements as $delta => $entity) {
      $elements[$delta]['#suffix'] = ' x' . $values[$delta]['corresponding'];
    }

    return $elements;
  }
}