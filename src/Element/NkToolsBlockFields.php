<?php

namespace Drupal\nk_tools\Element;

use Drupal\Core\Render\Element\Details;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Provides a form element for a Views reference composite element.
 *
 * Usage example:
 * @code
 * $form['nk_tools'] = [
 *   '#type' => 'nk_tools_block_fields',
 *   '#title' => t('Reference Views'),
 *   '#open' => FALSE,
 *   '#default_value' => [
 *     'block_label' => $block_label ? $block_label : NULL,
 *     'hide_mobile' => $hide_mobile ? $hide_mobile : NULL,
 *   ],
 * ];
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Checkboxes
 * @see \Drupal\Core\Render\Element\Radios
 * @see \Drupal\Core\Render\Element\Select
 *
 * @FormElement("nk_tools_block_fields")
 */
class NkToolsBlockFields extends Details {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    
    $class = get_class($this);
    $info = parent::getInfo();
    array_unshift($info['#process'], [$class, 'processBlockFields']);
    return $info;
  }

  /**
   * Processes a file upload element, make use of #multiple if present.
   */
  public static function processBlockFields(&$element, FormStateInterface $form_state, &$complete_form) {
  
    $label_value = isset($element['#default_value']) && isset($element['#default_value']['block_label']) &&  isset($element['#default_value']['block_label']['value']) ? $element['#default_value']['block_label']['value'] : NULL; 
    $label_format = isset($element['#default_value']) && isset($element['#default_value']['block_label']) &&  isset($element['#default_value']['block_label']['format']) ? $element['#default_value']['block_label']['format'] : NULL; 

    $element['block_label'] = [
      '#base_type' => 'textfield',
      '#type' => 'text_format',
      '#title' => t('Block label'),
      '#description' => t('The real title for this block. Note that default Drupal\'s "Display title" checkbox <strong>controls this one too</strong>. Yet, the default Drupal\'s "Title" field becomes only admin title in the backend.'),
      '#format' => $label_format ? $label_format : 'basic_html',
      '#default_value' => $label_value,
      '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
        'visible' => [
          ':input[name="settings[label_display]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['hide_mobile'] = [
      '#type' => 'checkbox',
      '#title' => t('Do not display on mobile'),
      '#description' => t('It ends up on just a single CSS rule eventually.'), 
      '#default_value' => isset($element['#default_value']) && isset($element['#default_value']['hide_mobile']) ? $element['#default_value']['hide_mobile'] : NULL, 
    ];

    $element['hide_init'] = [
      '#type' => 'checkbox',
      '#title' => t('Hide initially'),
      '#description' => t('This block is initially hidden in CSS, meant to be shown upon some click or scroll, or similar "action/event".') ,
      '#default_value' => isset($element['#default_value']) && isset($element['#default_value']['hide_init']) ? $element['#default_value']['hide_init'] : NULL, 
    ];

    $element['icon'] = [
      '#type' => 'textfield',
      '#title' => t('Icon'),
      '#description' => t('Any icon you could use in twig template. Provided are <a href="https://material.io/resources/icons/" target="blank_">material icons </a>, can be any icon name from that set.'),
      '#default_value' => isset($element['#default_value']) && isset($element['#default_value']['icon']) ? $element['#default_value']['icon'] : NULL,
    ];

    $element['additional_class'] = [
      '#type' => 'textfield',
      '#title' => t('Additional CSS class for this block'),
      '#description' => t('Just a class name, without "."'),
      '#default_value' => isset($element['#default_value']) && isset($element['#default_value']['additional_class']) ? $element['#default_value']['additional_class'] : NULL,
    ];
    
    $element['animations'] = [
      '#type' => 'fieldset',
      '#title' => t('Animation classes'),
      '#description' => t('In & Out CSS animation classes for this block. Seel list of animations/effects of <a href="https://daneden.github.io/animate.css/" target="blank_">Animate.css</a> which is integrated and supported here.') ,
      '#open' => TRUE,
    ];
    $element['animations']['animation_in'] = [
      '#type' => 'textfield',
      '#title' => t('Animation "in"'),
      '#description' => t('A CSS class name, without ".", f.ex. <em>bounceInUp</em>'),
      '#default_value' => isset($element['#default_value']) && isset($element['#default_value']['animation_in']) ? $element['#default_value']['animation_in'] : NULL,
    ];
    $element['animations']['animation_out'] = [
      '#type' => 'textfield',
      '#title' => t('Animation "out"'),
      '#description' => t('A CSS class name, without ".", f.ex. <em>bounceOutDown</em>'),
      '#default_value' => isset($element['#default_value']) && isset($element['#default_value']['animation_out']) ? $element['#default_value']['animation_out'] : NULL,
    ];

    return $element;
  }
}