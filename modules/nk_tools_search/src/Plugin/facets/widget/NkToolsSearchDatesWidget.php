<?php

namespace Drupal\nk_tools_search\Plugin\facets\widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Template\Attribute;

use Drupal\facets\FacetInterface;
use Drupal\facets\Result\Result;
// use Drupal\facets\Widget\WidgetPluginBase;
use Drupal\facets\Plugin\facets\widget\LinksWidget;


/**
 * The links widget.
 *
 * @FacetsWidget(
 *   id = "nk_tools_search_dates",
 *   label = @Translation("Nk tools dates"),
 *   description = @Translation("A custom widget for various date fields aggregated"),
 * )
 */
class NkToolsSearchDatesWidget extends LinksWidget {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'nk_tools_search_date_icon' => 'chevron_right',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {

    $build = parent::build($facet);
  
    $items = [];
    $build['#items'] = [];
    $count = isset($this->getConfiguration()['show_numbers']) && $this->getConfiguration()['show_numbers'];
    $icon = isset($this->getConfiguration()['nk_tools_search_date_icon']) && !empty($this->getConfiguration()['nk_tools_search_date_icon']) ? $this->getConfiguration()['nk_tools_search_date_icon'] : NULL;

    //$year = '2019';
    $total = 0;
    $date = [];
    foreach ($facet->sortedItems as $timestamp => $dates) {
      $date[$dates['year']][$dates['month_number']][$dates['day']] = $dates;
    }
    
    foreach ($date as $year_key => $data) {
      
      $i = 0;

      $build['#items'][$year_key] = [
        'target' => 'pane-' . $year_key,
        'label' => $year_key,
        'content' => [
          '#theme' => 'nk_tools_search_collapsible_icon',
          '#items' => []
        ],
      ];
  
      $days = [];

      foreach ($data as $month_key => $month) {
        
        $days[$month_key] = [
          '#theme' => 'links', 
          '#set_active_class' => TRUE,
          '#attributes' => [
            'data-list' => 'nk_tools_search_facets_dates',
            'class' => [
              'nk-tools-facets-dates',
            ]
          ],
          '#links' => [],
        ];

        foreach ($month as $day_key => $day) {
         
          $days[$month_key]['#links'][$day_key] = [
            'url' => $day['item']->getUrl(),
            'title' => $day['item']->getDisplayValue(),
            'attributes' => [
              'data-count' => $day['count'],
              'data-drupal-facet-item-id' => $day['item']->getFacet()->id(),
              'data-drupal-facet-item-value' => $day['item']->getRawValue(),
              'class' => [
                'nk-tools-date-facet-link'
              ] 
            ],
          ];

          $build['#items'][$year_key]['content']['#items'][$month_key] = [
            'target' => 'pane-' . $year_key . '-' . $month_key,
            'label' =>  $day['month'],
            'content' => $days[$month_key],
          ];

          $months_toggle_attributes = [
           'data-parent-in' => 'col-xs-9',
           'data-parent-out' => 'col-xs-3',
           'data-target-in' => 'pl-24',
           //'data-icon' => $icon, //'date_range',
           'class' => [
             'fs-default',
             'no-p'
           ]
         ];
         $build['#items'][$year_key]['content']['#toggle_attributes'] = new Attribute($months_toggle_attributes); 

         $months_wrapper = [
           'class'=> [
             'col-xs-3',
             'mr-8',
             'no-p' 
           ]
         ];
         $build['#items'][$year_key]['content']['#pane_wrapper_attributes'] = new Attribute($months_wrapper);
       }

      }

   }       
   
   $build['#theme'] = 'nk_tools_collapsible_pane';
    

    $toggle_attributes = [ 
      'data-parent-in' => 'col-xs-12',
      'data-parent-out' => 'col-xs-3',
      'data-target-in' => 'row',
      'data-icon' => $icon,
      'class' => [
        'pl-4'
      ]
    ];
    $build['#toggle_attributes'] = new Attribute($toggle_attributes); 

    $pane_wrapper = [
      'class'=> [
        'col-xs-3',
        'mr-8',
        'no-p' 
      ]
    ];
    $build['#pane_wrapper_attributes'] = new Attribute($pane_wrapper);

    return $build;
  }

    /**
   * Appends widget library and relevant information for it to build array.
   *
   * @param array $build
   *   Reference to build array.
   */
  protected function appendWidgetLibrary(array &$build) {
    $build['#attached']['library'][] = 'facets/drupal.facets.link-widget';
    $build['#attributes']['class'][] = 'js-facets-links';
    $build['#attributes']['class'][] = 'pl-16';
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $form = parent::buildConfigurationForm($form, $form_state, $facet);
    $form['nk_tools_search_date_icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon'),
      '#description' => $this->t('Set icon for date widget\'s parent'),
      '#default_value' => $this->getConfiguration()['nk_tools_search_date_icon'],
    ];

   return $form;
  }

}
