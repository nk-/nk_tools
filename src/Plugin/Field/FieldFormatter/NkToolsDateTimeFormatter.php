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
       
    $date_markup = [];
   
    if (!empty($dates)) { // Daterange field
     
      foreach ($dates as $index => $date) {

         if (isset($date['start_date']) && !empty($date['start_date']) && !empty($date['start_date']['#text'])) {
           $date_markup[] = $this->process($date);
         }
         else {
           if (isset($date['#theme']) && $date['#theme'] == 'time' && isset($date['#text']) && !empty($date['#text'])) { // Start and end date are exact the same (same time too)
             $format_type = $this->getSetting('format_type');
             $separator = isset($date['separator']) && isset($date['separator']['#plain_text']) ? $date['separator']['#plain_text'] : $this->getSetting('separator');
             $date_markup = $date;
             $date_string = $format_type == 'html_datetime' ?  'j M Y ' . $separator . ' H:i\h' :  'j M Y';
             $date_markup['#text'] =  $this->parseDate($date['#text'], $date_string);
           }
           else {
             if (is_array($date)) {
             }
           }
         }
      }
      return $date_markup; 
    }

    // Datetime field
    else {
     
      if (!empty($items)) {
        
        $format_type = $this->getSetting('format_type');
        $separator = isset($date['separator']) && isset($date['separator']['#plain_text']) ? $date['separator']['#plain_text'] : $this->getSetting('separator');

        $date_markup_array = [
          '#theme' => 'time',
          '#text' => NULL,
          '#attributes' => [
            'datetime' => NULL
          ],
          '#cache' => [
            'contexts' => ['timezone']
          ]
        ];
        
        foreach ($items as $index => $item) {
          $value = $item->get('value')->getDateTime();
          $date_markup[$index] = $date_markup_array;

          $date_string = $format_type == 'html_datetime' ? 'j M Y ' . $separator .' H:i\h' : 'j M Y';

          $date_markup[$index]['#text'] = $this->parseDate($value, $date_string);
          $date_markup[$index]['#attributes']['datetime'] = $value;
        }
      }
      return $date_markup;
    } 
  }

  protected function parseDate(string $datestring, string $format = '') {
    $timezone_string = $this->getSetting('timezone_override') ? $this->getSetting('timezone_override') : \Drupal::config('system.date')->get('timezone')['default'];
    $timezone = new \DateTimeZone($timezone_string);
    $date_object = new DrupalDateTime($datestring, $timezone); //DrupalDateTime::createFromTimestamp(time(), $timezone); // 
    return !empty($format) ? $date_object->setTimeZone($timezone)->format($format) : $date_object;
  }

  protected function process(array $date) {
 
    $date_markup = [];
   
    $format_type = $this->getSetting('format_type');
    $separator = isset($date['separator']) && isset($date['separator']['#plain_text']) ? $date['separator']['#plain_text'] : $this->getSetting('separator');
    $timezone_string = $this->getSetting('timezone_override') ? $this->getSetting('timezone_override') : \Drupal::config('system.date')->get('timezone')['default'];

    $start_date_object = $this->parseDate($date['start_date']['#text']);

    if ($start_date_object) {

      $start_datetime = $format_type == 'html_datetime' ? $this->parseDate($date['start_date']['#text'], 'j M Y H:i\h') : $this->parseDate($date['start_date']['#text'], 'j M');

      $start_year = $this->parseDate($date['start_date']['#text'], 'Y');
      $start_date = $this->parseDate($date['start_date']['#text'], 'j M Y');
      $start_time = $this->parseDate($date['start_date']['#text'], 'H:i');

      if (isset($date['end_date']) && !empty($date['end_date']) && !empty($date['end_date']['#text'])) {
            
        $end_date_object = $this->parseDate($date['end_date']['#text']);
        
        if ($end_date_object) {
                
          $end_datetime = $format_type == 'html_datetime' ? $this->parseDate($date['end_date']['#text'], 'j M Y H:i\h') : $this->parseDate($date['end_date']['#text'], 'j M Y');  

          $end_year = $this->parseDate($date['end_date']['#text'], 'Y');
          $end_date = $this->parseDate($date['end_date']['#text'], 'j M Y');
          $end_time = $this->parseDate($date['end_date']['#text'], 'H:i');

          if ($end_date == $start_date) { // Same date
            $end_string = $format_type == 'html_datetime' ? $this->parseDate($date['end_date']['#text'], 'g:i A') : NULL;
            //'H:i\h'); // 'g:i A');
          }
          // Different dates
          else {
            $end_string = $end_datetime;
          }
         
          $date_markup = $date['end_date'];
          if ($start_year == $end_year) {
            //$start_year_string = $format_type == 'html_datetime' ? $this->parseDate($date['start_date']['#text'], 'j M H:i') : $this->parseDate($date['start_date']['#text'], 'j M');
            $date_markup['#text'] =  $end_string ? $start_datetime . ' ' . $separator . ' ' . $end_string : $start_datetime;
          }
          else {
            $date_markup['#text'] =  $end_string ? $start_datetime . ' ' . $separator . ' ' . $end_string : $start_datetime;
          }
        }
        else {
          $date_markup = $date['start_date'];
          $date_markup['#text'] = $format_type == 'html_datetime' ? $this->parseDate($date['start_date']['#text'], 'j M Y H:i\h') : $this->parseDate($date['start_date']['#text'], 'j M Y');
        } 
      }
      else {
        $date_markup = $date['start_date'];
        $date_markup['#text'] = $format_type == 'html_datetime' ? $this->parseDate($date['start_date']['#text'], 'j M Y H:i\h') : $this->parseDate($date['start_date']['#text'], 'j M Y');
      } 
    }
    // Same dates and same times
    else {
      if (isset($date['#theme']) && $date['#theme'] == 'time' && isset($date['#text']) && !empty($date['#text'])) {
        $date_markup = $date;
        $date_markup['#text'] = $format_type == 'html_datetime' ? $this->parseDate($date['#text'], 'j M Y H:i\h') : $this->parseDate($date['#text'], 'j M Y');
      }
    }
  
    return $date_markup;
  }

}