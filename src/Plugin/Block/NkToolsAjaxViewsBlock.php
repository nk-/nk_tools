<?php
 
namespace Drupal\nk_tools\Plugin\Block;
 
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\Element;

use Drupal\Component\Utility\Crypt;

use Drupal\nk_tools\Plugin\Block\NkToolsBlockBase;

/**
 * Provides a "DiploAjaxViewsBlock" block.
 *
 * @Block(
 *  id = "nk_tools_ajax_views_block",
 *  admin_label = @Translation("Ajax loaded View(s)"),
 * )
 */
class NkToolsAjaxViewsBlock extends NkToolsBlockBase {
 
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
       
      'view_id' => NULL,
      'display_id' => NULL,
      'argument' => NULL,
      'filter' => NULL,
      'rendered_view' => NULL,
      'rendered_view_wrapper' => NULL,
      'view_trigger' => NULL,

      'show_links' => NULL,
      'links' => NULL,
      'links_labels' => NULL,
      'argument_order' => NULL,
      'links_reset' => NULL,
      'links_reset_link' => NULL,
      'links_reset_label' => NULL,
      'class_assign_after' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
  
    $form = parent::blockForm($form, $form_state);

    // Disable caching on this form.
    $form_state->setCached(FALSE);

    $config = $this->getConfiguration();
    $nk_tools_config = \Drupal::config('nk_tools.settings');

    $values = $this->getCurrentFormState($form_state);
 
    $form['#after_build'][] = [$this, 'afterBuild'];

