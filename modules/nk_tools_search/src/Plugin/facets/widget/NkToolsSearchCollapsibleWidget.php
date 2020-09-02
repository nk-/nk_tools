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
 *   id = "nk_tools_search_collapsible",
 *   label = @Translation("Nk tools collapsible"),
 *   description = @Translation("A custom widget that implements grouped links within collapsible panes (aka nk_tools_collapsible_pane thene)"),
 * )
 */
class NkToolsSearchCollapsibleWidget extends LinksWidget {

  public function defaultConfiguration() {
    return [
      'nk_tools_search_collapsible_icon' => 'chevron_right',
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
    $icon = isset($this->getConfiguration()['nk_tools_search_collapsible_icon']) && !empty($this->getConfiguration()['nk_tools_search_collapsible_icon']) ? $this->getConfiguration()['nk_tools_search_collapsible_icon'] : NULL;

    //$year = '2019';
    $total = 0;
    $group = [];
     
    if (!empty($facet->collapsible)) {
      $links = [] ;
      foreach ($facet->collapsible as $collapsible_key => $collapsible) {
        
        $label = isset(array_values($collapsible)[0]) && isset(array_values($collapsible)[0]['label']) ? array_values($collapsible)[0]['label'] : $collapsible_key;
        $build['#items'][$collapsible_key] = [
          'target' => 'pane-' . $collapsible_key,
          'label' => $label,
          'content' => [],
        ];
        
        $links[$collapsible_key] = [
          '#theme' => 'links', 
          '#set_active_class' => TRUE,
          '#attributes' => [
            'data-list' => 'nk_tools_search_facets_collapsible',
            'class' => [
              'no-p',
              'list-none',
              'nk-tools-collapsible-links',
            ]
          ],
          '#links' => [],
        ];
        
        foreach ($collapsible as $item_key => $item) {
          $links[$collapsible_key]['#links'][$item_key] = [
            'url' => $item['item']->getUrl(),
            'title' => $item['item']->getDisplayValue() .' (' . $item['count'] . ')',
            'attributes' => [
              'data-count' => $item['count'],
              'data-drupal-facet-item-id' => $item['item']->getFacet()->id(),
              'data-drupal-facet-item-value' => $item['item']->getRawValue(),
              'class' => [
                'nk-tools-collapsible-facet-link'
              ] 
            ],

          ];
        }
        
        $build['#items'][$collapsible_key]['content'] = $links[$collapsible_key];

      }
    }
   
    $build['#theme'] = 'nk_tools_collapsible_pane';
    

    $toggle_attributes = [ 
      'data-icon' => $icon, //'date_range',
      'class' => [
        'pl-4'
      ]
    ];
    $build['#toggle_attributes'] = new Attribute($toggle_attributes); 

    $pane_wrapper = [
      'class'=> [
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
    $form['nk_tools_search_collapsible_icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon'),
      '#description' => $this->t('Set icon for countries widget\'s parent (continent)'),
      '#default_value' => $this->getConfiguration()['nk_tools_search_collapsible_icon'],
    ];

   return $form;
  }

}
