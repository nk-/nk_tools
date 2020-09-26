<?php

namespace Drupal\nk_tools\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Menu\MenuParentFormSelectorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Component\Utility\UrlHelper;

use Drupal\views\Entity\View;
use Drupal\user\RoleStorageInterface;
use Drupal\filter\Entity\FilterFormat;


/**
 * Provides settings for nk_tools module.
 */
class NkToolsMainConfigForm extends ConfigFormBase {

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * Menu parent select forme.
   *
   * @var \Drupal\Core\Menu\MenuParentFormSelectorInterface
   */
  protected $menuParentSelector;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;


  /**
   * The module handler.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
   protected $messenger;

  /**
   * Constructs an EuCookieComplianceConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   * @param \Drupal\user\RoleStorageInterface $role_storage
   *   The role storage.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger interface.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PathValidatorInterface $path_validator, RequestContext $request_context, MenuParentFormSelectorInterface $menu_parent_selector, ModuleHandlerInterface $module_handler, MessengerInterface $messenger) {

    parent::__construct($config_factory);

    $this->pathValidator = $path_validator;
    $this->requestContext = $request_context;
    $this->menuParentSelector = $menu_parent_selector;
    $this->moduleHandler = $module_handler;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path.validator'),
      $container->get('router.request_context'),
      $container->get('menu.parent_form_selector'),
      $container->get('module_handler'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nk_tools_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'nk_tools.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('nk_tools.settings');



    /*
    $default_filter_format = filter_default_format();
    $full_html_format = FilterFormat::load('full_html');
    if ($default_filter_format == 'restricted_html' && !empty($full_html_format) && $full_html_format->get('status')) {
      $default_filter_format = 'full_html';
    }
    */
    //  \Drupal::logger('ViewID')->notice('<pre>' . print_r($form_state->getValue('view_id'), 1) .'</pre>');

    if ($form_state->getValue('view_id')) {
      $view_id = $form_state->getValue('view_id');
    }
    else {
      $view_id = isset($config->get('widgets')['search']['view_container']['view_id']) && !empty($config->get('widgets')['search']['view_container']['view_id']) ? $config->get('widgets')['search']['view_container']['view_id'] : NULL;  
    }

    $view = $view_id ? View::load($view_id) : NULL;

    $form['layout'] = [
      '#type' => 'details',
      '#title' => $this->t('Layout and HTML/CSS'),
      '#description' => $this->t('Layout and HTML/CSS related settings.'),
      '#open' => TRUE,
    ];

    $form['layout']['desktop_only_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Desktop only class'),
      '#description' => $this->t('A CSS class for elements visible only on desktop screen size. Enter without leading dot.'),
      '#default_value' => $config->get('layout')['desktop_only_class'],
    ];

    $form['layout']['mobile_only_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mobile only class'),
      '#description' => $this->t('A CSS class for elements visible only on mobile or very small screen size, width. Enter without leading dot.'),
      '#default_value' => $config->get('layout')['mobile_only_class'],
    ];
    $form['layout']['hidden_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hidden class'),
      '#description' => $this->t('A CSS class for elements that are hidden, initially or permanently'),
      '#default_value' => $config->get('layout')['hidden_class'],  
    ];
    $form['layout']['animate_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Animation class'),
      '#description' => $this->t('A main CSS class for animations. For example we use "animated" which is integrated and supported here. With that we can combine in/out transitions from the list at <a href="https://daneden.github.io/animate.css/" target="blank_">Animate.css</a>.'),
      '#default_value' => $config->get('layout')['animate_class'],  
    ];

    $form['layout']['page_title'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Hide page title on paths'),
      '#description' => $this->t('Enter a list of paths where we do not want to list/show page title. One path per line and with leading slash. Class set is "visually-hidden" which is a default Drupal\'s class for elements that are invisible on page but rendered and fully standardised and accessible for search bots etc. SEO friendly.'),
      '#default_value' => $config->get('layout')['page_title'],  
    ];

    $form['layout']['trim_text'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('String trimming defaults'),
      '#description' => $this->t('Set some default values for text/markup trimming. You can use it anywhere in the code by calling like <pre>$trimmed_string = NkToolsBase::trimMarkup($markup_to_trim, $override_this_very_params);</pre> Obviously if you do not provide $override_this_very_params values set above will apply.'),
      '#attributes' => [
        'class' => [
          'widget-next'
        ],
      ],
    ];

    $form['layout']['trim_text']['trim_max_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Max length'),
      '#description' => $this->t('And integer value for maximum length of trimmed sting/input'),
      '#default_value' => $config->get('layout')['trim_text']['trim_max_length'], 
     ];

    $trim_options = [];
    foreach ($config->get('layout')['trim_text']['trim_options'] as $key => $trim_option) {
      if ($trim_option) {
        $trim_options[$key] = $key; 
      }
    }

    $form['layout']['trim_text']['trim_options'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Options'),
      '#options' => [
         'word_boundary' => $this->t('Word boundary'), 
         'ellipsis' => $this->t('Add ellipsis "..."'),
         'html' => $this->t('Consider HTML/markup'),
      ], 
      '#description' => $this->t('Additional trimming options. It uses Views\'s <em>\Drupal\views\Plugin\views\field\FieldPluginBase::trimText</em> method.'),
      '#default_value' => $trim_options, //$config->get('layout')['trim_text']['trim_options'], 
    ];

    // TODO: Place this into separate form/settings

