<?php
 
namespace Drupal\nk_tools\Plugin\Block;
 
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Crypt;

use Drupal\views\Entity\View;
use Drupal\views\ViewExecutable;

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
      'rendered_view' => [],
      'rendered_view_wrapper' => [],
      'view_trigger' => [],
      //'view_reference' => [],
      //'block_view_display_reference' => [],
      //'block_view_args' => [],
      'block_show_links' => [],
      'block_links'=> [],
      'block_links_labels'=> [],
      'block_additional_class_assign' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
/*
  protected function blockAccess(AccountInterface $account) {

    try {
      $config = $this->getConfiguration();
      $route = \Drupal::service('current_route_match');
      //$is_view = $route->getParameters()->has('view_id');
      $parameters = $route->getParameters()->all();
      if (isset($parameters['view_id']) && !empty($parameters['view_id']) && isset($parameters['display_id']) && !empty($parameters['display_id'])) {
        $config_view_id = isset($config['view_id']) && !empty($config['view_id']) ? $config['view_id'][0] : NULL;
        //$config_display_id = isset($config['block_view_display_reference']) && !empty($config['block_view_display_reference']) ? $config['block_view_display_reference'][0] : NULL;
        // This is the same View - grant access
        if ($config_view_id == $parameters['view_id']) { // && $config_display_id == $parameters['display_id']) {
          return parent::blockAccess($account);
        }
        else {
          return AccessResult::forbidden();
        }
      }
      else {
        return AccessResult::forbidden();
      }
    }
    catch (\UnexpectedValueException $ex) {
      return AccessResult::neutral();
    }
   
    return parent::blockAccess($account);
  }
 
*/


  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
  
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();
    $nk_tools_config = \Drupal::config('nk_tools.settings');

/*
    $form['block_label'] = [
      '#base_type' => 'textfield',
      '#type' => 'text_format',
      '#title' => $this->t('Block label'),
      '#description' => $this->t('The real title for this block.'),
      '#format' => isset($config['block_label']['format']) && !empty($config['block_label']['format']) ? $config['block_label']['format'] : 'full_html',
      '#default_value' => isset($config['block_label']['value']) && !empty($config['block_label']['value']) ? $config['block_label']['value'] : '',  
    ];
*/
    
    // Gather the number of referenced views in the form already.
    $num_views = $form_state->get('num_views');
    // We have to ensure that there is at least one view
    if ($num_views === NULL) {
      if (isset($config['view_id']) && count($config['view_id']) > 1) {
        $num_views = count($config['view_id']);
      }
      else {
        $num_views = 1;
      }
    }

    // Check current form state values (if coming from ajax or not) 
    if ($form_state instanceof SubformStateInterface) {
      $values = $form_state->getCompleteFormState()->getValues();
    }
    else {
      $values = $form_state->getValues();
    }
    
    $view_ids = [];
    $display_ids = [];
    $arguments = [];
    $filters = [];

    if (isset($values['settings']) && isset($values['settings']['block_views']) && isset($values['settings']['block_views']['block_view']) && !empty($values['settings']['block_views']['block_view'])) {
      foreach ($values['settings']['block_views']['block_view'] as $delta => $view_data) {
        $view_ids[$delta] = $view_data['view_id'];
        $display_ids[$delta] = $view_data['display']['display_id'];
        $arguments[$delta] = $view_data['display']['argument'];
        $filters[$delta] = $view_data['display']['filter'];
      }
    }
    else {
      $view_ids = !empty($config['view_id']) ? $config['view_id'] : [];
      $display_ids = !empty($config['display']['display_id']) ? $config['display']['display_id'] : [];
      $arguments = !empty($config['display']['argument']) ? $config['display']['argument'] : [];
      $filters = !empty($config['display']['filter']) ? $config['display']['filter'] : [];
    }
    
    // Now that we did all checkups on properties, start building or Views configuration Details
    $form['block_views'] = [
      '#type' => 'details',
      '#title' => $this->t('Reference Views'),
      '#open' => TRUE,
      '#prefix' => '<div id="views-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    $view_insert = [];
    
    for ($i = 0; $i < $num_views; $i++) {
    
      $view_id = !empty($view_ids) && isset($view_ids[$i]) && !empty($view_ids[$i]) ? $view_ids[$i] : NULL;     
      $view_insert[$i] = $view_id ? View::load($view_id) : NULL;
      
      $display_id = !empty($display_ids) && isset($display_ids[$i]) && !empty($display_ids[$i]) ? $display_ids[$i] : NULL; 
      $argument = !empty($arguments) && isset($arguments[$i]) && !empty($arguments[$i]) ? $arguments[$i] : NULL; 
      $filter =  !empty($filters) && isset($filters[$i]) && !empty($filters[$i]) ? $filters[$i] : NULL; 

      $form['block_views']['block_view'][$i] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'view-id-container-' . $i,
          'class' => [
            'widget-next'
          ],
        ],
      ];

         
       // Custom composite element
      $form['block_views']['block_view'][$i] = [ 
        '#type' => 'nk_tools_views_reference',
        '#title' => $this->t('Reference a View'),
        //'#description' =>  $this->t('Here we choose a View that will serve a route with search result'),
        '#default_value' => [
          'view_id' => $view_id,
          'display' => [
            'display_id' => $display_id,
            'argument' => $argument,
            'filter' => $filter,
          ]
        ],
      ];

      $form['block_views']['block_view'][$i]['view_trigger'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Load trigger'),
        '#description' => $this->t('jQuery selector (a class) for element(s) that load this View on click, via async (ajax). For instance something like <em>.async-view-trigger</em>'), 
        '#default_value' => isset($config['view_trigger'][$i]) ? $config['view_trigger'][$i] : NULL,
       ];

