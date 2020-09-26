<?php

namespace Drupal\nk_tools\Element;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Fieldset;
#use Drupal\Core\Render\Element\CompositeFormElementTrait;
use Drupal\Core\Entity\Element\EntityAutocomplete;

use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\Component\Utility\NestedArray;

use Drupal\views\Entity\View;

use Symfony\Component\DependencyInjection\ContainerInterface;

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
 * @FormElement("nk_tools_views_reference")
 */

class NkToolsViewsReference extends Fieldset implements ContainerFactoryPluginInterface {

  # use CompositeFormElementTrait;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
   // parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {

    $class = get_class($this);
    $info = parent::getInfo();

    // Store View entity storage reference here
    $info['#view_storage'] = $this->entityTypeManager->getStorage('view');

    // Prepend our process function as first
    array_unshift($info['#process'], [$class, 'processViewsReference']);

    return $info;
  }


  public static function processViewsReference(&$element, FormStateInterface $form_state, &$complete_form) {

    $trigger = $form_state->getTriggeringElement();

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

    $element['view_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => t('View label'),
      '#target_type' => 'view',
      '#description' => t('Select a View that will serve a route with search result.'),
      '#ajax' => [
        'event' => 'autocompleteclose',
        'callback' => '\Drupal\nk_tools\Element\NkToolsViewsReference::showViewDisplays',
        'effect' => 'fade',
        'wrapper' => 'nk-tools-views-reference-display-wrapper-' . $delta,
        'progress' => [
          'type' => 'throbber',
          'message' => t('Verifying entry...'),
        ],
      ],
      // '#submit' => ['\Drupal\nk_tools\Element\NkToolsViewsReference::addDisplays']
    ]; 

    $element['display'] = [
      '#type' => 'container',
      '#weight' => 2,
      '#attributes' => [
        'id' => 'nk-tools-views-reference-display-wrapper-' . $delta,
      ],
      //'#id' => 'nk-tools-views-reference-display-wrapper-' . $delta,
      //'#prefix' => '<div id="nk-tools-views-reference-display-wrapper-' . $delta .'">',
      //'#suffix' => '</div>'
    ];


    /*
    $element['remove_view'] = [
      '#type' => 'submit',
      '#name' => 'op-' . $delta,
      '#value' => t('Remove'),
      '#weight' => 10,
      '#submit' => [
        '\Drupal\nk_tools\Element\NkToolsViewsReference::removeSubmit', //'::removeSubmit',
      ],
      '#ajax' => [
        'callback' => '\Drupal\nk_tools\Element\NkToolsViewsReference::removeAjax',
        //'callback' => ['::removeAjax'],
        'wrapper' => 'views-fieldset-wrapper',
      ],
    ];
    */

    static::getViewDisplays($element, $form_state, $delta);

    return $element;

  }

  public static function getViewDisplays(&$element, FormStateInterface $form_state, $delta = 0) {

    $values = $form_state->getValues();

    if (isset($element['#default_value']['view_id']) && !empty($element['#default_value']['view_id'])) {
      $view = $element['#view_storage']->load($element['#default_value']['view_id']);
    }  
    else {
      $view = NULL;
    }

    $trigger = $form_state->getTriggeringElement();
    //if ($trigger && $trigger['#type'] == 'entity_autocomplete') {

    if ($view) { 
      # if ($view_ref && isset($view_ref['view_id']) && !empty($view_ref['view_id'])) {
      //preg_match('/\((.*?)\)/', $view_ref['view_id'], $view_id);
      //$view_id = $view_id && $view_id[1] ? $view_id[1] : NULL;
      //$element['view_id']['#default_value'] = $views['view_id'];
      if ($trigger && strpos($trigger['#name'], 'op-') !== FALSE ) {
        $index = (int) str_replace('op-', '', $trigger['#name']);
        if ($delta == $index) {
        }
        //\Drupal::logger('Vallues')->notice('<pre>' . print_r($values, 1) . '<pre>');
      }

      // View display select
      $display_options = [];
      $has_value = ['filled' => TRUE];
      //$view_input_name = isset($element['#name']) ? $element['#name'];

      // Essential
      $element['view_id']['#default_value'] = $view;

      $view_data = $view->toArray(); 
      foreach ($view_data['display'] as $display_id => $display) {
        $display_options[$display_id] = $display['display_title'];
      }

      $default_display_id = isset($element['#default_value']['display']['display_id']) && !empty($element['#default_value']['display']['display_id']) ? $element['#default_value']['display']['display_id'] : NULL;    

      $element['display']['display_id'] = [
        '#title' => t('View\'s display label'),
        '#description' => t('Display name of a View that we are loading for this block.'),
        '#type' => 'select',
        '#options' => $display_options,
        '#validated' => TRUE,
        '#empty_option' => t('- Choose -'),
        '#default_value' => $default_display_id, //isset($views['display_id']) ? $views['display_id'] : '',
        '#attributes' => [
          'class' => [
            'nk-tools-viewsreference-display-id',
          ],
        ],
        //'#prefix' => '<div id="diplo-views-reference-display-wrapper-' . $delta .'">',
        //'#suffix' => '</div>'
        //'#states' => [
        //  'visible' => [
        //    ':input[name="' . $view_input_name .'"]' => $has_value, //['value' => 'other'],
        //  ],
        //],
      ];

      $element['display']['argument'] = [
        '#type' => 'textfield',
        '#title'  => t('View argument'),
        '#description' => t('Value of View argument (contextual filter) you may want to use here, for instance within twig template, some hook or so. Leave blank for default functionality.'),
        '#default_value' => isset($element['#default_value']['display']['argument']) && !empty($element['#default_value']['display']['argument']) ? $element['#default_value']['display']['argument'] : NULL,
      ];  

      $element['display']['filter'] = [
        '#type' => 'textfield',
        '#title'  => t('View\'s exposed Filter identifier'),
        '#description' => t('A machine name of any possible exposed filter used in scope. Leave blank for default functionality.'),
        '#default_value' => isset($element['#default_value']['display']['filter']) && !empty($element['#default_value']['display']['filter']) ? $element['#default_value']['display']['filter'] : NULL,
     ];  

    }
    return $element;
  }

  public static function showViewDisplays(&$form, FormStateInterface $form_state) {

    $trigger = $form_state->getTriggeringElement();
    $parents = array_slice($trigger['#parents'], 0, -1);
    $parents[] = 'display';
    //$parents[] = 'display_id';
    $element = NestedArray::getValue($form, $parents);
    return $element; 
  }


  public static function removeAjax(&$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $num_views = $form_state->get('num_views');

    if ($trigger && strpos($trigger['#name'], 'op-') !== FALSE) {
      $index = (int) str_replace('op-', '', $trigger['#name']);
      $parents = array_slice($trigger['#parents'], 0, -2);
      $parent = NestedArray::getValue($form, $parents);


      $unset = $parents;
      $unset[] = $index;
      //$form_state->unsetValue($unset);
      $values= [];
      return $parent;
    }
    else {
      return [];
    }

  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public static function removeSubmit(array &$form, FormStateInterface $form_state) {

     $num_views = $form_state->get('num_views') - 1;


    //$values = $form_state->getValues();


    $trigger = $form_state->getTriggeringElement();

    if ($trigger && strpos($trigger['#name'], 'op-') !== FALSE) {

      //$form_state->set('num_views', $num_views); 

      $index = (int) str_replace('op-', '', $trigger['#name']);
      $parents = array_slice($trigger['#parents'], 0, -2);

      $unset = $parents;
      $unset[] = $index; 
      $form_state->unsetValue($unset);
      // $form_state->setUserInput($newInputArray);
      $form_state->setRebuild(TRUE);

    }

  }

  public static function addDisplays(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
/*
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE) {
      // When there's user input (including NULL), return it as the value.
      // However, if NULL is submitted, FormBuilder::handleInputElement() will
      // apply the default value, and we want that validated against #options
      // unless it's empty. (An empty #default_value, such as NULL or FALSE, can
      // be used to indicate that no radio button is selected by default.)
      if (!isset($input) && !empty($element['#default_value'])) {
        $element['#needs_validation'] = TRUE;
      }
      return $input;
    }
    else {
      // For default value handling, simply return #default_value. Additionally,
      // for a NULL default value, set #has_garbage_value to prevent
      // FormBuilder::handleInputElement() converting the NULL to an empty
      // string, so that code can distinguish between nothing selected and the
      // selection of a radio button whose value is an empty string.
      $value = isset($element['#default_value']) ? $element['#default_value'] : NULL;
      if (!isset($value)) {
        $element['#has_garbage_value'] = TRUE;
      }
      return $value;

    }
  }
*/

}