/*
    $form['widgets'] = [
      '#type' => 'details',
      '#title' => $this->t('Widgets'),
      '#description' => $this->t('Contributed and custom UI elements and widgets to use'),
      '#open' => TRUE, 
    ];

    $form['widgets']['use_search'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Search widget'),
      '#description' => $this->t('A custom widget for search input, with icon'),
      '#default_value' => $config->get('widgets')['use_search'],
      '#attributes' => [
      //  'data-target' => 'widget-next',
       // 'data-class' => 'visually-hidden',
        'id' => 'use-search',
        'class' => [
        //  'trigger',
         // 'widget-trigger'
        ],
      ] 
    ];

    $form['widgets']['search'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Search'),
      '#attributes' => [
        'class' => [
         // 'widget-next',
        ],
      ],
      '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
        'visible' => [
          ':input[id="use-search"]' => ['checked' => TRUE],
        ],
      ],
    ];
*/


/*
    $form['widgets']['search']['icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon'),
      '#description' => $this->t('Usage of <a href="https://material.io/resources/icons/" target="blank_">material icons </a>, can be any from that set.'),
      '#default_value' => $config->get('widgets')['search']['icon'],
    ];

    $form['widgets']['search']['view'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Bind to a View'),
      '#description' => $this->t('Recommended, this way we can use search input as View\'s exposed filter while View itself is providing results.'),
      '#default_value' => $config->get('widgets')['search']['view'],
      '#attributes' => [
        'id' => 'search-use-view',
      ] 
    ];

    $form['widgets']['search']['view_container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'view-container',
      ],
      '#states' => [
        'visible' => [
          ':input[id=search-use-view]' => ['checked' => TRUE],
        ],
      ]
    ];

    $form['widgets']['search']['view_container']['view_id'] = [
      '#title'  => $this->t('View label'),
      '#description' => $this->t('A label of a View to bind search widget to.'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'view',
      '#tags' => FALSE,
      // The #default_value can be either an entity object or an array of entity objects.
      '#default_value' => $view instanceof View ? $view : NULL,
      //'#disabled' => TRUE, 
      //'#multiple' => TRUE,
      '#maxlength' => '256',
      '#ajax' => [
        'event' => 'autocompleteclose',
        'callback' => [get_class($this), 'provideViewDisplays'],
        'wrapper' => 'display-id-container',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Verifying entry...'), 
        ],
      ],

    ];

    // View display select
    $display_options = [];
    $disabled = TRUE;

    if ($view instanceof View) {
      $disabled = FALSE;
      $view_data = $view->toArray(); 
      foreach ($view_data['display'] as $display_id => $display) {
        $display_options[$display_id] = $display['display_title'];
      }
    }

    $form['widgets']['search']['view_container']['display'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'display-id-container',
      ],
    ];

    $form['widgets']['search']['view_container']['display']['display_id'] = [
      '#type' => 'select',
      '#title' => $this->t('View display name'),
      '#description' => $this->t('Display name of a View that we are binding with search widget.'),
      '#disabled' => $disabled,
      '#options' => $display_options,
      '#empty_option' => $this->t('- Choose -'),
      '#default_value' =>  $config->get('widgets')['search']['view_container']['display']['display_id'], //$default_display, //$config->get('display_id_reference'),
    ]; 

    $form['widgets']['search']['view_container']['view_filter'] = [
      '#type' => 'textfield',
      '#title'  => $this->t('View\'s exposed Filter identifier'),
      '#description' => $this->t('A machine name of Fulltext search exposed View filter, aka "Filter identifier" on exposed filter setup'),
      '#default_value' =>  $config->get('widgets')['search']['view_container']['view_filter'],
    ];  

     // Inject piece of a config from nk_tools_menu featured submodule   
    if (\Drupal::moduleHandler()->moduleExists('nk_tools_menu')) {

      $form['widgets']['search']['menu'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use as menu item'),
        '#description' => $this->t('This way search widget can be used as menu item, note that such still must be created manually as an item within a chosen menu <a href="/admin/structure/menu">here</a> or within a View configuration.'),
        '#default_value' => $config->get('widgets')['search']['menu'],
        '#attributes' => [
        // 'data-target' => 'widget-next',
        // 'data-class' => 'visually-hidden',
        'id' => 'use-menu',
          'class' => [
           //'trigger',
          // 'widget-trigger'
         ],
        ] 
      ];

      // Select menu core form
      $form['widgets']['search']['menu_item'] = $this->menuParentSelector->parentSelectElement('main:');
      $form['widgets']['search']['menu_item']['#default_value'] = $config->get('widgets')['search']['menu_item'];

      $form['widgets']['search']['menu_item']['#attributes']['class'][] = 'menu-title-select';
      //$form['widgets']['search']['menu_item']['#prefix'] = '<div class="widget-next">';
      //$form['widgets']['search']['menu_item']['#suffix'] = '</div>';

      $form['widgets']['search']['menu_item']['#states'] = [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
        'visible' => [
          ':input[id=use-menu]' => ['checked' => TRUE],
        ],
      ];

      $form['widgets']['search']['menu_uuid'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Menu link uuid'),
        '#description' => $this->t('A unique uuid for search input as a part of the menu, prevent double programmatic saving of menu link.'),
        '#default_value' => $config->get('widgets')['search']['menu_uuid'],
        '#states' => [
          'visible' => [
            ':input[id=use-menu]' => ['checked' => TRUE],
          ],
        ]
      ];

      $form['widgets']['search']['menu_path'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Menu link path'),
        '#description' => $this->t('In case you do not want to bind search input action to a View you should provide existing internal path here, in <em>/somepath</em> format.'),
        '#default_value' => $config->get('widgets')['search']['menu_path'],
        '#states' => [
          'visible' => [
            ':input[id=use-menu]' => ['checked' => TRUE],
            ':input[id=search-use-view]' => ['checked' => FALSE],
          ],
        ]
      ];


    }
*/

    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('nk_tools.settings')
      // Layout related configuration 
      ->set('layout.desktop_only_class', $form_state->getValue('desktop_only_class'))
      ->set('layout.mobile_only_class', $form_state->getValue('mobile_only_class'))
      ->set('layout.hidden_class', $form_state->getValue('hidden_class'))
      ->set('layout.animate_class', $form_state->getValue('animate_class'))
      ->set('layout.page_title', $form_state->getValue('page_title'))

      ->set('layout.trim_text.trim_max_length', $form_state->getValue('trim_max_length'))
      ->set('layout.trim_text.trim_options.word_boundary', $form_state->getValue('trim_options')['word_boundary'])
      ->set('layout.trim_text.trim_options.ellipsis', $form_state->getValue('trim_options')['ellipsis'])
      ->set('layout.trim_text.trim_options.html', $form_state->getValue('trim_options')['html']);

