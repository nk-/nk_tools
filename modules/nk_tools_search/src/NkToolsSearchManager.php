<?php

namespace Drupal\nk_tools_search;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\facets\FacetInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implement some custom search.
 */
class NkToolsSearchManager implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
 // protected $account;

  /**
   * The toolbar menu elements entities.
   *
   * @var \Drupal\toolbar_menu\Entity\ToolbarMenuElement[]
   */
 // protected $toolbarMenuElements;

  /**
   * Construct a new ToolbarMenu.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The account service.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) { //, AccountProxyInterface $account) {
    $this->entityManager = $entity_manager;
   // $this->account = $account->getAccount();
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      //$container->get('stream_wrapper_manager'),
      $container->get('entity_type.manager')
    );
  }


  public function facetsResultItem(array &$variables) {
  
    if (isset($variables['facet']) && $variables['facet'] instanceof FacetInterface) {
      switch ($variables['facet']->id()) {
      
        case 'issues':
        case 'elastic_issues':
          if (isset($variables['value']) && $variables['value'] > 0) {
            $node = Node::load($variables['value']);
            if ($node instanceof Node) {
              $variables['value'] = $node->getTitle(); 
            }
          }
         break;
 
         case 'dates':
           //$variables['value'] = \Drupal::service('date.formatter')->format($variables['value'], 'custom', 'Y');
         break;
      }
    }
  }

  public function facetsItemList(array &$variables) { 
  
    if (isset($variables['facet']) && $variables['facet'] instanceof FacetInterface) {
    
      switch ($variables['facet']->id()) {

        case 'dates':
          $items = [];
          $default_timezone = \Drupal::config('system.date')->get('timezone');
          $site_timezone = isset($default_timezone['default']) && !empty($default_timezone['default']) ? $default_timezone['default'] : 'UTC'; // 'Europe/Paris' ATM

          foreach ($variables['items'] as $index => $item) {
            if (isset($item['#title']) && isset($item['#title']['#raw_value']) && !empty($item['#title']['#raw_value'])) {

              if ($item['#title']['#raw_value'] == 'reset_all') {
                $now = new DrupalDateTime('now', $site_timezone);
                $items[$now->getTimestamp()] = ['item' => $item]; 
              }
              else {

                if (strpos($item['#title']['#raw_value'], 'T') !== FALSE) {
                  // A weird case where would have value like this (double): 2019-10-09T00:00:00 2019-10-09
                  $raw_value = strlen($item['#title']['#raw_value']) < 20 ? $item['#title']['#raw_value'] : substr($item['#title']['#raw_value'], 0 , 19);
                }
                // Date only, no time present
                else {
                  $raw_value = $item['#title']['#raw_value'] .'T00:00:00';
                }

             
                $date_chunks = explode('-', $raw_value);
                array_pop($date_chunks);
                $datestring = implode('-', $date_chunks) . '-01';
             
                $timezone = new \DateTimeZone($site_timezone);
                $date = new \DateTime($datestring,  $timezone);
                if (is_object($date)) {

                  $year_datestring = $date_chunks[0] .'-01-01';
                  //$month_datestring = $datestring;

                  $year_date = new \DateTime($datestring,  $timezone);
                  if (is_object($year_date)) {
                    // $year_timestamp = $year_date->getTimestamp();
                    //$items[$year_timestamp] = []; //['item' => $item, 'datestring' => $month_year];  
                    $month_year_timestamp = $date->getTimestamp(); 
                    $month_year_string = \Drupal::service('date.formatter')->format($month_year_timestamp, 'custom', 'F'); //'F Y'
                    $items[$date_chunks[0]][$date_chunks[1]] = [
                      'year' => $date_chunks[0],
                      'month' => $date_chunks[1],
                      'month_year' => $month_year_string,
                      'item' => $item
                    ];
                  }

                // $timestamp = $date->getTimestamp();
               // $month_year = \Drupal::service('date.formatter')->format($timestamp, 'custom', 'F Y');
               // $items[$timestamp] = ['item' => $item, 'datestring' => $month_year];
                }
              }  
            }
          } 
  
          if (!empty($items)) {

            krsort($items);
      
            //facets_preprocess_facets_item_list($variables);
            $variables['items'] = [];
            $i = 0;
            foreach ($items as $year => $months) {
             krsort($months);
             $variables['items'][$year] = [];
             foreach ($months as $month => $date_link) {
               if (isset($date_link['item']['#url']) && isset($date_link['month_year']) && !empty($date_link['month_year'])) {
                 $variables['items'][$year][$month] = $date_link['item'];
                 $variables['items'][$year][$month]['#title']['count'] = 100;
                 $variables['items'][$year][$month]['#title']['#value'] = $date_link['month_year'];
                 $variables['items'][$year][$month]['#title']['#raw_value'] = $date_link['month_year'];
                 //$variables['items'][$i] = $date_link['item'];
               }     
             } 
             $i++; 
          }
           
        }

      break;

    }
     
    }

  }

}

