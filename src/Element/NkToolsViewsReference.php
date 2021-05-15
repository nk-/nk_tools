<?php

namespace Drupal\nk_tools\Element;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Render\Element\Fieldset;

use Drupal\Component\Utility\NestedArray;

use Drupal\views\Entity\View;

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

    // Append our process function as first
    $info['#process'][] = [$class, 'processViewsReference'];

    return $info;
  }

  /**
   * Our own specific process callback
   */
  public function processViewsReference(&$element, FormStateInterface $form_state, &$complete_form) {

    // $trigger = $form_state->getTriggeringElement();

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
      //'#executes_submit_callback' => TRUE,
      //'#limit_validation_errors' => [],
      '#title' => t('View label'),
      '#target_type' => 'view',
      '#description' => t('Select a View that will serve here.'),
      '#default_value' => NULL,
      '#ajax' => [
        'event' => 'autocompleteclose',
        'callback' => [__CLASS__ , 'showViewDisplays'],
        'effect' => 'fade',
        'wrapper' => 'nk-tools-views-reference-display-wrapper-' . $delta,
        'progress' => [
          'type' => 'throbber',
          'message' => t('Verifying entry...'),
        ],
      ],
      //'#submit' => [__CLASS__ , 'showDisplaysSubmit'], //'\Drupal\nk_tools\Element\NkToolsViewsReference::showDisplaysSubmit']
    ]; 

    $element['display'] = [
      '#type' => 'container',
      '#weight' => 2,
      '#attributes' => [
        'id' => 'nk-tools-views-reference-display-wrapper-' . $delta,
      ],
    ];

    static::getViewDisplays($element, $form_state, $delta);

    return $element;

  }

  /**
   * A helper method for process function
   */
  public static function getViewDisplays(&$element, FormStateInterface $form_state, $delta = 0) {

    $values = $form_state->getValues();

    $input = $form_state->getUserInput();
    $view_id = NULL;

    if (isset($input['settings']['view']) && isset($input['settings']['view'][$delta]) && isset($input['settings']['view'][$delta]['view_id'])) {
      if (!empty($input['settings']['view'][$delta]['view_id'])) {
        $view_id =  strpos($input['settings']['view'][$delta]['view_id'], '(') !== FALSE ? EntityAutocomplete::extractEntityIdFromAutocompleteInput($input['settings']['view'][$delta]['view_id']) : $input['settings']['view'][$delta]['view_id'];
      }
    }
    
    if ($view_id  === NULL || empty($view_id)) {
      $view_id = isset($element['#default_value']) && isset($element['#default_value']['view_id']) ? $element['#default_value']['view_id'] : NULL;
    }

    //$trigger = $form_state->getTriggeringElement();
    

    if (is_string($view_id)) { 

      $view = View::load($view_id); 

      if ($view instanceof View) {
        
        // View display select
        $display_options = [];
        $has_value = ['filled' => TRUE];

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
          '#empty_option' => t('- Choose display -'),
          '#default_value' => $default_display_id,
          '#attributes' => [
            'class' => [
              'nk-tools-viewsreference-display-id',
            ],
          ],
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
    }
    return $element;
  }

  /**
   * Ajax callback on view_id element
   */
  public static function showViewDisplays(&$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $parents = array_slice($trigger['#parents'], 0, -1);
    $parents[] = 'display';
    $element = NestedArray::getValue($form, $parents);
    return $element; 
  }
}