/*
     $form['block_views']['block_view'][$i]['view_id'] = [ 
        '#title'  => $this->t('View label'),
        '#description' => $this->t('A label of a View to load here.'),
        '#type' => 'entity_autocomplete',
        '#target_type' => 'view',
        '#tags' => FALSE,
        // The #default_value can be either an entity object or an array of entity objects.
        '#default_value' => !empty($view_insert) && $view_insert[$i] instanceof View ? $view_insert[$i] : NULL,
        //'#disabled' => TRUE, 
        //'#multiple' => TRUE,
        '#maxlength' => '256',
        '#ajax' => [
          'event' => 'autocompleteclose',
          'callback' => [get_class($this), 'provideViewDisplays'],
          //'callback' => ['\Drupal\nk_tools\DiploFormattersService', 'provideViewDisplays'],
          //'wrapper' => 'display-id-container',
          'wrapper' => 'view-id-container-' . $i, //'wrapper' => 'reference-view-wrapper-' . $i,
          'progress' => [
            'type' => 'throbber',
            'message' => t('Verifying entry...'),
          ],
        ],
      ];
*/

 
/*
      $form['block_views']['block_view'][$i]['display'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'display-id-container-' . $i,
          'class' => [
            'widget-next'
          ],
        ],
      ];
  
      // View display select
      $display_options = [];
      $disabled = TRUE;

      if (!empty($view_insert) && $view_insert[$i] instanceof View) {
        $disabled = FALSE;
        $view_data = $view_insert[$i]->toArray(); 
        //$display_options[$i] = [];
        foreach ($view_data['display'] as $display_id => $display) {
          $display_options[$display_id] = $display['display_title'];
        }
      }

      $form['block_views']['block_view'][$i]['display']['block_view_display_reference'] = [
        '#type' => 'select',
        '#title' => $this->t('View display name'),
        '#description' => $this->t('Display name of a View that we are loading for this block.'),
        '#disabled' => $disabled,
        '#options' => $display_options,
        '#empty_option' => $this->t('- Choose -'),
        '#default_value' =>  isset($config['block_view_display_reference'][$i]) ? $config['block_view_display_reference'][$i] : NULL, //$default_display, //$config->get('display_id_reference'),
      ]; 

      $form['block_views']['block_view'][$i]['block_view_args'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Contextual filters'),
        '#description' => $this->t('If view has contextual filters set provide value(s). If case of multiple values separate those with a "/". For example, all/12/feed.'),
        '#default_value' => isset($config['block_view_args'][$i]) ? $config['block_view_args'][$i] : NULL,  
      ];
*/

      $form['block_views']['block_view'][$i]['rendered_view'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Load in existing View DOM'),
        '#description' => $this->t('This is already a View generated page hence view dom element exist and we could load into that one.'), 
        '#default_value' => isset($config['rendered_view'][$i]) ? $config['rendered_view'][$i] : NULL, 
        '#attributes' => [
          // Define static name and id so we can easier select it
          'id' => 'diplo-rendered-view',
        ], 
      ];

      $form['block_views']['block_view'][$i]['rendered_view_wrapper'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Rendered view DOM Wrapper'),
        '#description' => $this->t('Provide jQuery selector of parent HTML element wrapper, in order to target existing view dom inside. Typical one may be for example <em>.block-system-main-block .views-element-container</em>'),
        '#default_value' => isset($config['rendered_view_wrapper'][$i]) ? $config['rendered_view_wrapper'][$i] : NULL,
        '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
          'visible' => [
            ':input[id="diplo-rendered-view"]' => ['checked' => TRUE],
          ],
        ], 
      ];

      $form['block_views']['block_view'][$i]['block_show_links'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Render Links/Tabs'),
        '#description' => $this->t('Render a list of links, or tabs, that are loading triggers. These are bare values here (i.e. can be a View argument value) and the rest of the logic and styling should go into blok\'s twig template.'), 
        '#default_value' => isset($config['block_show_links'][$i]) ? $config['block_show_links'][$i] : NULL, 
        '#attributes' => [
          // Define static name and id so we can easier select it
          'id' => 'diplo-show-links',
        ], 
      ];

      $form['block_views']['block_view'][$i]['block_links'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Links/Tabs arguments'),
        '#description' => $this->t('A comma separated list of possible "fixed" contextual filters, triggered by links/tabs that are loading the view. For example, for events <em>upcoming, current, past</em>.'),
        '#default_value' => isset($config['block_links'][$i]) ? $config['block_links'][$i] : NULL,
        '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
          'visible' => [
            ':input[id="diplo-show-links"]' => ['checked' => TRUE],
          ],
        ], 
      ];

      $form['block_views']['block_view'][$i]['block_links_labels'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Links/Tabs labels'),
        '#description' => $this->t('A comma separated list of labels for any possible trigger links/buttons that load this view. This works only with the above field set and those labels follow the above sequence. So with the same example it could be like <em>Future, Current, Past events</em> etc.'),
        '#default_value' => isset($config['block_links_labels'][$i]) ? $config['block_links_labels'][$i] : NULL,
        '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
          'visible' => [
            ':input[id="diplo-show-links"]' => ['checked' => TRUE],
          ],
        ], 
      ];