    // Views
    $this->viewElement($form, $form_state, $values, $config);
    $weight = 10;
    foreach (Element::children($form['view']) as $view_delta) {
      
      if (is_numeric($view_delta)) {
      
        $form['view'][$view_delta]['view_trigger'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Load trigger'),
          '#description' => $this->t('jQuery selector (a class) for element(s) that load this View on click, via async (ajax). For instance something like <em>.async-view-trigger</em>'), 
          '#default_value' => isset($config['view_trigger'][$view_delta]) ? $config['view_trigger'][$view_delta] : NULL,
          '#weight' => $weight,
        ];

        $rendered_view_id = 'nk-tools-rendered-view-' . $view_delta;

        $form['view'][$view_delta]['rendered_view'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Load in existing View DOM'),
          '#description' => $this->t('This is already a View generated page hence view dom element exist and we could load into that one.'), 
          '#default_value' => isset($config['rendered_view'][$view_delta]) ? $config['rendered_view'][$view_delta] : NULL, 
          '#attributes' => [
            // Define static name and id so we can easier select it
            'id' => $rendered_view_id,
          ], 
          '#weight' => $weight + 1,
        ];

        $form['view'][$view_delta]['rendered_view_wrapper'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Rendered view DOM Wrapper'),
          '#description' => $this->t('Provide jQuery selector of parent HTML element wrapper, in order to target existing view dom inside. Typical one may be for example <em>.block-system-main-block .views-element-container</em>'),
          '#default_value' => isset($config['rendered_view_wrapper'][$view_delta]) ? $config['rendered_view_wrapper'][$view_delta] : NULL,
          '#states' => [ 
            'visible' => [
             ':input[id="'. $rendered_view_id .'"]' => ['checked' => TRUE],
            ],
          ],
          '#weight' => $weight + 2, 
        ];
        
        $show_links_id = 'nk-tools-show-links-' . $view_delta;
        
        $form['view'][$view_delta]['show_links'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Render Links/Tabs'),
          '#description' => $this->t('Render a list of links, or tabs, that are loading triggers. These are bare values here (i.e. can be a View argument value) and the rest of the logic and styling should go into blok\'s twig template.'), 
          '#default_value' => isset($config['show_links'][$view_delta]) ? $config['show_links'][$view_delta] : NULL, 
          '#attributes' => [
            // Define static name and id so we can easier select it
            'id' => $show_links_id,
          ], 
          '#weight' => $weight + 3,
        ];

        $form['view'][$view_delta]['links'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Links/Tabs arguments'),
          '#description' => $this->t('A comma separated list of possible "fixed" contextual filters, triggered by links/tabs that are loading the view. For example, for events <em>upcoming, current, past</em>.'),
          '#default_value' => isset($config['links'][$view_delta]) ? $config['links'][$view_delta] : NULL,
          '#states' => [ 
            'visible' => [
              ':input[id="' . $show_links_id . '"]' => ['checked' => TRUE],
            ],
          ],
          '#weight' => $weight + 4, 
        ];

        $form['view'][$view_delta]['links_labels'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Links/Tabs labels'),
          '#description' => $this->t('A comma separated list of labels for any possible trigger links/buttons that load this view. This works only with the above field set and those labels follow the above sequence. So with the same example it could be like <em>Future, Current, Past events</em> etc.'),
          '#default_value' => isset($config['links_labels'][$view_delta]) ? $config['links_labels'][$view_delta] : NULL,
          '#states' => [
            'visible' => [
              ':input[id="' . $show_links_id . '"]' => ['checked' => TRUE],
            ],
          ], 
          '#weight' => $weight + 5,
        ];
    

        $form['view'][$view_delta]['argument_order'] = [
          '#type' => 'number',
          '#title' => $this->t('Arguments order'),
          '#description' => $this->t('This is the order of these arguments for the case where this block is used along with another block of the same kind and we need to ping-pong with arguments on click.'),
          '#default_value' => isset($config['argument_order'][$view_delta]) ? $config['argument_order'][$view_delta] : NULL,
          '#weight' => $weight + 6,
        ];

        $show_reset_id = 'nk-tools-show-reset-' . $view_delta;

        $form['view'][$view_delta]['links_reset'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Reset'),
          '#description' => $this->t('Render "reset" kind of link as a last one in a row of arguments links specified above.'),
          '#default_value' =>  isset($config['links_reset'][$view_delta]) ? $config['links_reset'][$view_delta] : NULL,
          '#attributes' => [
            // Define static name and id so we can easier select it
            'id' => $show_reset_id,
          ],
          '#weight' => $weight + 7,
        ];
      
        $form['view'][$view_delta]['links_reset_link'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Reset link'),
          '#description' => $this->t('Relative path starting with "/"'),
          '#default_value' => isset($config['links_reset_link'][$view_delta]) ? $config['links_reset_link'][$view_delta] : NULL,
          '#states' => [
            'visible' => [
              ':input[id="' . $show_reset_id . '"]' => ['checked' => TRUE],
            ],
          ],
          '#weight' => $weight + 8, 
        ];
        $form['view'][$view_delta]['links_reset_label'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Reset label'),
          '#description' => $this->t('Set label for reset link.'),
          '#default_value' => isset($config['links_reset_label'][$view_delta]) ? $config['links_reset_label'][$view_delta] : NULL,
          '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
            'visible' => [
              ':input[id="' . $show_reset_id . '"]' => ['checked' => TRUE],
            ],
          ],
          '#weight' => $weight + 9, 
        ];

      }
    }

    return $form;
  }
  
  /**
   * Custom form['#after_build'] method
   */
  public function afterBuild(array $form, FormStateInterface $form_state) {
    
    $form['nk_tools_fields']['animations']['class_assign_after'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add classes only upon Ajax response'),
      '#description' => $this->t('Does not apply current, otherwise adds this class to a block wrapper element only after ajax load was completed.'),
      '#default_value' => $config['class_assign_after'],
      '#weight' => 3,
      '#disabled' => TRUE,
      '#attributes' => [
        'disabled' => 'disabled',
      ]
 
    ];

    foreach (Element::children($form['view']) as $view_delta) {
      if (is_numeric($view_delta) && is_array($form['view'][$view_delta]) && isset($form['view'][$view_delta]['#type']) && $form['view'][$view_delta]['#type'] == 'nk_tools_views_reference') {
        $form['view'][$view_delta]['display']['argument']['#title'] = $this->t('Previous argument');
        $form['view'][$view_delta]['display']['argument']['#description'] = $this->t('This may be argument that exists in a View path before the one being manipulated here.');
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
 
    $config = $this->getConfiguration();
    $nk_tools_config = \Drupal::config('nk_tools.settings');
    $block_label = isset($config['block_label']['value']) && !empty($config['block_label']['value'])  ? $config['block_label']['value'] : '';
    $route = \Drupal::service('current_route_match');
    $route_params = $route->getParameters()->all();
    
    $parent =  parent::build();
   
    $build = [
      '#theme' => 'nk_tools_async_view_block',
      '#attributes' => [
        'class' => [
        ],
      ],
    ];

    $view_ids = is_array($config['view_id']) && !empty($config['view_id']) ? $config['view_id'] : [];
    $view_displays = is_array($config['display_id']) && !empty($config['display_id']) ? $config['display_id'] : [];
    $view_args = is_array($config['argument']) && !empty($config['argument']) ? $config['argument'] : [];
    $view_filters = is_array($config['filter']) && !empty($config['filter']) ? $config['filter'] : [];

    if (!empty($view_ids)) {

      // Generate unique id/key for this block
      $hash = Crypt::hashBase64($config['id'] . '__' . implode('__', $view_ids));

      $build['#attached']['drupalSettings']['nk_tools']['asyncBlocks'] = [];

      foreach ($view_ids as $delta => $view_id) {
     
        $view = $this->entityTypeManager->getStorage('view')->load($view_id);
        $display = $view->getDisplay($view_displays[$delta]);
        if (!$display) {
          $display = $view->getDisplay('default');  
        }

        $build['#views'][$delta] = $view;
        $build['#displays'][$delta] = $display; 

        $arguments = [];
        
        if (isset($route_params['view_id'])) {
          foreach ($route_params as $key => $param) {
            if (strpos($key, 'arg_') !== FALSE && $param) {
              $arguments[] = $param;
            }
          }
        }
        
        if (empty($arguments)) {
          $arguments = isset($view_args[$delta]) && !empty($view_args[$delta]) ? [$view_args[$delta]] : [];
        }
       
        $build['#arguments'][$delta] = $arguments;

        $build['#attached']['drupalSettings']['nk_tools']['asyncBlocks'][$hash] = [];

        $use_rendered = [];
        if (is_array($config['rendered_view']) && isset($config['rendered_view'][$delta]) && $config['rendered_view'][$delta] > 0) {
          $use_rendered[$delta] = is_array($config['rendered_view_wrapper']) && isset($config['rendered_view_wrapper'][$delta]) ? $config['rendered_view_wrapper'][$delta] : NULL;
        }

        // A View pager 
        //$pager = isset($view_display['display_options']['pager']) && !empty($view_display['display_options']['pager']['type']) ? $view_display['display_options']['pager']['type'] : 'none'; 
        $build['#attached']['drupalSettings']['nk_tools']['asyncBlocks'][$hash][$delta] = [
          'use_rendered' =>  $use_rendered[$delta],
          'trigger' => is_array($config['view_trigger']) && isset($config['view_trigger'][$delta]) && !empty($config['view_trigger'][$delta]) ? $config['view_trigger'][$delta] : NULL, 
          'once' => TRUE,
          'block_id' => 'async-view-block-' . $hash,
          'additionalClass' => $config['additional_class'],
          'animationIn' => $config['animation_in'],
          'animationOut' => $config['animation_out'],
          'order' => is_array($config['argument_order']) && isset($config['argument_order'][$delta]) ? $config['argument_order'][$delta] : NULL,
          'view' => [
            'pager_element' => 'mini', //$pager, //NULL,
            'view_name' => $view->id(),
            'view_display_id' => $display['id'],
            'view_args' => !empty($view_args) ? $view_args : NULL,
            'view_dom_id' => 'async-view-view-'. $hash, // Note that for usage of existing/rendered view dom it happens in JS since we can't have "future" view_dom_id here 
          ],
        ];

        $build['#items'] = [];
        $build['#labels'] = [];

        if (is_array($config['show_links']) && isset($config['show_links'][$delta]) && $config['show_links'][$delta] > 0) {
          $defined_arguments = is_array($config['links']) && isset($config['links'][$delta]) && !empty($config['links'][$delta]) ? explode(', ', $config['links'][$delta]) : [];
          $labels = is_array($config['links_labels']) && isset($config['links_labels'][$delta]) && !empty($config['links_labels'][$delta]) ? explode(', ', $config['links_labels'][$delta]) : [];
          if (!empty($defined_arguments)) {
            $links = [];
            foreach ($defined_arguments as $d => $argument) {
              $links[$d] = [
                'key' => $argument,
                'label' => isset($labels[$d]) ? $labels[$d] : ucfirst($argument),
              ];  
            }
            //$current_arg = !empty($route_params['arg_0']) && in_array($route_params['arg_0'], $arguments) ? $route_params['arg_0'] : NULL;
            // $config['links'] = $this->renderLinks($links, $config, $view_id, $display_id, $current_arg);
            $build['#reset_link'][$delta] = [];
            
            if (is_array($config['links_reset']) && isset($config['links_reset'][$delta]) && $config['links_reset'][$delta] > 0) {
              $build['#reset_link'][$delta] = [
                'view_id' => $view->id(),
                'display_id' => $display['id'],
                'uri' => is_array($config['links_reset_link']) && isset($config['links_reset_link'][$delta]) && !empty($config['links_reset_link'][$delta]) ? $config['links_reset_link'][$delta] : NULL,
                'label' => is_array($config['links_reset_label']) && isset($config['links_reset_label'][$delta]) && !empty($config['links_reset_label'][$delta]) ? $config['links_reset_label'][$delta] : NULL,
              ];
              if (!$build['#reset_link'][$delta]['uri']) {
                $build['#reset_link'][$delta]['uri'] = isset($display['display_options']['path']) ? '/' . $display['display_options']['path'] : Url::fromRoute('<current>')->toString();
              }
            }
            
            $ajax_links = $this->nkToolsFactory->renderAjaxArgumentsLinks($links, $config, $view, $display, $arguments, $build['#reset_link'][$delta]);
            $title = isset($parent['#title']) && !empty($parent['#title']) ? $parent['#title'] : $config['label'];
            $config['target_ui_id'] = !empty($block_label) ? Crypt::hashBase64($config['id'] . '__' . $config['label'] . '__' . $block_label) : Crypt::hashBase64($config['id'] . '__' . $config['label']);

            $build['#items'][$delta] = [
              'label' => $title,
              'value' => [
                '#theme' => 'nk_tools_ajax_links',
                '#ajax_links' => $ajax_links,
                '#attributes' => [],
              ],
              'target' => $config['target_ui_id'],    
            ]; 
            $build['#labels'][$delta] = $title; 
          } 
        }
      }  
    }
    else {
      // Generate unique id/key for this block
      $hash = !empty($block_label) ? Crypt::hashBase64($config['id'] . '__' . $config['label'] . '__' . $block_label) : Crypt::hashBase64($config['id'] . '__' . $config['label']);
    }    

    $build['#dom_id'] = 'async-view-view-'. $hash;
    $build['#attributes']['class'][] = 'async-view-block-' . $hash;

    $build['#config'] = $config;

    /*
    if ((isset($config['animation_in']) && !empty($config['animation_in'])) || (isset($config['animation_out']) && !empty($config['animation_out']))) {
      if (isset($config['nk_tools_fields']['class_assign_after']) && $config['nk_tools_fields']['class_assign_after'] > 0 && in_array($config['animation_in'], array_values($parent['#attributes']['class']))) {
        $filtered = array_filter($parent['#attributes']['class'], function($var) {
          return $var != $config['animation_in'];
        });
        $parent['#attributes']['class'] = $filtered;
      }
    }
    */

    $build['#attached']['library'][] = 'nk_tools/async_vew';

    $this->nkToolsFactory->renderTargetUi($build, $config);
    $build['#ajax_links'] = $build['#items'];

    return $parent + $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
  
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();

    // View
    $this->viewElementSubmit($values);
    
    //$this->configuration['class_assign_after'] = isset($values['nk_tools_fields']['animations']) && isset($values['nk_tools_fields']['animations']['class_assign_after']) && $values['nk_tools_fields']['animations']['class_assign_after'] > 0 ? $values['nk_tools_fields']['animations']['class_assign_after'] : NULL;

  }

}