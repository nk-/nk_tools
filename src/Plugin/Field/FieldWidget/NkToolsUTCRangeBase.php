<?php

namespace Drupal\nk_tools\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeWidgetBase;

use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;

use Drupal\datetime_range\Plugin\Field\FieldWidget\DateRangeWidgetBase;

/**
 * Plugin implementation of the 'datetime_default' widget.
 *
 * @FieldWidget(
 *   id = "nk_tools_daterange_utc",
 *   label = @Translation("Daterange UTC"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class NkToolsUTCRangeBase extends DateRangeWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    return $element;
  }


 public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // The widget form element type has transformed the value to a
    // DrupalDateTime object at this point. We need to convert it back to the
    // storage timezone and format.

    $datetime_type = $this->getFieldSetting('datetime_type');
    if ($datetime_type === DateRangeItem::DATETIME_TYPE_DATE) {
      $storage_format = DateTimeItemInterface::DATE_STORAGE_FORMAT;
    }
    else {
      $storage_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
    }

    //$storage_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);
    $node = $form_state->getFormObject();
    $site_timezone = \Drupal::config('system.date')->get('timezone')['default']; // 'Europe/Paris' ATM
    $storage_timezone = $node && $node->getOperation() == 'edit' ? new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE) : new \DateTimeZone($site_timezone);
    $user_timezone = new \DateTimeZone(drupal_get_user_timezone());

    foreach ($values as &$item) {
      if (!empty($item['value']) && $item['value'] instanceof DrupalDateTime) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
        $start_date = $item['value'];

        if ($datetime_type === DateRangeItem::DATETIME_TYPE_ALLDAY) {
          // All day fields start at midnight on the starting date, but are
          // stored like datetime fields, so we need to adjust the time.
          // This function is called twice, so to prevent a double conversion
          // we need to explicitly set the timezone.
          $start_date->setTimeZone($user_timezone)->setTime(0, 0, 0);
        }

        // Adjust the date for storage.
        $item['value'] = $start_date->setTimezone($storage_timezone)->format($storage_format);
        
      }
      

      if (!empty($item['end_value']) && $item['end_value'] instanceof DrupalDateTime) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
        $end_date = $item['end_value'];

        if ($datetime_type === DateRangeItem::DATETIME_TYPE_ALLDAY) {
          // All day fields start at midnight on the starting date, but are
          // stored like datetime fields, so we need to adjust the time.
          // This function is called twice, so to prevent a double conversion
          // we need to explicitly set the timezone.
          $end_date->setTimeZone($user_timezone)->setTime(23, 59, 59);
        }

        // Adjust the date for storage.
        $item['end_value'] = $end_date->setTimezone($storage_timezone)->format($storage_format);
      }
    }

    return $values;
  }

}
