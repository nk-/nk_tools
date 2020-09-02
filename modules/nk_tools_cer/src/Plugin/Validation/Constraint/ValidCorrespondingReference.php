<?php

namespace Drupal\nk_tools_cer\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\ValidReferenceConstraint;

/**
 * Corresponding Entity Reference valid reference constraint.
 *
 * Verifies that referenced entities are valid.
 *
 * @Constraint(
 *   id = "ValidCorrespondingReference",
 *   label = @Translation("Corresponding Entity Reference valid reference", context = "Validation")
 * )
 */
class ValidCorrespondingReference extends ValidReferenceConstraint {

  /**
   * The default violation message.
   *
   * @var string
   */
   public $invalidDestinationFields =  'Referenced content <em>@label</em> belongs to a content type <em>@type_label</em> that does not have <em>@field_label</em> field assigned to it so operating two way is not possible.';
  

  public $invalidDestinationFieldError = 'Referenced content <em>@label</em> belongs to a content type(s) <em>@labels</em> that does not have <em>@field_label</em> field assigned to it so saving two way is not possible. Remove incorrect value from field below and then save this form again. Then, for example, for <em>@first_label</em> content type you could create <em>@field_label</em> by choosing it from <em>Re-use an existing field</em> list on <a href="@url" target="blank_">this page</a>. On the second configuration step you should enable <em>@self_label</em> as Referenced Content type.';

  /**
   * Violation warning message.
   *
   * @var string
   */
  public $invalidDestinationFieldWarning = 'Two way operations are disabled for the following destination bundles: <em>@labels</em> - which do not have this field assigned. For example you can create one for <em>@first_label</em> content type by choosing it from <em>Re-use an existing field</em> list on <a href="@url" target="blank_">this page</a>. On the second configuration step you should enable <em>@self_label</em> as Referenced Content type, in case you are opting for default autocomplete callback and not one provided by Views as possible for this plugin too. Then repeat for all the others as per your preference.';

  
  /**
   * Valid field message.
   *
   * @var string
   */
  public $valid_field = 'Two way operations enabled for the following destination bundles: <em>@labels</em>';
}