/*
      $form['block_views']['block_view'][$i]['view_actions'] = [
        '#type' => 'container',
        '#description' => $this->t('May become future feature, to load more than one view in this way, sequentially, currently disabled'),
        '#attributes' => [
          'class' => [
            'container-inline'
          ]
        ]
      ];

      $form['block_views']['block_view'][$i]['view_actions']['remove_view'] = [
        '#type' => 'submit',
        '#name' => 'op-' . $i,
        '#disabled' => TRUE,
        '#value' => $this->t('Remove one'),
        '#submit' => [
          [get_class($this), 'removeCallback'], //'::removeCallback',
        ],
        '#ajax' => [
          'callback' => [get_class($this), 'removeOneCallback'],
          'wrapper' => 'views-fieldset-wrapper',
        ],
      ];
*/

    }

    $form['block_views']['views_actions'] = [
      '#type' => 'container',
      '#description' => $this->t('May become future feature, to load more than one view in this way, sequentially, currently disabled'),
      '#attributes' => [
        'class' => [
          'container-inline'
        ]
      ]
    ];
    
    $form['block_views']['views_actions']['add_view'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add+'),
      '#description' => $this->t('May become future feature, to load more than one view in this way, sequentially, currently disabled'),
      '#disabled' => TRUE,
      '#submit' => [
        [get_class($this), 'addOne'],
      ],
      '#ajax' => [
        'callback' => [get_class($this), 'addmoreCallback'],
        'wrapper' => 'views-fieldset-wrapper',
      ],
    ];

    // DOM and effects
    $form['block_effects'] = [
      '#type' => 'details',
      '#title' => $this->t('DOM and markup'),
      '#open' => TRUE,
    ];

    $form['block_effects']['block_hide_mobile'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not display on mobile'),
      '#description' => $this->t('It ends up on just a single CSS rule eventually.'), 
      '#default_value' => $config['block_hide_mobile'], 
    ];

    $form['block_effects']['block_hide_init'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide initially'),
      '#description' => $this->t('This block is initially hidden in CSS, meant to be shown upon some "action", i.e. playing a video.') ,
      '#default_value' => $config['block_hide_init'],
    ];


    $form['block_effects']['block_additional_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Additional CSS class for this block'),
      '#description' => $this->t('Just a class name, without "."'), 
      '#default_value' => $config['block_additional_class'],
    ];

    $form['block_effects']['block_animations'] = [
      '#type' => 'details',
      '#title' => $this->t('Animation classes'),
      '#description' => $this->t('In & Out CSS animation classes for this block. See a list of animations/effects of <a href="https://daneden.github.io/animate.css/" target="blank_">Animate.css</a> which is integrated and supported here.') ,
      '#open' => TRUE,
    ];
    $form['block_effects']['block_animations']['block_animation_in'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Animation in'),
      '#description' => $this->t('A CSS class name, without ".", f.ex. <em>bounceInUp</em>'),
      '#default_value' => $config['block_animation_in'],
    ];
    $form['block_effects']['block_animations']['block_animation_out'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Animation out'),
      '#description' => $this->t('A CSS class name, without ".", f.ex. <em>bounceOutDown</em>'),
      '#default_value' => $config['block_animation_out'],
    ];


    $form['block_effects']['block_additional_class_assign'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add classes only upon Ajax response'),
      '#description' => $this->t('Adds this class to a block wrapper element only after ajax load was completed.'),
      '#default_value' => $config['block_additional_class_assign'],
    ];

    return $form;
  
  }

   /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public static function addmoreCallback(array &$form, FormStateInterface $form_state) {
    // \Drupal::logger('diplo_view_displays')->notice('<pre>' . print_r($form['block_effects'], 1) .'</pre>');
    return $form['settings']['block_views']['block_view'];
  }

  public static function removeOneCallback(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    
    $input = $form_state->getUserInput();
    $i = (int) str_replace('op-', '', $input['_triggering_element_name']);
    
    if (isset($values['settings']['block_views']) && isset($values['settings']['block_views']) && !empty($values['settings']['block_views']['block_view'])) {
      $object = [];
      $num_views = $form_state->get('num_views');
      
      foreach ($values['settings']['block_views']['block_view'] as $index => $view) {
        if ($index == $i) { //if (isset($view['view_id']) && !empty($view['view_id']) && 
          unset($form['settings']['block_views']['block_view'][$i]);
        }
      }
    }
     
    return $form['settings']['block_views']['block_view'];
  }



  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public static function addOne(array &$form, FormStateInterface $form_state) {
    $view_field = $form_state->get('num_views');
    $add_button = $view_field + 1;
    $form_state->set('num_views', $add_button);

    // Since our buildForm() method relies on the value of 'num_names' to
    // generate 'name' form elements, we have to tell the form to rebuild. If we
    // don't do this, the form builder will not call buildForm().
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public static function removeCallback(array &$form, FormStateInterface $form_state) {
    $view_field = $form_state->get('num_views');
    if ($view_field > 1) {
      $remove_button = $view_field - 1;
      $form_state->set('num_views', $remove_button);
    }

    // Since our buildForm() method relies on the value of 'num_names' to
    // generate 'name' form elements, we have to tell the form to rebuild. If we
    // don't do this, the form builder will not call buildForm().
    $form_state->setRebuild();
  }



/*
  public static function getView($config) {
    if (isset($config['view_id']) && !empty($config['view_id'])) {
      $target_id = isset($config['view_id'][0]['target_id']) && !empty($config['view_id'][0]['target_id']) ? $config['view_id'][0]['target_id'] : NULL;
      $view_insert = $target_id ? View::load($target_id) : NULL; //loadByName($field['entity_type'], $field['bundle'], $field['field_name']);
      if ($view_insert instanceof View) {
        return $view_insert;
        //$view_data = $view_insert->toArray();
      }
    } 
    //return $view_data;
  }
*/

/*
  public static function getDisplays($displays) {
    $options = [];
    foreach ($displays as $display_id => $display) {
      $options[$display_id] = $display['display_title'];
    }
    return $options;
  }
*/

  public static function provideViewDisplays($form, FormStateInterface $form_state) {

    /*
    $values = $form_state->getValues();
    $views = $values['settings']['block_views']['block_view']; //['block_view_delta'];
    foreach ($views as $delta => $view) {
  
    }
    */

    return $form['settings']['block_views']['block_view'];
  }


/*
  public static function __provideViewDisplays($form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    if (isset($values['settings']['block_views']) && isset($values['settings']['block_views']) && !empty($values['settings']['block_views']['block_view'])) {
      $object = [];
      $num_views = $form_state->get('num_views');
      
      foreach ($values['settings']['block_views']['block_view'] as $index => $view) {
                       
         
        //$view_id = $values['settings']['block_view']['view_id'][0]['target_id'];
        if (isset($view['view_id']) && !empty($view['view_id']) && ($index == $num_views - 1)) {
         
         //foreach ($view['view_reference'] as $view_id) {
             $object[$index] = View::load($view['view_reference']);
             if ($object[$index] instanceof View) { 
               $view_data = $object[$index]->toArray();
              
               $displays = self::getDisplays($view_data['display']);
               $form['block_views']['block_view'][$index]['block_view_display_reference']['#type'] = 'select';
               $form['block_views']['block_view'][$index]['block_view_display_reference']['#options'] = $displays; //[0 => 'test', 1 => 'tost'];
               $form['block_views']['block_view'][$index]['block_view_display_reference']['#title'] = 'View display label'; 
               $form_state->setRebuild();
               //return $form['block_views']['block_view'][$index]; //['block_view_display_reference'];
             }  

          //}

        }
      }
     
    }

    return $form['block_views']['block_view'];

  }
*/

  public static function provideMoreViews($form, FormStateInterface $form_state) { 
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function build() {
 
    $config = $this->getConfiguration();
    $nk_tools_config = \Drupal::config('nk_tools.settings');
    $block_label = isset($config['block_label']) && !empty($config['block_label'])  ? $config['block_label'] : '';
    $route = \Drupal::service('current_route_match');
    $route_params = $route->getParameters()->all();

   
    $build = [
      '#theme' => 'nk_tools_async_view_block',
      '#attributes' => [
        'class' => [
        ],
      ],
    ];

    $view_ids = isset($config['view_id']) && !empty($config['view_id']) ? $config['view_id'] : [];
    $view_displays = isset($config['display']) && !empty($config['display']) && isset($config['display']['display_id']) ? $config['display']['display_id'] : [];
    $view_args = isset($config['display']) && !empty($config['display']) && isset($config['display']['argument']) ? $config['display']['argument'] : [];
    $view_filters = isset($config['display']) && !empty($config['display']) && isset($config['display']['filter']) ? $config['display']['filter'] : [];

    if (!empty($view_ids)) {

      //$nk_tools_factory = \Drupal::service('nk_tools.main_service');

      // Generate unique id/key for this block
      $hash = Crypt::hashBase64($config['id'] . '__' . implode('__', $view_ids));

      $use_rendered = $config['rendered_view'] && !empty($config['rendered_view_wrapper']) ? $config['rendered_view_wrapper'] : NULL;
 
      $build['#attached']['drupalSettings']['nk_tools']['asyncBlocks'] = [];
      
      foreach ($view_ids as $delta => $view_id) {

        //$view = View::load($view_id); //
        //$view_display = $view->getDisplay($view_displays[$delta]);

        //$argument = $view_display['display_options']['arguments']['diplo_daterange_tabs']; 
 
        // Generate unique id/key for this block settings
        //$settings_hash = Crypt::hashBase64($config['label'] . $view_id . $block_label);
        $build['#attached']['drupalSettings']['nk_tools']['asyncBlocks'][$hash] = [];

        // A View pger 
        //$pager = isset($view_display['display_options']['pager']) && !empty($view_display['display_options']['pager']['type']) ? $view_display['display_options']['pager']['type'] : 'none'; 
 
        $build['#attached']['drupalSettings']['nk_tools']['asyncBlocks'][$hash][$delta] = [
          'use_rendered' =>  $use_rendered,
          'trigger' => isset($config['view_trigger']) && !empty($config['view_trigger'][$delta]) ? $config['view_trigger'][$delta] : NULL, 
          'once' => TRUE,
          'block_id' => 'async-view-block-' . $hash,
          'additionalClass' => $config['block_additional_class_assign'] && !empty($config['block_additional_class']) ? $config['block_additional_class'] : NULL,
          'animationIn' => isset($config['block_animation_in']) && !empty($config['block_animation_in']) ? $config['block_animation_in'] : NULL,
          'animationOut' => isset($config['block_animation_out']) && !empty($config['block_animation_out']) ? $config['block_animation_out'] : NULL,
          'view' => [
            'pager_element' => 'mini', //$pager, //NULL,
            'view_name' => $config['view_id'][$delta],
            'view_display_id' => isset($view_displays[$delta]) && !empty($view_displays[$delta]) ? $view_displays[$delta] : 'default',
            'view_args' => !empty($view_args) ? $view_args : NULL,
            'view_dom_id' => 'async-view-view-'. $hash, // Note that for usage of existing/rendered view dom it happens in JS since we can't have "future" view_dom_id here 
          ],
        ];

        $config['links'] = []; 
        if (!empty($config['block_show_links']) && $config['block_show_links'][$delta]) {
          $arguments = !empty($config['block_links']) && !empty($config['block_links'][$delta]) ? explode(', ', $config['block_links'][$delta]) : [];
          $labels = !empty($config['block_links_labels']) && !empty($config['block_links_labels'][$delta]) ? explode(', ', $config['block_links_labels'][$delta]) : [];
          if (!empty($arguments)) {
            $links = [];
            foreach ($arguments as $delta => $argument) {
              $links[$delta] = [
                'key' => $argument,
                'label' => isset($labels[$delta]) ? $labels[$delta] : ucfirst($argument),
              ];  
            }
            $current_arg = !empty($route_params['arg_0']) && in_array($route_params['arg_0'], $arguments) ? $route_params['arg_0'] : NULL;
            $config['links'] = $this->renderLinks($links, $config, $current_arg);
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
    
/*
    $config['links'] = []; 
    if (!empty($config['block_show_links']) && $config['block_show_links'][0]) {
      $links = !empty($config['block_links']) && !empty($config['block_links'][0]) ? explode(', ', $config['block_links'][0]) : [];
      if (!empty($links)) {
        $config['links'] = $this->renderLinks($links, $config);
      } 
    }
*/

    $build['#config'] = $config;

    $parent =  parent::build();

    if ((isset($config['animation_in']) && !empty($config['animation_in'])) || (isset($config['animation_out']) && !empty($config['animation_out']))) {
      
      if ($config['additional_class_assign'] && in_array($config['animation_in'], array_values($parent['#attributes']['class']))) {
        $filtered = array_filter($parent['#attributes']['class'], function($var) {
          return $var != $config['animation_in'];
        });
        $parent['#attributes']['class'] = $filtered;
      }
    }

    $build['#attached']['library'][] = 'nk_tools/async_vew';
 
    return $parent + $build;
  }
 

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
  
    parent::blockSubmit($form, $form_state);

    $values = $form_state->getValues();
   
    // Block custom label field
   // $this->configuration['block_label']['value'] = $values['block_label']['value'];
   // $this->configuration['block_label']['format'] = $values['block_label']['format'];

    // Views configuration details
    foreach ($values['block_views']['block_view'] as $delta => $view) {
      
      $this->configuration['view_trigger'][$delta] = $view['view_trigger'];
      $this->configuration['view_id'][$delta] = $view['view_id'];
      $this->configuration['display']['display_id'][$delta] = $view['display']['display_id'];
      $this->configuration['display']['argument'][$delta] = $view['display']['argument'];
      $this->configuration['display']['filter'][$delta] = $view['display']['filter'];

      $this->configuration['rendered_view'][$delta] = $view['rendered_view'];
      $this->configuration['rendered_view_wrapper'][$delta] = $view['rendered_view_wrapper'];
      $this->configuration['block_show_links'][$delta] = $view['block_show_links'];
      $this->configuration['block_links'][$delta] = $view['block_links'];
      $this->configuration['block_links_labels'][$delta] = $view['block_links_labels'];
    }
   
    

    // DOM and Markup configuration details
    $this->configuration['block_hide_mobile'] = $values['block_effects']['block_hide_mobile'];
    $this->configuration['block_hide_init'] = $values['block_effects']['block_hide_init'];

    $this->configuration['block_additional_class'] = $values['block_effects']['block_additional_class'];
    $this->configuration['block_additional_class_assign'] = $values['block_effects']['block_additional_class_assign'];
 
    $this->configuration['block_animation_in'] = $values['block_effects']['block_animations']['block_animation_in'];
    $this->configuration['block_animation_out'] = $values['block_effects']['block_animations']['block_animation_out']; 

  }

  protected function renderLinks(array $links, array $config, $current_arg = NULL) {
  
    $render = [];
    $route = \Drupal::service('current_route_match');
    $route_params = $route->getParameters()->all();
    $arg = $current_arg ? $current_arg : NULL;
    
    if (!$arg && isset($route_params['arg_0'])) {
      $arg = $route_params['arg_0'];  
    }

    foreach ($links as $delta => $link) {
   
      $link_attributes = [
        'id' => 'button-' . $link['key'],
        'data-id' => $link['key'],
        'class' => []
      ];

      if (!empty($config['view_trigger']) && !empty($config['view_trigger'][0])) {
        $link_attributes['class'][] = str_replace('.', '', $config['view_trigger'][0]);
      }
          
      if ($arg) {
        if ($arg == $link['key']) { 
          $link_attributes['class'][] = 'btn-active';
        }
      }
      else {
        if ($delta == 0) {
          $link_attributes['class'][] = 'btn-active';  
        }
      } 

      $route_params['arg_0'] = $link['key'];

      if ($route->getRouteName()) {
        $url = Url::fromRoute($route->getRouteName(), $route_params, ['attributes' => $link_attributes]);
        $render[$delta]['url'] = $url;
      }
      else {
        $render[$delta]['url'] = Url::fromRoute('<current>')->toString(); // Url::fromUserInput()
      }

      $render[$delta]['title'] = Markup::create($link['label'] .'<i class="material-icons hidden fs-085 absolute ml-12">close</i>'); 
      //ksm($render[$delta]);

      $render[$delta]['link'] = $render[$delta]['url'] instanceof Url ? Link::fromTextAndUrl($render[$delta]['title'], $render[$delta]['url']) : NULL;
          
    }
    return $render;   
  
  }

}