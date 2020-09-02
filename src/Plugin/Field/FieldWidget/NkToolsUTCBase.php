<?php

namespace Drupal\nk_tools\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeWidgetBase;

use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;

use Drupal\datetime_range\Plugin\Field\FieldWidget\DateRangeWidgetBase;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'datetime_default' widget.
 *
 * @FieldWidget(
 *   id = "nk_tools_datetime_utc",
 *   label = @Translation("Date UTC"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class NkToolsUTCBase extends DateTimeWidgetBase implements ContainerFactoryPluginInterface {

   /**
   * The date format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityStorageInterface $date_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->dateStorage = $date_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity.manager')->getStorage('date_format')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // If the field is date-only, make sure the title is displayed. Otherwise,
    // wrap everything in a fieldset, and the title will be shown in the legend.
    if ($this->getFieldSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATE) {
      $element['value']['#title'] = $this->fieldDefinition->getLabel();
      $element['value']['#description'] = $this->fieldDefinition->getDescription();
    }
    else {
      $element['#theme_wrappers'][] = 'fieldset';
    }

    // Identify the type of date and time elements to use.
    switch ($this->getFieldSetting('datetime_type')) {
      case DateTimeItem::DATETIME_TYPE_DATE:
        $date_type = 'date';
        $time_type = 'none';
        $date_format = $this->dateStorage->load('html_date')->getPattern();
        $time_format = '';
        break;

      default:
        $date_type = 'date';
        $time_type = 'time';
        $date_format = $this->dateStorage->load('html_date')->getPattern();
        $time_format = $this->dateStorage->load('html_time')->getPattern();
        break;
    }

    $element['value'] += [
      '#date_date_format' => $date_format,
      '#date_date_element' => $date_type,
      '#date_date_callbacks' => [],
      '#date_time_format' => $time_format,
      '#date_time_element' => $time_type,
      '#date_time_callbacks' => [],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // The widget form element type has transformed the value to a
    // DrupalDateTime object at this point. We need to convert it back to the
    // storage timezone and format.

    $datetime_type = $this->getFieldSetting('datetime_type');
    if ($datetime_type === DateTimeItem::DATETIME_TYPE_DATE) {
      $storage_format = DateTimeItemInterface::DATE_STORAGE_FORMAT;
    }
    else {
      $storage_format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
    }

    $site_timezone = \Drupal::config('system.date')->get('timezone')['default'];
    $node = $form_state->getFormObject();
    $storage_timezone = $node && $node->getOperation() == 'edit' ? new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE) : new \DateTimeZone($site_timezone);
    //$storage_timezone = new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE);

    foreach ($values as &$item) {
      if (!empty($item['value']) && $item['value'] instanceof DrupalDateTime) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $date */
        $date = $item['value'];

        // Adjust the date for storage.
        $item['value'] = $date->setTimezone($storage_timezone)->format($storage_format);
      }
    }
    return $values;
  }

  /**
   * Creates a date object for use as a default value.
   *
   * This will take a default value, apply the proper timezone for display in
   * a widget, and set the default time for date-only fields.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   The UTC default date.
   * @param string $timezone
   *   The timezone to apply.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   A date object for use as a default value in a field widget.
   */
   protected function createDefaultValue($date, $timezone) {
     // The date was created and verified during field_load(), so it is safe to
     // use without further inspection.
     if ($this->getFieldSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATE) {
       $date->setDefaultDateTime();
     }
     $date->setTimezone(new \DateTimeZone($timezone));
     return $date;
   }

}