/*
      ->set('widgets.use_search', $form_state->getValue('use_search'))
      ->set('widgets.search.icon', $form_state->getValue('icon'))

      ->set('widgets.search.view', $form_state->getValue('view'))
      ->set('widgets.search.view_container.view_id', $form_state->getValue('view_id'))
      ->set('widgets.search.view_container.display.display_id', $form_state->getValue('display_id'))

      ->set('widgets.use_animated_sheet', $form_state->getValue('use_animated_sheet'))
      ->set('widgets.animated_sheet.icon', $form_state->getValue('close_icon'));
*/

      // Inject piece of a config from nk_tools_menu featured submodule   
/*
      if (\Drupal::moduleHandler()->moduleExists('nk_tools_menu')) {

        $use_menu = $form_state->getValue('menu');
        $uuid = $form_state->getValue('menu_uuid'); //'bb5355e3-2cc5-4aa8-b30b-747d09959c5a';
        $menu_name = $form_state->getValue('menu_item') ? str_replace(':', '', $form_state->getValue('menu_item')) : NULL;
        $menu_link_storage = \Drupal::service('entity_type.manager')->getStorage('menu_link_content');

        $existing = $menu_link_storage->loadByProperties(['uuid' => $uuid]);
        $menu_link_entity = is_array($existing) ? reset($existing) : NULL;

        $menu_fallback_path = $form_state->getValue('menu_path');
        $this->config('nk_tools.settings')->set('widgets.search.menu', $use_menu);
        $this->config('nk_tools.settings')->set('widgets.search.menu_item', $form_state->getValue('menu_item'));
        $this->config('nk_tools.settings')->set('widgets.search.menu_uuid', $uuid);
        $this->config('nk_tools.settings')->set('widgets.search.menu_path', $menu_fallback_path);

        if ($use_menu) {
          if ($menu_name) {
            if ($form_state->getValue('view') && $form_state->getValue('view_id') && !empty($form_state->getValue('view_id')) && $form_state->getValue('display_id') && !empty($form_state->getValue('display_id'))) {
              $view_id = $form_state->getValue('view_id');
              $display_id = $form_state->getValue('display_id');
              $display = View::load($view_id)->getDisplay($display_id);
              if (is_array($display) && $display['display_plugin'] == 'page') {
                $link = 'internal:/' . $display['display_options']['path'];
              }
              else {
                $link = 'internal:/<nolink>';
              }
            }
            else {
              $link = 'internal:' . $menu_fallback_path;
            }

            if ($menu_link_entity) {
              $menu_link_entity->delete();
              $status = $this->t('Deleted menu link item which was previously auto-generated for search input.');
              $this->messenger->addStatus($status);
            }

            $saved = $menu_link_storage->create([
              'uuid' => $uuid,
              'title' => 'Search',
              'icon' => $form_state->getValue('icon') ? $form_state->getValue('icon') : 'search',
              'link' => ['uri' => $link],
              'menu_name' => $menu_name,
              'weight' => 100,
            ])->save();

            if ($saved) {
              $status = $this->t('A menu link item was appended to Menu <em>@menu_name</em>, check it out <a href="@menu_url">here</a>', ['@menu_name' => $menu_name, '@menu_url' => '/admin/structure/menu/manage/' . $menu_name]);
              $this->messenger->addStatus($status);
            }
          }
        }
        else {
          if ($menu_link_entity) {
            $menu_link_entity->delete();
            $status = $this->t('Deleted menu link item which was previously auto-generated for search input.');
            $this->messenger->addStatus($status);
          }
        }
      }

*/
      $this->config('nk_tools.settings')->save();

      parent::submitForm($form, $form_state);

  } 

/*
  public static function provideViewDisplays($form, FormStateInterface $form_state) {
    return $form['widgets']['search']['view_container']['display'];
  }
*/

}
