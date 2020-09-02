<?php

namespace Drupal\nk_tools_search\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Search;
use Drupal\Core\Url;

use Drupal\Component\Utility\Html;

use Drupal\nk_tools\NkToolsBase;

/**
 * Provides a form element for a Views reference composite element.
 *
 * Usage example:
 * @code
 * $form['reference_views'] = [
 *   '#type' => 'nk_tools_views_reference',
 *   '#title' => t('Reference Views'),
 *   '#default_value' => [
 *     'view_id' => $view_id ? $view_id : NULL,
 *     'display_id' => $display_id ? $display_id : '',
 *   ],
 * ];
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Checkboxes
 * @see \Drupal\Core\Render\Element\Radios
 * @see \Drupal\Core\Render\Element\Select
 *
 * @FormElement("nk_tools_search_input")
 */

class NkToolsSearchInput extends Search {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    
    $class = get_class($this);
    $info = parent::getInfo();
    // Append our process function
    $info['#pre_render'][] = [$class, 'prerenderSearchInput'];
    // Prepend our process function as first
    //array_unshift($info['#pre_render'], [$class, 'prerenderSearchInput']);
    return $info;
  }

  public static function prerenderSearchInput($element) {

    $keys = array_filter($element['#parents'], function($var) {
      if (is_int($var)) {
        return $var;
      } 
    });

    if (is_array($keys)) {
      $delta = empty($keys) ? 0 : reset($keys);
    }
    else {
      $delta = 0;
    }

    
    $nk_tools_config = \Drupal::service('config.factory')->get('nk_tools.settings')->getRawData();
    
    // Assign sent default values to our overall config array
    $config = isset($element['#default_value']) && !empty($element['#default_value']) ? $element['#default_value'] : [];
    
    //$config['type'] = isset($config['input_type']) && !empty($config['input_type']) ? $config['input_type'] : 'search';

    // Set view related variables
    $view_id = isset($config['view_id']) && !empty($config['view_id']) ? $config['view_id'] : NULL;
    $display_id = isset($config['display_id']) && !empty($config['display_id']) ? $config['display_id'] : NULL;
    $argument = isset($config['argument']) && !empty($config['argument']) ? $config['argument'] : NULL;
    $filter = isset($config['filter']) && !empty($config['filter']) ? $config['filter'] : 'search_api_fulltext';

    // Set some default properties/variables first
    $wrapper_type = isset($config['type']) && !empty($config['type']) ? $config['type'] : 'single'; // Wether this is a single, standalone views exposed filter, without any other exposed filters
    $inline_target = isset($config['inline_target']) && !empty($config['inline_target']) ? $config['inline_target'] : NULL;
    if ($inline_target) {
      $name = 'search-inline'; 
    }
    else {
      if ($view_id && $display_id) {
        $name = $filter;
      }
      else {
        $name = 'search';
      }
    }
   
    if (isset($config['autocomplete']) && !empty($config['autocomplete'])) { //$element['#module_handler']->moduleExists('search_api_autocomplete')) {
      $config['search_api_autocomplete'] = TRUE;
    }
          
    $attached = [];
    $attached['drupalSettings']['nk_tools_search'][$name] = [
      'type' => $wrapper_type,
      'target' => $inline_target ? $inline_target : 'input.nk-tools-search-input',
      'layout' => $nk_tools_config['layout'],
      'config' => $element['#default_value']
    ];

    $element_config = [
      '#theme' => 'nk_tools_search_input',
      '#config' => $config,
      '#attributes' => [
        'class' => ['nk-tools-search-input'], 
        'type' => 'search',
        'placeholder' => isset($config['placeholder']) && !empty($config['placeholder']) ? $config['placeholder'] : NULL,
      ]
    ];
    
    if ($view_id && $display_id) {

      $views_exposed_form = [];
      $view_path = NULL;

      $attached['drupalSettings']['nk_tools_search'][$name]['view_id'] = $view_id;
      $attached['drupalSettings']['nk_tools_search'][$name]['display_id'] = $display_id;
      $attached['drupalSettings']['nk_tools_search'][$name]['view_filter'] = $filter;  

      if ($display_id != 'default') {
        $view_route = 'view.' . $view_id .'.' . $display_id; 
        $view_path = Url::fromRoute($view_route);
        if ($view_path) {
          $attached['drupalSettings']['nk_tools_search'][$name]['view_path'] = $view_path->toString(); 
        }
      }

      $config['view'] = ['view_id' => $view_id, 'display_id' => $display_id, 'argument' => $argument, 'filter' => $filter, 'path' => $view_path->toString()]; 

      $params = ['#config' => $config];
      $views_exposed_form = \Drupal::service('nk_tools.main_service')->renderViewFilter($view_id, $display_id, FALSE, $params);

      if (isset($views_exposed_form['form'])) {
        $element = $views_exposed_form['form'];
      }
      
      $element_config['#attributes']['data-drupal-selector'] = 'edit-' . $filter;
         
      // Finally a support for search_api_autocomplete required processing
      if (isset($config['autocomplete']) && !empty($config['autocomplete'])) {
        $plugin_id = 'views:' . $view_id;
        $search_storage = \Drupal::service('entity_type.manager')->getStorage('search_api_autocomplete_search');
        $search = $search_storage->loadBySearchPlugin($plugin_id);
        if ($search && $search->getEntityTypeId() == 'search_api_autocomplete_search') {
          // TODO: With search_api_autocomplete module on test if this is really needed
          search_api_autocomplete_form_views_exposed_form_alter($views_exposed['form'], $views_exposed['form_state']);

          $element_config['#attributes']['data-search-api-autocomplete-search'] = $view_id;
          $element_config['#attributes']['data-autocomplete-path'] = '/search_api_autocomplete/' . $view_id . '?display=' . $display_id .'&filter=' . $filter;
        }
      }

      // Uncomment this and create such twig template if you want access to "form-item" form element wrapper       
      //$element[$filter]['#theme_wrappers'][] = 'nk_tools_search_input_wrapper';

      // Set our theme and other input properties
      $element[$filter]['#theme'] = $element_config['#theme'];
      $element[$filter]['#config'] = $element_config['#config'];

      $element[$filter]['#attributes'] = $element_config['#attributes'];
      // Does not work for some reason, yet in theme / twig tpl it does
      //$element[$filter]['#type'] = 'search';
      // Element::setAttributes($element[$filter], ['type']);
      //static::setAttributes($element[$filter], ['form-search']);
    }
    // So this is not a View related input, set some default values
    else {
      

      // Set our theme and other input properties
      $element_config['#attributes']['data-drupal-selector'] = $element_config['#attributes']['name'] = $name;
      $element_config['#attributes']['id'] = $inline_target ? $name . '-' . $config['inline_target'] : $name . '-' . Html::getUniqueId($config['id']);
      if ($inline_target) {
        $element_config['#attributes']['data-target'] = $inline_target;   
      }
       
      $element = $element_config;
    }

    // Attach our config array to drupalSettings and have it available in accompanying jQuery code
    $element['#attached'] = $attached; 
    // Attach that jQuery code too
    $element['#attached']['library'][] = 'nk_tools_search/search_widget';
    
    return $element;

  }

}