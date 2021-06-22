<?php

namespace Drupal\nk_tools_search\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Cache\Cache;

use Drupal\Component\Utility\Html;

use Drupal\nk_tools\Plugin\Block\NkToolsBlockBase;

/**
 * Provides block with links to social networks.
 *
 * @Block(
 *   id = "nk_tools_search_input_block",
 *   admin_label = @Translation("Search input"),
 *   category = @Translation("Nk tools")
 * )
 */
class NkToolsSearchInput extends NkToolsBlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
   return [
      'autocomplete' => NULL,
      'inline' => NULL,
      'inline_target' => NULL,
      'collapsed' => NULL,
      'placeholder' => NULL,
      'icon' => 'search',
      'border' => NULL,
      'border_classes' => NULL, 
      'view_id' => NULL,
      'display_id' => NULL,
      'argument' => NULL,
      'filter' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
  
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['search_layout'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Layout and styles'),
    ];

    $form['search_layout']['inline'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Inline search'),
      '#description' => $this->t('A variation of a widget where DOM markup content is parsed and searched by jQuery.'), 
      '#default_value' => $config['inline'],
      '#attributes' => [
        // Define static name and id so we can easier select it
        'id' => 'nk-tools-search-inline',
      ], 
    ];

    if ($this->moduleHandler->moduleExists('search_api_autocomplete')) {
      $form['search_layout']['autocomplete'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Search API autocomplete'),
        '#description' => $this->t('This module is installed and its functionality can apply to this input.'), 
        '#default_value' => $config['autocomplete'],
      ];
    }
    
    $form['search_layout']['inline_target'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Inline search target'),
      '#description' => $this->t('jQuery selector for the paent container whose content will be searched. For example <em>#content-items</em> or <em>.main-content .item-list</em> and similar.'), 
      '#default_value' => $config['inline_target'],
      '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
        'visible' => [
          ':input[id="nk-tools-search-inline"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['search_layout']['collapsed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Input collapsed'),
      '#description' => $this->t('If checked, only clickable search icon shows up.'), 
      '#default_value' => $config['collapsed'],
    ]; 

    $form['search_layout']['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder text'),
      '#description' => $this->t('A placeholer attribute for this block, leave empty for none.'), 
      '#default_value' => $config['placeholder'],
    ];

    $form['search_layout']['border'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Border'),
      '#description' => $this->t('Set border for search input\'s parent wrapper'), 
      '#default_value' => $config['border'],
      '#attributes' => [
        // Define static name and id so we can easier select it
        'id' => 'nk-tools-search-input-border',
      ], 
    ];
 
    $form['search_layout']['border_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Border classes'),
      '#description' => $this->t('A space separated list of classes to apply to search input\s parent wrapper. For example: <em>border-1 border-grey border-radius-4</em>'), 
      '#default_value' => $config['border_classes'],
      '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
        'visible' => [
          ':input[id="nk-tools-search-input-border"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Relate View responsible for a search results 
    $view_id = NULL;
    $view = NULL;
    $view_storage = $this->entityTypeManager->getStorage('view');
 
    // Check current form state values (if coming from ajax or not) 
    if ($form_state instanceof SubformStateInterface) {
      $values = $form_state->getCompleteFormState()->getValues();
    }
    else {
      $values = $form_state->getValues();
    }

    if (isset($values['settings']) && isset($values['settings']['view']) && !empty($values['settings']['view'])) {
      $view_id =  isset($values['settings']['view']['view_id']) ? $values['settings']['view']['view_id'] : NULL;  
    }
    else {
      if (isset($values['view']) && !empty($values['view'])) {
        $view_id = isset($values['view']['view_id']) ? $values['view']['view_id'] : NULL;
      } 
    }

    if ($view_id) {
      $view = is_object($view_id) ? $view_id : $view_storage->load($view_id);
    }
    else {
      $view_id = isset($config['view_id']) && !empty($config['view_id']) ? $config['view_id'] : NULL;
      $view = $view_id ? $view_storage->load($view_id) : NULL;
    }
  
    // Custom composite element
    $form['view'] = [ 
      '#type' => 'nk_tools_views_reference',
      '#title' => $this->t('Relate a View'),
      //'#description' =>  $this->t('Here we choose a View that will serve a route with search result'),
      '#default_value' => [
        'view_id' => $view_id,
        'display' => [
          'display_id' => $config['display_id'],
          'argument' => $config['argument'],
          'filter' => $config['filter'],
        ]
      ],
    ];
  
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    
    parent::blockSubmit($form, $form_state);
    
    $values = $form_state->getValues();
 
    if (isset($values['search_layout']) && !empty($values['search_layout'])) {
      foreach ($values['search_layout'] as $key => $layout_value) {
        $this->configuration[$key] = !empty($layout_value) ? $layout_value : NULL;
      }
    }

    // Search View related values
    if (isset($values['view']) && isset($values['view']['view_id']) && !empty($values['view']['view_id'])) {

      $this->configuration['view_id'] = $values['view']['view_id'];

      if (isset($values['view']['display']) && !empty($values['view']['display'])) {
        $this->configuration['display_id'] = isset($values['view']['display']['display_id']) && !empty($values['view']['display']['display_id']) ? $values['view']['display']['display_id'] : NULL;
        $this->configuration['argument'] = isset($values['view']['display']['argument']) && !empty($values['view']['display']['argument']) ? $values['view']['display']['argument'] : NULL;
        $this->configuration['filter'] = $values['view']['display']['filter'];
      }
      else {
        $this->configuration['display_id'] = NULL;
        $this->configuration['argument'] = NULL;  
        $this->configuration['filter'] = NULL;
      } 
    }
    else {
      $this->configuration['view_id'] = NULL;
      $this->configuration['display_id'] = NULL;
      $this->configuration['argument'] = NULL;  
      $this->configuration['filter'] = NULL; 
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $config = $this->getConfiguration();
    $config['input_type'] = 'search';
  
    $search_input_element = [
      '#type' => 'nk_tools_search_input',
      '#default_value' => $config,
      '#cache' => [
        'contexts' => [ 
          'url',
          'route', 
        ],
      ], 
    ];
    // Attach that jQuery code too
    //$search_input_element['#attached']['library'][] = 'nk_tools_search/search_widget'; 
/*
    $parent = parent::build(); 
    $parent['#attached']['library'][] = 'search_api_autocomplete/search_api_autocomplete';
    ksm($parent);
*/
  
    return parent::build() + $search_input_element;
  }

  public function getCacheContexts() {
    //if you depends on \Drupal::routeMatch()
    //you must set context of this block with 'route' context tag.
    //Every new route this block will rebuild
    return Cache::mergeContexts(parent::getCacheContexts(), ['route', 'url']);
  }


}