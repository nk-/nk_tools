<?php

namespace Drupal\nk_tools\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Element;

use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeFormatterBase;
use Drupal\datetime_range\Plugin\Field\FieldFormatter\DateRangeDefaultFormatter;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Plugin implementation of the 'Custom Start-End' formatter for 'datetime_range' fields.
 *
 * @FieldFormatter(
 *   id = "nk_tools_datetime_range",
 *   label = @Translation("Custom Start-End"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class NkToolsDateTimeFormatter extends DateRangeDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'separator' => '—',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function formatDate($date) {
    $format_type = $this->getSetting('format_type');
    $timezone = $this->getSetting('timezone_override') ?: $date->getTimezone()->getName();
    $date_format = $this->dateFormatter->format($date->getTimestamp(), 'custom', DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $timezone != '' ? $timezone : NULL);
    return $date_format;
  }
  
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    
    $dates = parent::viewElements($items, $langcode);
    
    if (!empty($dates)) {

      $date_markup = [
        '#theme' => 'time',
        '#text' => '',
        '#attributes' => [
          'datetime' => ''
        ],
        '#cache' => [
          'contexts' => ['timezone']
        ]
      ];

      $timezone_string = $this->getSetting('timezone_override') ? $this->getSetting('timezone_override') : \Drupal::config('system.date')->get('timezone')['default'];
      $format_type = $this->getSetting('format_type');
      $separator = $this->getSetting('separator');

      foreach ($dates as $delta => $date) {

        if (isset($date['start_date']) && !empty($date['start_date']) && !empty($date['start_date']['#text'])) {

          $start_date_object = $this->parseDate($date['start_date']['#text']);

          if ($start_date_object) {

            $start_date = $this->parseDate($date['start_date']['#text'], 'j M Y');

            $start_time = $this->parseDate($date['start_date']['#text'], 'H:i'); 

            //$start_string = $this->dateFormatter->format($start_date_object->getTimestamp(), $format_type, '', $timezone_string != '' ? $timezone_string : NULL);
            $start_string = $this->dateFormatter->format($start_date_object->getTimestamp(), 'custom', 'l, n F Y g:i A', $timezone_string != '' ? $timezone_string : NULL);

            if (isset($date['end_date']) && !empty($date['end_date']) && !empty($date['end_date']['#text'])) {
            
              $end_date_object = $this->parseDate($date['end_date']['#text']);

              if ($end_date_object) {
                $end_date = $this->parseDate($date['end_date']['#text'], 'j M Y');  
                $end_time = $this->parseDate($date['end_date']['#text'], 'H:i');
 
                if ($end_date == $start_date) {
                  $end_string = $end_time == $start_time ? NULL : $this->dateFormatter->format($end_date_object->getTimestamp(), 'custom', 'g:i A', $timezone_string != '' ? $timezone_string : NULL);
                }
                else {
                  $end_string = $this->dateFormatter->format($end_date_object->getTimestamp(), 'custom', 'l, n F Y g:i A', $timezone_string != '' ? $timezone_string : NULL);     
                }
  
                $date_markup['#text'] =  $end_string ? $start_string . ' ' . $separator . ' ' . $end_string : $start_string;
                $date_markup['#attributes']['datetime'] = $this->dateFormatter->format($start_date_object->getTimestamp(), 'custom', DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $timezone_string != '' ? $timezone_string : NULL); 
              }
            }
          }
        }
        else {
          if (isset($date['#theme']) && $date['#theme'] == 'time' && isset($date['#text']) && !empty($date['#text'])) {
            $single_date = $this->parseDate($date['#text']);
            $date_markup['#text'] = $this->dateFormatter->format($single_date->getTimestamp(), 'custom', 'j M Y', $timezone_string != '' ? $timezone_string : NULL);
          }
        }
      }
      
      return $date_markup; 
    }
    else {
      return [];
    } 
  }

  public function parseDate(string $datestring, string $format = '') {
    $timezone_string = $this->getSetting('timezone_override') ? $this->getSetting('timezone_override') : \Drupal::config('system.date')->get('timezone')['default'];
    $timezone = new \DateTimeZone($timezone_string);
    $date_object = new DrupalDateTime($datestring, $timezone); //DrupalDateTime::createFromTimestamp(time(), $timezone); // 
    return !empty($format) ? $date_object->setTimeZone($timezone)->format($format) : $date_object;
  }

}