<?php

namespace Drupal\nk_tools_search\Plugin\facets\processor;

use Drupal\facets\FacetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Drupal\facets\Plugin\facets\processor\DateItemProcessor;

use Drupal\facets\Plugin\facets\query_type\SearchApiDate;

/**
 * Provides a processor for for various date fields aggregated.
 *
 * @FacetsProcessor(
 *   id = "nk_tools_search_date_item",
 *   label = @Translation("Nk toools Date"),
 *   description = @Translation("A custom processor for various date fields aggregated"),
 *   stages = {
 *     "build" = 35
 *   }
 * )
 */
class NkToolsSearchDateItemProcessor extends DateItemProcessor implements BuildProcessorInterface { //DateItemProcessor { 

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
  
    // Assign our dates grouped by year][month
    if (!empty($results)) {
      $facet->sortedItems = $this->parse($facet, $results);
    }
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $build = parent::buildConfigurationForm($form, $form_state, $facet);
    //$this->getConfiguration();
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryType() {
    return 'string';
  }
 

  /**
   * {@inheritdoc}
   */
/*
  public function defaultConfiguration() {
    return [
      'date_display' => 'actual_date',
      'granularity' => SearchApiDate::FACETAPI_DATE_MONTH,
      'date_format' => '',
    ];
  }
*/


  protected function parse(FacetInterface $facet, array $results) {
  
    $items = [];
    $default_timezone = \Drupal::config('system.date')->get('timezone');
    $site_timezone = isset($default_timezone['default']) && !empty($default_timezone['default']) ? $default_timezone['default'] : 'UTC'; // 'Europe/Paris' ATM
    $date_format = isset($this->getConfiguration()['date_format']) && !empty($this->getConfiguration()['date_format']) ? $this->getConfiguration()['date_format'] : 'D j';
    
    foreach ($results as $index => &$result) {
      
      $raw_value = $result->getRawValue();

      if (!empty($raw_value)) {  

       
        $timezone = new \DateTimeZone($site_timezone);
 
        if (!is_numeric($raw_value)) { 

          if (strpos($raw_value, 'T') !== FALSE) {
            // A weird case where would have value like this (double): 2019-10-09T00:00:00 2019-10-09
            $new_value = strlen($raw_value) < 20 ? $raw_value : substr($raw_value, 0 , 19);
            //$new_value = strlen($value) < 20 ? $value : substr($value, 0 , 19); 
            $explode = explode('T', $string_value);
            $string_value = (string)$explode[0];
          }
          // Date only, no time present
          else {
            $string_value = $raw_value; // .'T00:00:00';
          }
          $raw_value_date = new \DateTime($string_value,  $timezone);
          $raw_value = $raw_value_date->getTimestamp();         
        }
        
        //$value = \Drupal::service('date.formatter')->format($raw_value, 'custom', DateTimeItemInterface::DATETIME_STORAGE_FORMAT, 'UTC');


        /*
        $date_chunks = explode('-', $value);
        array_pop($date_chunks);
        $datestring = implode('-', $date_chunks) . '-01';
             
        
        $date = new \DateTime($datestring,  $timezone);
        if (is_object($date)) {
        
          $year_datestring = $date_chunks[0] .'-01-01';
          $year_date = new \DateTime($datestring,  $timezone);
          if (is_object($year_date)) {
            $month_year_timestamp = $date->getTimestamp(); 
            
          */
           // $date = DrupalDateTime::createFromTimestamp((int)$raw_value, $timezone); // 
           $year_string = \Drupal::service('date.formatter')->format($raw_value, 'custom', 'Y', $site_timezone);
           $month_year_string = \Drupal::service('date.formatter')->format($raw_value, 'custom', $date_format, $site_timezone);
           $month_string = \Drupal::service('date.formatter')->format($raw_value, 'custom', 'M', $site_timezone);
           $month_number = \Drupal::service('date.formatter')->format($raw_value, 'custom', 'm', $site_timezone);
           $month_day_string = \Drupal::service('date.formatter')->format($raw_value, 'custom', 'd F', $site_timezone);
           $day_string = \Drupal::service('date.formatter')->format($raw_value, 'custom', 'd', $site_timezone);
           
           $format = \Drupal::service('date.formatter')->format($raw_value, 'custom', $date_format, $site_timezone);

           // Essential, set display value to each item in Facets results array  
           $result->setDisplayValue($format); //$month_year_string);

           $items[$raw_value] = [
           //$items[$date_chunks[0]][$date_chunks[1]] = [
              'year' => $year_string,
              'month_number' => $month_number,
              'month' => $month_string,
              'month_year' => $month_year_string,
              'day' => $day_string,
              'format' => $format,
              'count' => $result->getCount(),
              'item' => $result
            ];
        //  }
        //}
      }
      else {
        unset($results[$index]);
      }
  
    }
    
    if (!empty($items)) {
      krsort($items);
      //foreach ($items as $year => $months) {
      // krsort($months);
      // $items[$year] = $months;
     // }
    }

    return $items; 
  }

}
