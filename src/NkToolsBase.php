<?php

namespace Drupal\nk_tools;


use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Form\FormState;

use Drupal\Core\Entity\EntityInterface;
// use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

use Drupal\Core\Render\Markup;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;
//use Drupal\Core\Render\BubbleableMetadata;
//use Drupal\Core\Image\Image;
use Drupal\Core\Link;
use Drupal\Core\Url;
//use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Plugin\ContextAwarePluginInterface;

use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;
use Drupal\block\Entity\Block;
use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\BlockContentInterface;

use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

use Drupal\views\Views;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Entity\View;
use Drupal\paragraphs\ParagraphInterface;


use Drupal\media\MediaInterface;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;


use Drupal\Component\Utility\Html;
use Drupal\Component\Serialization\Json;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;


/**
 * Class NkToolsBase.
 * A various custom processing   
 */
class NkToolsBase {

 const NK_TOOLS_AJAX_FILTERS = [
    
   'faculties' => [
      'display_id' => ['page'],
      'filters' => [
        'title' => 'Quick find faculty',
        'tumor_type_select' => 'Tumor type'
      ]  
    ],
    'default_search' => [
      'display_id' => ['page_1'],
      'filters' => [
        'type' => 'Type',
      ],
    ],
  ];

  /**
   * Drupal\Core\Entity\EntityTypeManager definition
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Entity related with this block. Can be a Node, Field, a View
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match, EntityFieldManagerInterface $entity_field_manager, RendererInterface $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $this->entityFieldManager = $entity_field_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('entity_field.manager'),
      $container->get('renderer')
    );
  }


  public static function trimMarkup(string $markup, array $trim_params = []) {

    $nk_tools_config = \Drupal::config('nk_tools.settings');

    $default_params = $nk_tools_config->get('layout')['trim_text']['trim_options'];
    $default_params['max_length'] = $nk_tools_config->get('layout')['trim_text']['trim_max_length'];

    /*
    $default_params = [
      'max_length' => 106,
      'word_boundary' => FALSE, //TRUE,
      'ellipsis' => TRUE,
      'html' => TRUE,
    ]; 
    */

    $params = !empty($trim_params) ? array_merge($default_params, $trim_params) : $default_params;
    return FieldPluginBase::trimText($params, $markup);
  }

  public function renderAjaxArgumentsLinks(array $links, array $config, View $view, array $display, array $arguments = [], array $reset_link = []) {

    $render = [];
    $route = \Drupal::service('current_route_match');
    $route_params = $route->getParameters()->all();
        
    foreach ($links as $delta => $link) {

      $link_arguments = $arguments;
      $link_arguments[] = $link['key'];

      $link_attributes = [
        'id' => 'button-' . $link['key'],
        'data-id' => $link['key'],
        'data-order' => $config['argument_order'],
        'data-view-id' => $view->id(),
        'data-display-id' => $display['id'],
        'data-args' => Json::encode($link_arguments),
        'class' => [],
      ];

      if (!empty($config['view_trigger']) && !empty($config['view_trigger'][0])) {
        $link_attributes['class'][] = str_replace('.', '', $config['view_trigger'][0]);
      }
          
      if (!empty($arguments)) {
        if (in_array($link['key'], $arguments)) { 
          $link_attributes['class'][] = 'btn-active';
        }
      }
      else {
        if ($delta == 0) {
          //$link_attributes['class'][] = 'btn-active';  
        }
      } 
   
      if (isset($display['display_options']['path'])) {
        $href = !empty($arguments) ? '/' . $display['display_options']['path'] . '/' . implode('/', $link_arguments) : '/' . $display['display_options']['path'] . '/' . $link['key'];
        $url = Url::fromUserInput($href, ['attributes' => $link_attributes]);
      }
      else {
        
        if (!empty($arguments)) {
          foreach ($arguments as $index => $arg) {
            $route_params['arg_' . $index] = $arg;
          }
        }
        
        if ($route->getRouteName()) {
          if (!empty($arguments)) {
            foreach ($arguments as $index => $arg) {
              $route_params['arg_' . $index] = $arg;
            }
          }
          $url = Url::fromRoute($route->getRouteName(), $route_params, ['attributes' => $link_attributes]);
        }
        else {
          $url = Url::fromRoute('<current>', $route_params, ['attributes' => $link_attributes]);
        }
      }

      $render[$delta]['url'] = $url;
      $render[$delta]['uri'] = $url->toString();
     
      $render[$delta]['title'] = $link['label']; //Markup::create($link['label'] .'<i class="material-icons hidden fs-085 absolute ml-12 mt-4">close</i>'); 
      $render[$delta]['link'] = $render[$delta]['url'] instanceof Url ? Link::fromTextAndUrl($render[$delta]['title'], $render[$delta]['url']) : NULL;
      $render[$delta]['attributes'] = new Attribute($link_attributes);
    }
    
    if (!empty($reset_link) && isset($reset_link['view_id']) && isset($reset_link['display_id']) && isset($reset_link['uri'])) {
      $reset_attributes = [
        'id' => 'button-reset',
        'data-id' => 'reset',
        'data-view-id' => $reset_link['view_id'], //'faculties',
        'data-display-id' => $reset_link['display_id'], //'page', '/faculty'
        'data-args' => isset($reset_link['arguments']) ? Json::encode($reset_link['arguments']) : NULL,
        'class' => isset($reset_link['class']) ? $reset_link['class'] : [], //['async-view-trigger']
      ];

      $reset_url = Url::fromUserInput($reset_link['uri'], ['attributes' => $reset_attributes]);
      $reset_title = isset($reset_link['label']) && !empty($reset_link['label']) ? $reset_link['label'] : t('Reset');
 
       $render['reset'] = [
        'title' => $reset_title,
        'link' => Link::fromTextAndUrl($reset_title, $reset_url),
        'url' => $reset_url,
        'uri' => $reset_url->toString(),
        'attributes' => new Attribute($reset_attributes),
      ];
    }
    
    return $render;   
  
  }

   public function processAjaxViewFilters(&$form, $form_state, array $skip = []) {

    // Search widget binded to a View, as exposed filter  
    $nk_tools_config = \Drupal::config('nk_tools.settings');
    $nk_tools_widgets = $nk_tools_config->get('widgets');
    if (isset($nk_tools_widgets['search']['view_container']) && isset($nk_tools_widgets['search']['view_container']['view_filter'])) {
      $view_filter = !empty($nk_tools_widgets['search']['view_container']['view_filter']) ? $nk_tools_widgets['search']['view_container']['view_filter'] : 'search_api_fulltext';
    }
    else {
      $view_filter = 'search_api_fulltext';
    }
  
    $search_query_key = NULL;

  //  ksm($this->routeMatch);
    
    $route = $this->routeMatch; //\Drupal::service('current_route_match');
    $view = $route->getParameters()->has('view') ? $route->getParameter('view') : NULL;
    $view_id = $route->getParameters()->has('view_id') ? $route->getParameter('view_id') : NULL;
    $current_display = $route->getParameters()->has('display_id') ? $route->getParameter('display_id') : NULL;
    $current_args = [];
    
    $attached = [];

    $include = [];
    
    if ($view_id && $current_display) {
      foreach (static::NK_TOOLS_AJAX_FILTERS as $view_name => $view_data) {
        if ($view_name == $view_id && in_array($current_display, $view_data['display_id'])) {
          $include[$form['#id']] = $view_data['filters'];
        }
      }

      $parameters = $route->getParameters()->all();
      foreach ($parameters as $key => $parameter) {
        if (strpos($key, 'arg_') !== FALSE) {
          $current_args[] = strpos($parameter, ' ') !== FALSE ? str_replace(' ', '+', $parameter) : $parameter;
        }
      }
    }

    if (!empty($include)) {

      $ajax_filters[$form['#id']] = [];

      foreach (Element::children($form) as $element_key) {
        
        $key = $element_key;
        $element = $form[$key];
               
        if (in_array($key, array_keys($include[$form['#id']]))) {

          $ajax_filters[$form['#id']][$key] = $view_id;

          $form[$element_key]['#attributes']['class'][] = 'nk-tools-ajax-filter';
          $form[$element_key]['#attributes']['data-title'] = $include[$form['#id']][$key];

          if ($form[$element_key]['#type'] == 'select') {
            if (isset($form[$element_key]['#options']['All'])) {
              $option_string = $form[$element_key]['#options']['All'] instanceof Markup ? $form[$element_key]['#options']['All']->__toString() : $form[$element_key]['#options']['All'];
              if (empty($option_string) || $option_string == '- Any -') {
                $form[$element_key]['#options']['All'] = isset($form[$element_key]['#attributes']) && isset($form[$element_key]['#attributes']['data-label']) ? $form[$element_key]['#attributes']['data-label'] : 'Filter by ' . strtolower(str_replace('_', ' ', $form[$element_key]['#attributes']['data-title']));
              }
            }

            $options = $form[$element_key]['#options'];

            if (!empty($current_args)) {
              $set_args = [];
              foreach ($current_args as $arg) {
                if (strpos($arg, '+') !== FALSE) {
                  $set_args += explode('+', $arg);
                }
                else {
                  $set_args[] = $arg;
                }
              }
              foreach ($set_args as $set_arg) {
                if (in_array($set_arg, array_keys($options))) { 
                  //$form[$element_key]['#default_value'] = $set_arg;
                }
              } 
            }
          }
          else if ($form[$element_key]['#type'] == 'textfield' || $form[$element_key]['#type'] == 'search') {
            $form[$element_key]['#type'] = 'search';
            $options = ['title' => $form[$element_key]['#default_value']]; 
          }
          
          $form[$element_key]['#attributes']['data-filter'] = $element_key;

   
          $hash = 'gwrwgwrgw';
          $attached[$key] = [
            //'current_path' => $path_array[0],
            'use_rendered' =>  '#main-content .views-element-container',
/*
            'trigger' => '.nk-tools-ajax-filter', 
            'once' => TRUE,
            'block_id' => 'async-view-block-' . $hash,
            'view' => [
              'pager_element' => 'mini', //$pager, //NULL,
              'view_name' => $view_id,
              'view_display_id' => $current_display,
              'view_args' => $current_args,
              'view_dom_id' => 'async-view-view-'. $hash, // Note that for usage of existing/rendered view dom it happens in JS since we can't have "future" view_dom_id here 
            ],
*/
            'append_block' => '.block-page-title-block', // A bit of a hardcode, but no better place for this yet, unless rewriting each of default view filters separately
            'view_id' => $view_id,
            'display_id' => $current_display,
            'set_url' => TRUE,
            'hide_submit' => TRUE,
            'selector' => 'select[name="'. $key .'"]',
            //'button' => $form['actions']['submit']['#id']
          ];
  
          $path =  \Drupal::service('request_stack')->getCurrentRequest()->getPathInfo();
          $path_array = explode('/', $path);
          array_shift($path_array);

          if (isset($path_array[0])) {
            $attached[$key]['current_path'] = $path_array[0]; 
          }

        
          switch ($form[$element_key]['#type']) {

            case 'select':
              $attached[$key]['selector'] = 'select[name="'. $key .'"]';
              $attached[$key]['widget_type'] = 'select'; 
            break;

            case 'search':
              $attached[$key]['selector'] = 'input[type=search]';
              $attached[$key]['widget_type'] = 'search';   
            break;

            case 'textfield':
              $attached[$key]['selector'] = 'input.nk-tools-ajax-filter.form-text';
              $attached[$key]['widget_type'] = 'textfield'; 
            break;
          } 
        }
      }

      if (isset($ajax_filters[$form['#id']]) && !empty($ajax_filters[$form['#id']])) {
        $form['#attributes']['class'][] = 'nk-tools-filters';
      
        //$form['#attached']['library'][] = 'diplo_formatters/fixed';
        //$form['#attached']['library'][] = 'diplo_formatters/views_ajax_filters';

        // A View pager 
        //$pager = isset($view_display['display_options']['pager']) && !empty($view_display['display_options']['pager']['type']) ? $view_display['display_options']['pager']['type'] : 'none'; 
         

        $form['actions']['#attributes']['class'][] = 'visually-hidden'; 
        $form['actions']['#attributes']['class'][] = 'nk-tools-autotrigger';

        $form['#attached']['drupalSettings']['nk_tools']['ajax_filters'] = $attached;
        
        //$form['#attached']['drupalSettings']['nk_tools']['asyncBlocks'][$hash] = array_values($attached);
/*
        $hash = 'gwrwgwrgw';
        $form['#attached']['drupalSettings']['nk_tools']['asyncBlocks'][$attached[$key]][] = [
          'use_rendered' =>  '#main-content .views-element-container',
          'trigger' => '.nk-tools-ajax-filter', 
          'once' => TRUE,
          'block_id' => 'async-view-block-' . $hash,
          'additionalClass' => NULL, //$config['additional_class'],
          'animationIn' => NULL, //$config['animation_in'],
          'animationOut' => NULL, //$config['animation_out'],
          'order' => 1, //is_array($config['argument_order']) && isset($config['argument_order'][$delta]) ? $config['argument_order'][$delta] : NULL,
          'view' => [
            'pager_element' => 'mini', //$pager, //NULL,
            'view_name' => $view_id,
            'view_display_id' => $current_display,
            'view_args' => $current_args,
            'view_dom_id' => 'async-view-view-'. $hash, // Note that for usage of existing/rendered view dom it happens in JS since we can't have "future" view_dom_id here 
          ],
        ];
*/
        //$build['#attached']['library'][] = 'views-autocomplete-filters/drupal.views-autocomplete-filters';
       //$form['#attached']['library'][] = 'nk_tools/async_vew';
        $form['#cache']['contexts'][] = 'url.query_args';

      }
    }
  }

  /**
   * Load node entity
   *
   * @param array $params array of node specific params like ID, bundle etc.
   *
   * @return array|object array of node values or raw node object
   */
  public function getNode($params) {

    // We can load node either by NID or via route
    $node = isset($params['id']) ? $this->entityTypeManager->getStorage('node')->load($params['id']) : $this->routeMatch->getParameter('node');

    if (isset($params['validate'])) {

      // Validate for node/nid type of page 
      if (!$node instanceof NodeInterface) {
        $caller = isset($params['caller']) ? ['@caller' => Markup::create('<em>' . $params['caller'] .'</em>')] : ['@caller' => 'Entity'];
        $message = t('<em>@caller</em> is listed but is empty because it depends on node object and therefore should be configured so and rendered on node pages only.', $caller);
        \Drupal::messenger()->addWarning($message);
        //throw new \UnexpectedValueException("Not a node page");
      }
      else {
        // Validate for node--[type]/nid type of page
        if (isset($params['bundle']) && $node->bundle() !== $params['bundle']) {
          $caller = isset($params['caller']) ? ['@caller' => Markup::create('<em>' . $params['caller'] .'</em>')] : ['@caller' => 'Entity'];
          $message = t('<em>@caller</em> is listed but is empty because it depends on node object and a specific content type <em>@bundle</em>. Therefore it should be configured so and rendered on such pages only.', [
            '@caller' => $caller,
            '@bundle' => $params['bundle']]
          );
          \Drupal::messenger()->addWarning($message);
        }
        else {
          // Return either array of node values or raw node object 
          return isset($params['array']) ? $node->toArray() : $node;
        }
      }
    }

    if ($node instanceof NodeInterface) {
      // Return either array of node values or raw node object 
      return isset($params['array']) ? $node->toArray() : $node;
    }
  }

  /**
   * Load a view with contextual filter.
   *
   * @param string $viewId view's machine name
   * @param string $viewId display's machine name
   * @param array $arguments arguments to pass to a view
   * @param bool $render if true view will be rendered and returned as markup/string
   * @param bool $json if true json string is returned
   * @param array $data some data to return back to a caller
   * 
   * @return string|json rendered view markup or json array
   */
  public function getView($viewId, $displayId, array $arguments = [], $render = NULL, $json = NULL, array &$data = []) {

    $result = NULL;
    $view = Views::getView($viewId);

    if (is_object($view)) {

      $view->setDisplay($displayId);
      $view->setArguments($arguments);
      //$view->setTitle($view->getTitle());
      $data['title'] = $view->getTitle();

      $view->preExecute(); // Very important for JS/ajax settings

      // Render the view; It does execute method too
      // @see \Drupal\views\ViewExecutable::render()
      $render_view = $view->render($displayId);


      if (!empty($view->result)) {
        if ($json && $render) {
          $prepare = $this->renderer->render($render_view);
          $json = $prepare->jsonSerialize();
          $result = json_decode($json);
        }
        else {
          $result = $render ? $this->renderer->render($render_view) : $render_view;
        } 
      }
    }

    return $result;
  }

  /**
   * Load block entity
   *
   * @param string $blockId machine name of a block to load
   * @param bool $render if true returns rendered block markuo, else raw object
   * 
   * @return string|object rendered block markup or raw block object
   */

  public function getBlock($blockId, $block_type, array $config = [],  $render = TRUE) {

    $block = NULL;

    switch ($block_type) {

      case 'content':
        $block_entity = BlockContent::load($blockId);
        if ($block_entity) {
          $block = $render ? $this->entityTypeManager->getViewBuilder('block_content')->view($block_entity) : $block_entity;
        }
      break;

      // Note, in case of "plugin" type of block $blockId is id of custom @Block definition and not a machine name
      case 'plugin':
        $instance_config = isset($config['set_config']) ? $config : [];
        $block_entity = \Drupal::service('plugin.manager.block')->createInstance($blockId, $instance_config); 
       // $block_entities = \Drupal::service('plugin.manager.block')->getInstance(['id' => $blockId]); 

        if ($block_entity) {
          // Set any necessary context
          // In block plugin definition annotation see something that can be like this: context = { "node" = @ContextDefinition("entity:node", label = @Translation("Node") ) }
          $context_mapping = isset($config['context_mapping']) && !empty($config['context_mapping']) ? $config['context_mapping'] : NULL;        
          if ($context_mapping) {
            foreach ($context_mapping as $context_mapping_key => $context_mapping_value) {
              $block_entity->setContextValue($context_mapping_key, $context_mapping_value);
            }
          }
        
          $access_result = $block_entity->access(\Drupal::currentUser());

          if (is_object($access_result) && $access_result->isForbidden() || is_bool($access_result) && !$access_result) {
            $block = [];
          }
          else {
            $block = $render ? $block_entity->build() : $block_entity;
          }
        }
      break;

      case 'config':
        $block_entity = isset($config['block_content']) ? BlockContent::load($blockId) : Block::load($blockId);

        if ($block_entity) {

          if (isset($config['block_content'])) {
            $block = $render ? $this->entityTypeManager->getViewBuilder('block_content')->view($block_entity) : $block_entity;
          }
          else {
            $block = $render ? $this->entityTypeManager->getViewBuilder('block')->view($block_entity) : $block_entity;
          }
        }
      break; 

      case 'default':
        $block_entity = \Drupal::entityTypeManager()->getStorage('block')->load($blockId);
        if (!empty($block_entity)) {
          $block = $render ?  $this->entityTypeManager->getViewBuilder('block')->view($block_entity) : $block_entity;
        }
      break; 

    }

    return $block;

  }

  public static function provideViewDisplays($form_element) {
    return $form_element;
  }

  public function renderTargetUi(array &$build, $config, array $toggle_attributes = [], array $pane_wrapper_attributes = [], string $default_theme = NULL) {
  
    if ($config['target']) { 

      switch ($config['target']) {
       
        // Tabs front end UI
        case 'tabs':
          $build['#theme'] = 'nk_tools_tabs';
          $build['#attached']['library'][] = 'nk_tools/tabs';
        break;
       
        // Collapsible toggle front end UI
        case 'panel':
          $build['#theme'] = 'nk_tools_collapsible_pane';
         
          foreach ($build['#items'] as $delta => &$item) {
            $item = [
              'label' => isset($build['#labels'][$delta]) && !empty($build['#labels'][$delta]) ? $build['#labels'][$delta] : 'Toggle',
              'content' => $item,  
              'target' => isset($config['target_ui_id']) && !empty($config['target_ui_id']) ? 'panel-' . $config['target_ui_id'] . '-' . $delta : 'panel-' . $delta
            ];
          }
         
          $toggle_attributes_default = [ 
            'data-icon' =>  !empty($config['icon']) ? $config['icon'] : NULL,
            'data-icon-back' => !empty($config['icon_back']) ? $config['icon_back'] : NULL,
            'data-target-in' => 'fadeIn',
            'data-target-out' => 'fadeOut', 
            'class' => [
              'text-default-color',
            ]
          ];

          $toggle_attributes = array_merge_recursive($toggle_attributes_default, $toggle_attributes);

          $build['#toggle_attributes'] = new Attribute($toggle_attributes);  
        
          if (!empty($pane_wrapper_attributes)) {
            $build['#pane_wrapper_attributes'] = new Attribute($pane_wrapper_attributes);
          }

        break;

        // "Unformatted", default list of items
        default:
          $build['#theme'] = $default_theme ? $default_theme : 'nk_tools_items';
        break;

      }
    }
    else {
      $build['#theme'] = 'nk_tools_items';
    }

  }

  public function renderViewFilter($view_id, $display_id, $render = FALSE, array &$data = []) {

    static $count = 0;
    $count++;

    $view = Views::getView($view_id);
    $view->setDisplay($display_id);
    $view->initHandlers();
    $form_state = new FormState();

    $values = [
      'view' => $view,
      'display' => $view->display_handler->display,
      'exposed_form_plugin' => $view->display_handler->getPlugin('exposed_form'),
      'method' => 'get',
      'rerender' => TRUE,
      'no_redirect' => TRUE,
      'always_process' => TRUE,
      'nk_tools_search_input_data' => $data
    ]; 

    $form_state->setFormState($values);

    $form_state->setRequestMethod('POST');
    $form_state->setCached(TRUE);

    $form = \Drupal::formBuilder()->buildForm('Drupal\views\Form\ViewsExposedForm', $form_state);   
    $form['#id'] = 'nk-tools-search-widget-' . $count;
    //$form['#attributes']['data-dom-id'] = $view->dom_id;
    
    // We do not want submit button visible here, but we want it operational (JS/Ajax)
    //$form['actions']['#attributes']['class'][] = 'visually-hidden';
   

 
    if (isset($data['#config'])) {
      $form['#config'] = $data['#config'];
    }
/*
    else {
      $form['#config'] = [
        //'view' => [
        //  'view_dom_id' => $view->dom_id,
        //],
        'type' => 'sibling',
        'collapsed' => 0,
        'icon' => NULL,
      ]; 
    }
*/

/*
    ksm($form['#config']);

    // Support for search_api_autocomplete module   
    if (\Drupal::service('module_handler')->moduleExists('search_api_autocomplete') && isset($form['#config']['autocomplete'])) {
    
      $plugin_id = 'views:' . $view_id;
      $search_storage = $this->entityTypeManager->getStorage('search_api_autocomplete_search'); 
      $search = $search_storage->loadBySearchPlugin($plugin_id);
      
      if ($search && $search->getEntityTypeId() == 'search_api_autocomplete_search') {
        search_api_autocomplete_form_views_exposed_form_alter($form, $form_state);
        $form['#config']['search_api_autocomplete'] = TRUE; 
      }
    } 
*/
   

    return $render ? $this->renderer->render($form) : ['form_state' => $form_state, 'form' => $form]; 
  }

  // This can be called in hook_views_pre_view() for instance
  public static function processViewArguments(ViewExecutable $view, $display_id, array $args = [], array $filters, array &$context = []) {

    foreach ($filters as $bundle => $filter) {
      foreach ($filter as $form_name => $data) {
        if ($view->id() == $data['view_id'] && $display_id == $data['display_id']) {

          $path = \Drupal::request()->getPathInfo();
          $path_array = explode('/', $path);
          array_shift($path_array);


          if (count($path_array) > 1 && !empty($args)) {
            $url_string = trim(urldecode($path_array[1]));

            if (strpos($url_string, ',') !== FALSE || strpos($url_string, '+') !== FALSE) {

              $divider = strpos($url_string, ',') !== FALSE ? ',' : '+';
              $type = explode($divider, $url_string);
              $view_title = $view->getTitle();

              // This is comma separated entity IDs 
              if (!empty($type) && is_numeric($type[0])) {
                $args =  $url_string;
                foreach ($type as $nid) {
                  // Query node title with given nid from argument
                  $query = \Drupal::database()->select('node_field_data', 'n');
                  $query->addField('n', 'title');
                  $query->condition('nid', $nid);
                  $result = $query->execute()->fetchObject();
                  if ($result && $result->title) {
                    $context['title_array'][] = $result->title; 
                  } 
                }

                //if (isset($context['title_array']) && !empty($context['title_array'])) {
                //  $view_title .= Markup::create(' <span class="fs-1-25 view-argument subtitle">' . implode(', ', $context['title_array']) . '</span>');
                //  $view->setTitle($view_title);
                //} 
              }

              // These are comma separated entity title (strings)
              else {
                if (!empty($type)) {
                  $args = '';
                  $context['title_array'] = [];

                  foreach ($type as $title) {
                    // Query node nid with given title from argument
                    $query = \Drupal::database()->select('node_field_data', 'n');
                    $query->addField('n', 'nid');
                    $query->condition('title', trim($title));
                    $query->condition('type', $bundle);
                    $result = $query->execute()->fetchObject();
                    if ($result && $result->nid) {
                      $args .= empty($args) ? $result->nid : ',' . $result->nid;
                      $context['title_array'][] = $title; 
                    } 
                  }
                }
              }

           }

           // A single value (argument without comma separated values)
           else {

             if (!empty($url_string)) {

               // Argument can be either entity id or title string
               $add_field = is_numeric($url_string) ? 'title' : 'nid';
               $condition = is_numeric($url_string) ? 'nid' : 'title';

               $query = \Drupal::database()->select('node_field_data', 'n');
               $query->addField('n', $add_field);
               $query->condition($condition, $url_string);
               if (!is_numeric($url_string)) {
                 $query->condition('type', $bundle);
               }
               $result = $query->execute()->fetchObject();
               if ($result && (isset($result->nid) || isset($result->title))) {
                 $args = isset($result->nid) ? $result->nid : $url_string;
                 $context['title_array'] = is_numeric($url_string) && isset($result->title) ? [$result->title] : [$url_string];
               }

             }
           } 
         }

          else {
            $args = 'all';
            //$context['title_array'] = [$view->getTitle()];
          }

          return $args; 
        }
      }
    }
  } 

  public function getMenu(string $menu_name, int $depth = 1, string $sort = 'ASC') {
    $menu_tree = \Drupal::service('menu.link_tree');
    $parameters = new MenuTreeParameters();
    $parameters->setMaxDepth($depth) // How far below the tree we wanna list
    ->onlyEnabledLinks()
    ->excludeRoot();
    $tree = $menu_tree->load($menu_name, $parameters);
    $manipulators = [['callable' => 'menu.default_tree_manipulators::checkAccess']];
    $tree = $menu_tree->transform($tree, $manipulators);

    // ASC is a default sorting as the menu tree shows up in interface, smaller delta first
    $this->sortMenuLinks($tree, $sort);

    return $tree;
  }

  protected function sortMenuLinks(array &$tree, string $sort = 'ASC') {
    if ($sort == 'ASC') {
      usort($tree, function($a, $b) {
        return $a->link->getWeight() > $b->link->getWeight();
      });
    }
    else {
       usort($tree, function($a, $b) {
        return $a->link->getWeight() < $b->link->getWeight();
      });
    }
  }

  public function exposedQuery($filter, $bundle, $properties) {

    // Query nodes - should be lighter than loading full o
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->addField('n', 'title');
    $query->addField('n', 'nid');
    $query->condition('type', $bundle);
    if (!isset($properties['unpublished'])) {
      $query->condition('status', NodeInterface::PUBLISHED);
    }

    $results = $query->execute()->fetchAll();

    if (!empty($results)) {

      // Start building out the options for our select list
      $options = [];
      //$nodes = $storage->loadMultiple($nids);
 
      $arguments = [];
      // Push titles into select list
      foreach ($results as $result) {
        $group_by = $properties['group_by'] == 'title' ? $result->title : $result->nid;
        $options[$group_by] = $result->title;
      }
 
      // Sort ascending
      asort($options);

      // Modify our new form element
      $filter['#type'] = 'select';
      $filter['#multiple'] = FALSE;
 
      // Specify the empty option for our select list
      $filter['#empty_option'] = isset($properties['reset_value']) ? $properties['reset_value'] : '- Any -';
 
      // Add the $options from above to our select list
      $filter['#options'] = $options;

      unset($filter['#size']);
 
      return $filter;
    }
  
  }


  /**
   * List Views' exposed forms filters that we turn into select list (from textfield)
   */
  public function exposedWidgets(array &$form, array $selects) { 
    
    //$selects = static::EXPOSED_WIDGETS;
  
    foreach ($selects as $bundle => $select) {
  

      if (in_array($form['#id'], array_keys($select))) {

        // Filter identifier in a View should ideally have the machine name or bundle of items as <select> options
        $filter = $bundle;
     
        // A bit of a "hack" but for good, to secure further name of the views filter input (exposed Filter identifier)
        $default_elements = ['actions', 'form_id', 'form_build_id'];
        if (!isset($form[$filter])) {
          foreach (Element::children($form) as $field_name) {
            if (!in_array($field_name, $default_elements)) {
              $filter = $field_name;
            }
          } 
        }
      
        $form['#attached']['drupalSettings']['diplo_forms'] = ['filters' => []];

        foreach ($select as $id => $properties) {
          
           if ($form['#id'] == $id && isset($form[$filter]) && $elements = $this->exposedQuery($form[$filter], $bundle, $properties)) {

              $form[$filter] = $elements;

              // Hide submit button
              if ($properties['hide_submit']) {
                $form['actions']['#attributes']['class'][] = 'visually-hidden';
              }

              //$form['issue']['#attributes']['onchange'] = 'this.form.submit();';
              $button = $form['actions']['submit']['#id'];

              $form['#attached']['drupalSettings']['diplo_forms']['filters'][$filter] = $properties;
              $form['#attached']['drupalSettings']['diplo_forms']['filters'][$filter]['form_id'] = $id;
              // $form['#attached']['drupalSettings']['diplo_forms']['filters'][$filter]['target_id']
              $form['#attached']['drupalSettings']['diplo_forms']['filters'][$filter]['button'] = $button;

              $path = \Drupal::request()->getPathInfo();
              $path_array = explode('/', $path);
              array_shift($path_array);

              $form['#attached']['drupalSettings']['diplo_forms']['filters'][$filter]['current_path'] = $path_array[0];
              if (count($path_array) > 1) {
                $form['#attached']['drupalSettings']['diplo_forms']['filters'][$filter]['argument_title'] = urldecode($path_array[1]);
                $form['#attached']['drupalSettings']['diplo_forms']['filters'][$filter]['argument'] = array_search(urldecode($path_array[1]), $form[$filter]['#options'], TRUE);
              }
      
              $form['#attached']['library'][] = 'diplo_forms/ajax_callbacks';
              // \Drupal::logger('Updates')->notice('<pre>' .print_r($form['issue'], 1) .'</pre>');
              
              if (isset($properties['baskets'])) {
                $baskets = $this->exposedQuery($form[$filter], 'baskets', $properties);
                $form['baskets'] = $baskets;
              } 
              
              //return $form;

           }

        }

        return $form;     
 
     }

    }
    return FALSE;
  }


  public function getEntityFields(string $entity_type, string $bundle, bool $options = FALSE, string $field_name = '') {
    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
    $field_list = [];
    if (!empty($field_name)) {
      $field_list = [
        'id' => $field_name,
        'label' => $field_definition->getLabel(),
        'type' => $field_definition->getType(),
      ];
    }
    else {
      foreach ($fields as $field_name => $field_definition) {
        if (!empty($field_definition->getTargetBundle())) { // && $field_definition->getFieldStorageDefinition()->isBaseField() == FALSE) {               
          if ($options) {
            $field_list[$field_name] = $field_definition->getLabel();
          }
          else {
            $field_list[$field_name] = $field_definition->toArray();
/*            $field_list[$field_name] = [
              'id' => $field_name,
              'label' => $field_definition->getLabel(),
              'type' => $field_definition->getType(), 
            ]; */
          }
        }
      }
    }
    return $field_list;   
  }

  public function nodeBanners(array $fields, array $config) { 
    //'field_banner_source',
   // 'field_view_reference'.

    if (!empty($fields) && $fields[0] instanceof FieldConfig) { 

      $node = $config['node'];

      $field_name = $fields[0]->getName();
      if ($node->hasField($field_name)) { 
         // Here is possibility to override default banner for each node. Simply if image was uploaded on the node form
        $banner_image = $node->get($field_name)->getValue();

        $file_storage = $this->entityTypeManager->getStorage('file');
        $fid = !empty($banner_image) && isset($banner_image[0]['target_id']) ? $banner_image[0]['target_id'] : NULL; 

        if (!$fid) {
          $field_params = [
            'image_preset' => $config['field_banner_image_style'], 
            'alt' => $node->getTitle(),
            'title' => $node->getTitle(),
            'attributes' => [
              'height' => 'auto',  
              'width' => '100%',
              'class' => ['banner-image']
            ],
          ];

          if ($config['field_lazy_load']) {
            $field_params['attributes']['data-lazy'] = TRUE; 
            $field_params['attributes']['data-sizes'] = 'auto'; 
          }

          $default_images = $this->renderFileField('node', $node, $field_name, $field_params, [], TRUE); 
          //$images = $this->renderFileField('node', $node, $field_name, $field_params, $fids, TRUE); 
          $files = $file_storage->loadByProperties(['uri' => $default_images['#uri']]);
          //$image = isset($images[0]) && !empty($images[0]) ? $images[0] : $images;
          $file = reset($files);
          $file_title = $default_images['#title'];
          $file_alt = $default_images['#alt'];
        }
        else if ($fid) {
          $file = $file_storage->load($fid);
          $file_title = $node->get($field_name)->first()->get('title')->getValue();
          $file_alt = $node->get($field_name)->first()->get('alt')->getValue();
        }

        $image_style_storage = $this->entityTypeManager->getStorage('image_style');  
        $image_style = isset($config['field_banner_image_style']) && !empty($config['field_banner_image_style']) ? $image_style_storage->load($config['field_banner_image_style']) : NULL;  

        return [
          'file' => $file,
          'file_data' => [
            'image_style' => $image_style,
            'mimetype' => $file->getMimeType(),
            'title' => $file_title,
            'alt' => $file_alt,
           ],
           'url' => isset($default_images['#uri']) && !empty($default_images['#uri']) ? file_create_url($default_images['#uri']) : file_create_url($file->getFileUri()),
         ]; 
       } 
    }
  }


  private function paragraphBanners(string $paragraph_field, array $paragraph_fields) {
    $route = \Drupal::service('current_route_match');
    $route_array = strpos($route->getRouteName(), '.') !== FALSE ? explode('.', $route->getRouteName()) : [];
    if (count($route_array) > 2 && $route_array[0] == 'view') {
      $block_content = $this->getBlock(14, 'content', [], FALSE); //'c0752d16-e4d3-4048-991e-ed01f3ab9724'
      //$block_builder = \Drupal::service('entity_type.manager')->getViewBuilder('block_content');
      if ($block_content->hasField($paragraph_field) && !empty($block_content->get($paragraph_field)->getValue())) {
        $paragraph_storage = \Drupal::service('entity_type.manager')->getStorage('paragraph');
        $file_storage = \Drupal::service('entity_type.manager')->getStorage('file');
        $image_style_storage = \Drupal::service('entity_type.manager')->getStorage('image_style');  
        foreach ($block_content->get($paragraph_field)->getValue() as $delta => $value) {
          if (isset($value['target_id']) && !empty($value['target_id'])) {
            $paragraph = $paragraph_storage->load($value['target_id']);
            if (isset($paragraph_fields['source']) && isset($paragraph_fields['view'])) {
              if ($paragraph->hasField($paragraph_fields['view']) && $paragraph->hasField($paragraph_fields['source']) && !empty($paragraph->get($paragraph_fields['view'])->getValue()) && !empty($paragraph->get($paragraph_fields['source'])->getValue())) {
                $view_reference = $paragraph->get($paragraph_fields['view'])->getValue()[0];
                if ($view_reference['target_id'] == $route_array[1] && $view_reference['display_id'] == $route_array[2]) {
                  $banner_reference = $paragraph->get($paragraph_fields['source'])->getValue()[0]['target_id'];

                  $file_title = $paragraph->get($paragraph_fields['source'])->first()->get('title')->getValue();
                  $file_alt = $paragraph->get($paragraph_fields['source'])->first()->get('alt')->getValue();
                  $image_style = $paragraph->hasField($paragraph_fields['image_style']) ? $image_style_storage->load($paragraph->get($paragraph_fields['image_style'])->getValue()[0]['target_id']) : NULL; 

                  $file = $file_storage->load($banner_reference);
                  return [
                    'block_content' => $block_content,
                    'paragraph' => $paragraph,
                    'file' => $file,
                    'file_data' => [
                      'image_style' => $image_style,
                      'mimetype' => $file->getMimeType(),
                      'title' => $file_title,
                      'alt' => $file_alt,
                    ],
                    'url' => file_create_url($file->getFileUri()),
                  ]; 
                }
              }    
            }
          }
        }
      }
    }
  }

  public function banners(string $type = 'node', string $field, array $subfields, array $build = [], bool $access = FALSE) {

    $route = \Drupal::service('current_route_match');
    $add_class = NULL;
    if ($type == 'node') {

      $banner = $this->nodeBanners($subfields, $build);
      $prev_element = isset($build['field_fixed_element_selector']) && !empty($build['field_fixed_element_selector']) ? $build['field_fixed_element_selector'] : NULL;
      $css_bg = isset($build['field_css_background']) && $build['field_css_background'] ? $build['field_css_background'] : NULL;
      $selector = '#before-main'; // . str_replace('_', '-', $build['#block']->getRegion());
      $top_offset = isset($build['field_top_offset']) && !empty($build['field_top_offset']) ? $build['field_top_offset'] : NULL;
      $id = str_replace('_', '-', $build['id']);
      $add_class = 'banner-' . str_replace('_', '-', $build['node']->getType());
    }

    else if ($type == 'view') {

      $banner = $this->paragraphBanners($field, $subfields);

      if ($access) {
        return $banner;
      }


      $block_content = isset($banner['block_content']) && $banner['block_content'] instanceof BlockContentInterface ? $banner['block_content'] : NULL;   
      $id = !empty($build) && isset($build['#id']) ? str_replace('_', '-', $build['#id']) : 'nk-tools-views-banners';
      $add_class = 'banner-views';

      $prev_element = $block_content && $block_content->hasField('field_fixed_element_selector') && !empty($block_content->get('field_fixed_element_selector')->getValue()) ? $block_content->get('field_fixed_element_selector')->getValue()[0]['value'] : NULL;
      $css_bg = $block_content && $block_content->hasField('field_css_background') && !empty($block_content->get('field_css_background')->getValue()) ? $block_content->get('field_css_background')->getValue()[0]['value'] : NULL;
      $selector = !empty($build) && isset($build['#block']) ? '#' . str_replace('_', '-', $build['#block']->getRegion()) : '#before-main';
      $top_offset = $block_content && $block_content->hasField('field_top_offset') && !empty($block_content->get('field_top_offset')->getValue()) ? $block_content->get('field_top_offset')->getValue()[0]['value'] : 0;
      //$image_style = isset($banner['file_data']['image_style']) && !empty($banner['file_data']['image_style']) ? $banner['file_data']['image_style'] : NULL;

      /*
        // $svg = $block_content && $block_content->hasField('field_svg_source') && !empty($block_content->get('field_svg_source')->getValue()) ? $block_content->get('field_svg_source')->getValue()[0]['value'] : NULL;
        $svg = isset($banner['file_data']['mimetype']) && !empty($banner['file_data']['mimetype']) && strpos($banner['file_data']['mimetype'], 'svg') !== FALSE ? TRUE : NULL;

        if ($svg) {
          $svg_data = file_get_contents($banner['url']); //DRUPAL_ROOT . '/' . $path);
          $crawler = new Crawler($svg_data);
          $svg_properties = $crawler->attr('viewBox');
          list($x, $y, $width, $height) = explode(' ', $svg_properties);
          $build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id]['ratio'] = $width / $height;
          $build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id]['width'] = $width;
          $build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id]['height'] = $height;
          $build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id]['svg'] = $svg;
        }
        else {
          if ($image_style) {
            foreach ($image_style->getEffects() as $effect_uuid => $effect) {
              if (!empty($effect->getConfiguration()['data']) && (isset($effect->getConfiguration()['data']['width']) || isset($effect->getConfiguration()['data']['height']))) {  
                //$build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id] = $effect->getConfiguration()['data'];
                $build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id]['ratio'] = $effect->getConfiguration()['data']['width'] / $effect->getConfiguration()['data']['height'];
                $build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id]['width'] = $effect->getConfiguration()['data']['width'];
                $build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id]['height'] = $effect->getConfiguration()['data']['height'];
                //$build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id]['uri'] = $image_style->buildUri($file_uri);
                //$build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id]['url'] = $image_style->buildUrl($file_uri); 
              }
            }

         }
         else {
           $image_factory = \Drupal::service('image.factory')->get($banner['file']->getFileUri());
           $build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id]['ratio'] = $image_factory->getWidth() / $image_factory->getHeight();
           $build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id]['width'] = $image_factory->getWidth();
           $build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id]['height'] = $image_factory->getHeight();
         }
      }
*/
    }

    if (is_array($banner) && !empty($banner)) {

      $image_style = isset($banner['file_data']['image_style']) && !empty($banner['file_data']['image_style']) ? $banner['file_data']['image_style'] : NULL;

      $build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id] =[
        'region' => $selector,
        'uri' => $image_style ? $image_style->buildUri($banner['file']->getFileUri()) : $banner['file']->getFileUri(),
        'url' => $image_style ? $image_style->buildUrl($banner['file']->getFileUri()) : file_create_url($banner['file']->getFileUri()),
        'block' => '#block-' . $id,
        'prev_element' => $prev_element,
        'config' => isset($build['#configuration']) ? $build['#configuration'] : [],
        'top_offset' => $top_offset,
      ];

      // $svg = $block_content && $block_content->hasField('field_svg_source') && !empty($block_content->get('field_svg_source')->getValue()) ? $block_content->get('field_svg_source')->getValue()[0]['value'] : NULL;
      $svg = isset($banner['file_data']['mimetype']) && !empty($banner['file_data']['mimetype']) && strpos($banner['file_data']['mimetype'], 'svg') !== FALSE ? TRUE : NULL;
      $image_style = isset($banner['file_data']['image_style']) && !empty($banner['file_data']['image_style']) ? $banner['file_data']['image_style'] : NULL;

      if ($svg) {
        $svg_data = @file_get_contents($banner['url']); //DRUPAL_ROOT . '/' . $path);
        if ($svg_data) {
          $crawler = new Crawler($svg_data);
          //$svg_properties = $crawler->attr('viewBox');
          if ($crawler && $crawler->filter('svg') instanceof Crawler) {
            $width = !empty($crawler->filter('svg')->extract('width')) ? $crawler->filter('svg')->extract('width')[0] : NULL; 
            //$crawler->attr('width'); //$crawler->filter('svg')->attr('width');
            // $height = $crawler->attr('height'); //$crawler->filter('svg')->attr('height');
            $height = !empty($crawler->filter('svg')->extract('height')) ? $crawler->filter('svg')->extract('height')[0] : NULL;

            if ($width && $height) {
              //list($x, $y, $width, $height) = explode(' ', $svg_properties);
               $build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id]['ratio'] = $width / $height;
               $build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id]['width'] = $width;
               $build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id]['height'] = $height;
            }

          } 
        }
        $build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id]['svg'] = $svg;
        $build['#configuration']['is_svg'] = TRUE;
      }
      else {
        if ($image_style) {
          foreach ($image_style->getEffects() as $effect_uuid => $effect) {
            if (!empty($effect->getConfiguration()['data']) && (isset($effect->getConfiguration()['data']['width']) || isset($effect->getConfiguration()['data']['height']))) {  
              //$build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id] = $effect->getConfiguration()['data'];
              $build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id]['ratio'] = $effect->getConfiguration()['data']['width'] / $effect->getConfiguration()['data']['height'];
              $build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id]['width'] = $effect->getConfiguration()['data']['width'];
              $build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id]['height'] = $effect->getConfiguration()['data']['height'];
              //$build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id]['uri'] = $image_style->buildUri($file_uri);
              //$build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id]['url'] = $image_style->buildUrl($file_uri); 
            }
          }
        }
        else {
          $image_factory = \Drupal::service('image.factory')->get($banner['file']->getFileUri());
          $build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id]['ratio'] = $image_factory->getWidth() / $image_factory->getHeight();
          $build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id]['width'] = $image_factory->getWidth();
          $build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id]['height'] = $image_factory->getHeight();
        }
      }

      $bg_class = isset($banner['file_data']['alt']) && !empty($banner['file_data']['alt']) ? strtolower(str_replace(' ' , '-', $banner['file_data']['alt'])) : strtolower(str_replace(' ' , '-', $banner['file_data']['title']));

      $build['#attributes']['class'][] = $bg_class;
      if ($add_class) {
        $build['#attributes']['class'][] = $add_class;
      }

      if ($css_bg) {
        $build['#configuration']['bg_image'] = $banner['url'];
        // Set CSS background attributes to our file
        $build['#attributes']['style'] = 'background-image: url(' . $banner['url'] . ')';
        $build['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$id]['css_bg'] = TRUE;
      }

      // Attach our library now - it does auto-calculate image's container based on image data given here
      $build['#attached']['library'][] = 'nk_tools/nk_tools_factory_banner';

    }

    //ksm($build['#attached']);
    return $build;

  }


  public static function viewsBanner(&$variables, $route_array, $skip_frontpage = FALSE) { 

    $front_page = \Drupal::service('path.matcher')->isFrontPage(); 

    // Make sure cache does not interfere, this may not be necessary eventually
    $variables['#cache']['contexts'][] = 'url.path';
    $variables['#cache']['contexts'][] = 'url.query_args';

/*
    // Check current path and if it's a View get its machine name from route
    $view_banner = NULL;
    $route = \Drupal::routeMatch()->getRouteName();
    $route_array = explode('.', $route);
    if (count($route_array) && $route_array[0] == 'view') {
      if ($skip_frontpage) {
        if (!empty($route_array[1]) && !$front_page) {
          $view_banner =  $route_array[1];
        }
      }
      else {
        if (!empty($route_array[1])) {
          $view_banner =  $route_array[1];   
        } 
      }
    }
*/

    if (isset($route_array[1]) && !empty($route_array[1]) && isset($variables['content']) && !empty($variables['content']) && isset($variables['content']['field_banners'])) {

      $config = [];

      foreach (Element::children($variables['content']) as $field_id) {
        $config[$field_id] = [];
        if (is_object($variables['content'][$field_id]['#items']) && !empty($variables['content'][$field_id]['#items']->getValue())) {
          if (count($variables['content'][$field_id]['#items']->getValue()) > 1) {
            $config[$field_id] = $variables['content'][$field_id]['#items']->getValue();
          }
          else {
            $config[$field_id] = $variables['content'][$field_id]['#items']->getValue()[0];
          }
        }
      }

      $selector = $config['field_fixed_element_selector']['value']; // $variables['content']['field_fixed_element_selector']['#items']->getValue()[0]['value'];

      $variables['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$selector] = []; 

      if (isset($config['field_banners']) && !empty($config['field_banners'][0])) {

        $banner_variables = [];

        foreach ($config['field_banners'] as $delta => $banner_image) {

          $file = File::load($banner_image['target_id']);     
          $view = isset($config['field_views_reference']) && !empty($config['field_views_reference'][$delta]) ? $config['field_views_reference'][$delta]['target_id'] : NULL;
          $view_display = $view && !empty($config['field_views_reference'][$delta]['display_id']) ? $config['field_views_reference'][$delta]['display_id'] : NULL;

          if ($file && !empty($route_array[2]) && $view_display == $route_array[2]) {

            $image_style = $front_page ? NULL : $variables['content']['field_banners'][$delta]['#image_style'];
            $banner_variables[$delta] = self::setBanner($file->getFileUri(), $image_style); 

            // First assign the right  content/image, according to image extra fields                
            $variables['content']['field_banners'][0] = $variables['content']['field_banners'][$delta];  

            // Now enrich JS (drupalSettings) with a set of handy variables
            $variables['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$selector] = $banner_variables[$delta];

            // Provide entire block config/field values for JS (drupalSettings)
            $variables['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$selector]['config'] = $config;

            $variables['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$selector]['block'] = '#block-' . str_replace('_', '-', $variables['elements']['#id']);

            // Set URL for CSS background-image (if opted so on block config)
            if (isset($variables['content']['field_css_background']['#items']) && !empty($variables['content']['field_css_background']['#items']->getValue())) {
              $variables['bg_image'] = isset($banner_variables[$delta]['style_data']) && !empty($banner_variables[$delta]['style_data']) ? $banner_variables[$delta]['style_data']['url'] : '';
              $variables['#attached']['drupalSettings']['nk_tools']['fixed_banners'][$selector]['css_bg'] = isset($config['field_css_background']['value']) && $config['field_css_background']['value'] > 0 ? TRUE : FALSE;
            }

            $variables['content']['field_banners'][0]['#item_attributes']['height'] = 'auto';  
            $variables['content']['field_banners'][0]['#item_attributes']['width'] = '100%';

            if (isset($config['field_lazy_load']['value']) && $config['field_lazy_load']['value'] > 0) {
              $variables['content']['field_banners'][0]['#item_attributes']['data-lazy'] = TRUE;
              $variables['content']['field_banners'][0]['#item_attributes']['data-sizes'] = 'auto';
            }

            // Essential for JS to work 
            $variables['content']['field_banners'][0]['#item_attributes']['class'][] = 'banner-image';  
          }
          else {
            unset($variables['content']['field_banners'][$delta]);
          }

        }
      }

      // Attach our library now - it does auto-calculate image's container based on image data given here
      $variables['#attached']['library'][] = 'nk_tools/nk_tools_factory_banner';
    }
  }

  public static function setBanner($file_uri, $image_style = NULL) {

    $image = \Drupal::service('image.factory')->get($file_uri);
    $banners = [];

    if ($image) { 


      //$banners[$selector] = []; 

      if ($image_style) {
        $style = ImageStyle::load($image_style);

        foreach ($style->getEffects() as $effect_uuid => $effect) {
          if (!empty($effect->getConfiguration()['data']) && (isset($effect->getConfiguration()['data']['width']) || isset($effect->getConfiguration()['data']['height']))) {  
            $banners['style_data'] = $effect->getConfiguration()['data'];
            $banners['style_data']['ratio'] = $effect->getConfiguration()['data']['width'] / $effect->getConfiguration()['data']['height'];
            $banners['style_data']['uri'] = $style->buildUri($file_uri);
            $banners['style_data']['url'] = $style->buildUrl($file_uri); 
          }
        }
      }
      else {
        $image_factory = \Drupal::service('image.factory')->get($file_uri);
        $banners['width'] = $image_factory->getWidth();
        $banners['height'] = $image_factory->getHeight();
        $banners['ratio'] = $banners['width'] / $banners['height'];
        $banners['style_data']['uri'] = $file_uri;
        $banners['style_data']['url'] = file_create_url($file_uri);
      }  
    }
    return $banners;
  }


  public function paragraphLinks(NodeInterface $node, array $paragraphs) {

    $paragraph = [];
    $quick_links = [];

    foreach ($paragraphs as $field_name => $fields) {

      if ($node->hasField($field_name) && $node->get($field_name) instanceof EntityReferenceRevisionsFieldItemList) {

        $paragraph[$field_name] = $node->get($field_name)->referencedEntities();

        if (!empty($paragraph[$field_name])) {

          //$display_options = EntityViewDisplay::collectRenderDisplay($node, $build['#view_mode'])->getComponent($field_name);
          //if (!empty($display_options) && isset($display_options['settings']['view_mode'])) {
             //  $build['#node']->swiper_view_mode = $display_options['settings']['view_mode'];
          //}

          $quick_links = [
            '#theme' => 'nk_tools_quicklinks',
            '#links' => [],
            '#set_active_class' => TRUE,
            '#attributes' => [
              'data-list' => 'quicklinks',
              'class' => [
                'no-m',
                'no-p',
                'relative',
                'list-none',
              ],
            ] 
          ];

          foreach ($paragraph[$field_name] as $index => $p) {

            if ($short_title = $p->get($fields['title'])->value) { 
              $delta = $index + 1;
              $quick_links['#links'][$index]['title'] = Markup::create($short_title);
              $quick_links['#links'][$index]['url'] = Url::fromUserInput('#view-' . $node->id() . '-' . $delta);
              $quick_links['#links'][$index]['attributes'] = [
                'data-top-offset' => '46',
                'class' => [
                  'anchor-trigger',
                  'pointer'
                ], 
              ];
              //$variables['quick_links']['#links'][$index]['url'] = '#'; //Markup::create($short_title);
              //$variables['quick_links']['#links'][$index]['title'] = Markup::create($short_title);
              //$variables['quick_links']['#links'][$index]['link_title'] = Markup::create($short_title);
            } 
          }
        }
      }
    }

    return $quick_links;

  }

  public static function quicklinks(&$variables, NodeInterface $node) { 

    // Processing for all paragraph fields that are selected for anchor links or swiper links feature
    $quicklinks = self::QUICK_LINKS;

    if (!empty(array_keys($quicklinks))) {

      if (in_array($node->getType(), array_keys($quicklinks))) { 


         foreach ($quicklinks as $bundle => $paragraph) {

          $paragraphs = [];

          foreach ($paragraph as $field_name => $fields) {

             $paragraphs[$field_name] = $node->get($field_name)->referencedEntities();

              if (!empty($paragraphs[$field_name])) {

                //$display_options = EntityViewDisplay::collectRenderDisplay($node, $build['#view_mode'])->getComponent($field_name);
                //if (!empty($display_options) && isset($display_options['settings']['view_mode'])) {
                //  $build['#node']->swiper_view_mode = $display_options['settings']['view_mode'];
                //}

                 $quick_links = [
                   '#theme' => 'links',
                   '#links' => [],
                   '#set_active_class' => TRUE,
                   '#attributes' => [
                     'data-list' => 'quicklinks',
                     'class' => [
                       //'row',
                       //'start-xs',
                      // 'middle-xs',
                      // 'text-align-left', 
                       'no-m',
                       'no-p',
                       //'bg-white',
                       'relative',
                       //'ff-fauna-one', 
                       'list-none',
                       //'swiper-links-wrapper',
                     ],

                   ] 
                 ];

                 foreach ($paragraphs[$field_name] as $index => $p) {

                    if ($short_title = $p->get($fields['title'])->value) { 
                      $delta = $index + 1;
                      $quick_links['#links'][$index]['title'] = Markup::create($short_title);
                      $quick_links['#links'][$index]['url'] = Url::fromUserInput('#view-' . $node->id() . '-' . $delta);
                      $quick_links['#links'][$index]['attributes'] = [
                        'data-top-offset' => '46',
                        'class' => [
                          //'no-p',
                          //'fs-default',
                          'anchor-trigger',
                          'pointer'
                        ], 
                      ];
                           //$variables['quick_links']['#links'][$index]['url'] = '#'; //Markup::create($short_title);
         //$variables['quick_links']['#links'][$index]['title'] = Markup::create($short_title);
                      //$variables['quick_links']['#links'][$index]['link_title'] = Markup::create($short_title);
                    } 

                    //if ($body = $p->get($fields['teaser'])->value) {
                    //  $variables['quick_links'][$index]['link_teaser'] = Markup::create($body);
                    //} 
                  }
                }
             }
           }
         }

      }

      $variables['content'] = $quick_links;
  }

  public function elementFieldReference($reference) {
    $fields = [];
    if (isset($reference) && !empty($reference[0])) { 
      foreach ($reference as $index => $field) {
        if (!empty($field) && !empty($field['target_id'])) {
          $split = explode('.', $field['target_id']);
          if (count($split) > 2) {
            //str_replace('node.issue.', '', $field['target_id']); // : NULL;
            $fields[] = isset($split[2]) && !empty($split[2]) ? ['entity_type' => $split[0], 'bundle' => $split[1], 'field_name' => $split[2]] : [];
          }
        }
      }
    }

    $fields_entity = [];
    if (!empty($fields)) {
      foreach ($fields as $field) {
        $fields_entity[] = FieldConfig::loadByName($field['entity_type'], $field['bundle'], $field['field_name']);
      }
    }
    return $fields_entity;
  }


  public function fieldRender(EntityInterface $entity, array $field, $view_mode = NULL, $is_file = NULL) {

    if ($entity->getType() == $field['bundle'] && $entity->hasField($field['field_name']) && !empty($entity->get($field['field_name'])->getValue())) {

      $value_key = 'value';  
      $values = $entity->get($field['field_name'])->getValue();

      foreach($values as $item) {
        if (isset($item['target_id'])) {
          $value_key = 'target_id';
        }
      }

      $render_value = NULL;

      if ($view_mode) {
        $display_value = EntityViewDisplay::collectRenderDisplay($entity, $view_mode)->getComponent($field['field_name']);

        if (is_array($display_value) && !empty($display_value)) {
          $field_display = [];
          $field_display[] = $entity->get($field['field_name'])->view($display_value);
          $render_value = $this->renderer->renderRoot($field_display);
        } 
        // In case Display mode is set but field actually dosabled there this is a fallback
        // TODO: Send some message to user about this irregularity
        else {
          $render_value = $this->fieldRenderPrepare($entity, $values, $field, $is_file);
        }   
      }
      else {
        $render_value = $this->fieldRenderPrepare($entity, $values, $field, $is_file);
      }

      return $render_value;
    }
  }


  public function fieldRenderPrepare(EntityInterface $entity, array $values, array $field, $is_file = NULL) {

    $render_value = [];

    $title = NULL;

    if ($entity instanceof NodeInterface) {
      $title = $entity->getTitle();
    }
    else if ($entity instanceof ParagraphInterface) {
      $title = $entity->getParentEntity()->getTitle();
    }

    foreach($values as $delta => $value) {

      $value_key = isset($value['target_id']) ? 'target_id' : 'value';

      $params = [
        'alt' => $title,
        'title' => $title,
        'attributes' => [
          'class' => [
            'img-responsive',
          ],
        ] 
      ];
      if ($is_file) {
        $is_image = $is_file === 'image';
        $file_field = $this->renderFileField($field['entity_type'], $entity, $field['field_name'], $params, [], $is_image); 
        $render_value[$delta] = $this->renderer->render($file_field);
      }
      else {
        $render_value[$delta] = Markup::create($value[$value_key]);
      }
    }
    return $render_value;  
  }


  public function renderFileField($entity_type, $entity, string $fieldname, array $params = [], array $fids = [], bool $image = FALSE) {

    $rendered = [];
    $ids = !empty($fids) ? $fids : [];
    if (empty($ids)) {

      $files = $entity->get($fieldname)->getValue();

      // Use default field's image as a banner or empty array (for file not being an image but not existing)
      if (empty($files)) {
        return $image ? $this->defaultImage($entity_type, $entity, $fieldname, $params) : [];
      }
      /* var $image Image */
      //$image = \Drupal::service('image.factory')->get($file->getFileUri());
      else { 
        foreach ($files as $delta => $file) {
          $fids[] = (int)$file['target_id'];
        }
      }
    }

    $file_storage = $this->entityTypeManager->getStorage('file');

    $files_load = $file_storage->loadMultiple($fids);
    if (!empty($files_load)) {

      $i = 0;
      foreach($files_load as $fid => $file) {

        if ($file instanceof File) {
          if ($image) {
            $rendered[$i] = $this->renderImage($file, $params);
          }
          else {

            $description =  $entity->{$fieldname}[$i]->description;

            $options = [
              'attributes' => [
                //'download' => $file_properties['filename'][0]['value'],
                'class' => isset($params['class']) ? $params['class'] : [],
              ],
            ];


            $rendered[$i]['url'] = file_create_url($file->getFileUri());
            $uri = Url::fromUri($file->getFileUri(), $options);
            $rendered[$i]['link'] = Link::fromTextAndUrl(Markup::create($description), $uri);
            $rendered[$i]['description'] = $description;

          }

        }

        $i++;

      }

    } 

    return $rendered; 

  }


  public function renderImage(File $file, array $params = []) {

    $image = \Drupal::service('image.factory')->get($file->getFileUri());    

    $image_build = [ 
      '#uri' => $file->getFileUri(),
      '#width' => $image->getWidth(),
      '#height' => $image->getHeight(),
      '#alt' => isset($params['alt']) && !empty($params['alt']) ? $params['alt'] : 'Banner image',
      '#title' => isset($params['title']) && !empty($params['title']) ? $params['title'] : '',
      //'#srcset' => [],  
      //'#sizes' => '',
      '#attributes' => !empty($params) && isset($params['attributes']) && !empty($params['attributes']) ? $params['attributes'] : ['class' => ['default-image']],
    ];

    $image_style_storage = $this->entityTypeManager->getStorage('image_style');
    $style = !empty($params) && isset($params['url']) && !empty($params['url']) ? $image_style_storage->load($params['image_preset']) : NULL;

    if (!empty($params)) {
      if (isset($params['image_preset']) && !empty($params['image_preset'])) {

        //$derivative_uri = $style->buildUri($file->getFileUri());
        // Check on existing file or generate a new image style rendition
        //$derivative_rendition = file_exists($derivative_uri) || $style->createDerivative($file->getFileUri(), $derivative_uri);

        if ($style) {
          //$style = ImageStyle::load($params['image_preset']);
          // Return absolute URL to an image, use case as CSS bg image variable
          return $style->buildUrl($file->getFileUri());
        }
        else {
          $image_build['#theme'] = 'image_style';
          $image_build['#style_name'] = $params['image_preset']; 
          return $image_build;     
        }    
      }
      else {
        $image_build['#theme'] = 'image';
        return $image_build;
      }
    }
    else {
      $image_build['#theme'] = 'image';
      return $image_build;
    }
  }


  public function defaultImage($entity_type, $entity, $field_name, array $params = []) {

    $field_config = FieldConfig::loadByName($entity_type, $entity->getType(), $field_name); //'node', 'actors', 'field_image_banner');
    $file_uuid = $field_config->getSetting('default_image')['uuid'];
    if (!$file_uuid) {
      $field_storage_config = FieldStorageConfig::loadByName('node', $field_name);
      $file_uuid = $field_storage_config->getSetting('default_image')['uuid'];
    }
    $file = \Drupal::service('entity.repository')->loadEntityByUuid('file', $file_uuid);
    if ($file instanceof File) {
      return $this->renderImage($file, $params);
    }

  }


  /*
  public static function generateImageDerivate(Request $request, MediaInterface $media) {

    $query = $request->query;

    $width = (int) $query->get('width', 500);
    $height = (int) $query->get('height', 500);

    $image_style_id = sprintf('%d_%d', $width, $height);

    $file = $media->field_media_image->entity;

    $image_uri = $file->getFileUri();

    $image_style = ImageStyle::create([
      'name' => $image_style_id,
    ]);
    $image_style->addImageEffect([
      'id' => 'image_scale_and_crop',
      'weight' => 0,
      'data' => [
        'width' => $width,
        'height' => $height,
    ],
   ]);

   $derivative_uri = $image_style->buildUri($image_uri);

   $success = file_exists($derivative_uri) || $image_style->createDerivative($image_uri, $derivative_uri);

   $headers = [];

   $image = $this->imageFactory->get($derivative_uri);
   $uri = $image->getSource();
   $headers += [
    'Content-Type' => $image->getMimeType(),
    'Content-Length' => $image->getFileSize(),
   ];

   $response = new BinaryFileResponse($uri, 200, $headers);

   return $response;

 }
  */

   public function _getReferencedNodes($field_name, $parent_entity, bool $object = FALSE, array $format = [], array $target_ids = []) {

    $referenced_entities = [];

    // In some situations we have this array ready, like block that has fields target id already
    if (!empty($target_ids)) {
      $referenced_entities =  $target_ids;
    }
    else {
      foreach (Element::children($parent_entity[$field_name]) as $t) {
        $referenced_entities[$t] = $parent_entity[$field_name][$t]['target_id'];
      }
    }

    if (!empty($referenced_entities)) {

      // Return array of Drupal\node\Entity\Node instances
      if ($object) {
        $objects = \Drupal::service('entity_type.manager')->getStorage('node')->loadMultiple($referenced_entities);
        $nodes = [];
        foreach ($objects as $node) {
          $nid = $node->id();
          $nodes[$nid] = $node;
        }
        return $nodes;
      }

      // Light version with direct query to some fields only
      else {

        $query = \Drupal::database()->select('node_field_data', 'n');
        $query->addField('n', 'title');
        $query->addField('n', 'nid');
        $query->condition('nid', $referenced_entities, 'IN'); 
        $results = $query->execute()->fetchAll();

        if (!empty($results)) {
          $build = [];
          if (!empty($format)) {

            switch ($format['format']) {

              case 'links': 

                $items = [];
                $links = [];
                $links_theme = []; 
                foreach ($results as $result) {
                  if ($result->nid && $result->title) {
                    $title = \Drupal::translation()->translate($result->title); //Drupal::service('renderer')->render( 
                    $link_attributes = isset($format['links_attributes']) && !empty($format['links_attributes']) ? $format['links_attributes'] : ['class' => []];
                    $uri = Url::fromRoute('entity.node.canonical', ['node' => $result->nid], ['attributes' => $link_attributes]);
                    $links[] = Link::fromTextAndUrl($title, $uri); //, ['absolute' => TRUE])->toString();
                  }          
                }

                if (!empty($links)) {

                  if (isset($format['attributes'])) {
                    $format['attributes']['data-list'] = isset($format['attributes']['data-list']) && !empty($format['attributes']['data-list']) ? $format['attributes']['data-list'] : 'nk-tools-references';
                  }
                  else {
                    $format['attributes'] = [
                      'data-list' => 'nk-tools-references',
                      'class' => [],
                    ];
                  }
                  $build = [
                    '#theme' => 'item_list',
                    '#list_type' => 'ul',
                    '#title' => isset($format['title']) ? $format['title'] : NULL,
                    '#items' => $links,
                    '#attributes' => $format['attributes'],
                    '#wrapper_attributes' => isset($format['wrapper_attributes']) ? $format['wrapper_attributes'] : [ 'class' => [] ],
                  ]; 

                  return $build; 

                }

              break;
            }
          }   
        }
      }
    }


  }



   public function getReferencedNodes(EntityInterface $entity, array $field, $view_mode = NULL, array $target_ids = []) {  //$field_name, $parent_entity, bool $object = FALSE, array $format = [], array $target_ids = []) {

    $entities = [];

    // In some situations we have this array ready, like block that has fields target id already
    if (!empty($target_ids)) {
      $referenced_entities =  $target_ids;
    }
    else {
      if ($entity->hasField($field['field_name']) && !empty($entity->get($field['field_name'])->getValue())) {
        foreach ($entity->get($field['field_name'])->getValue() as $delta => $value) {
          if (isset($value['target_id']) && !empty($value['target_id'])) {
            $referenced_entities[$delta] = $value['target_id'];
          }
        }
      }
    }

    if (!empty($referenced_entities)) {

      $reference_type = $field['entity_reference'];

      $object_storage = $this->entityTypeManager->getStorage($reference_type); 
      $objects = $object_storage->loadMultiple($referenced_entities);    
      foreach ($objects as $id => $object) {
        $entities[$id] = $view_mode ? $this->entityTypeManager->getViewBuilder($reference_type)->view($object, $view_mode) : $object;
      } 
    }
    return $entities;

  }

  public static function reverseReference($entity_type, $field_name, $id = NULL, array $target_types = []) {

    // Query with entity_type.manager (The way to go)
    $query = $this->entityTypeManager->getStorage($entity_type);
    //$query_result = $query->getQuery()->condition('status', NODE_PUBLISHED);

    if ($id) {
      $query_result->condition($field_name, $id);
    }

    if (!empty($target_types)) {
      $query_result->condition('type', $target_types, 'IN');
    }
    //->condition('custom_taxonomy', [2, 8], 'IN')
    //->condition('custom_taxonomy.%delta', 2, '=')
    //->condition('id', $id);
    //->sort('field_last_name', DESC);
    //$or = $query->orConditionGroup();
    //$or->condition('custom_taxonomy.0.target_id', 2);
    //$or->condition('custom_taxonomy.0.target_id', 8);
    //$query->condition($or);
    //$query_result->execute();
    return $query_result;
  }

/*
  public static function tabbedBlocks($nid, $viewId, array $tabbed_blocks) {

    $blocks['tabbed_blocks'] = [];

    foreach ($tabbed_blocks as $key => $tab) {
      $content = self::getView($viewId, $key, [$nid]);
      if ($content) {
        $blocks[$key] = $content; 
        $blocks['tabbed_blocks'][$key] = [
          'label' => $tab,
          'id' => $key,
          'content' => $blocks[$key],
        ];
      }
    } 
    return $blocks;
  }

  public function buildBlockUI($config, $params) {

    $elements = NULL;

    if (isset($params['nid']) && !empty($params['nid'])) {
      $nid = $params['nid'];
    }
    else {
      $node = $this->getEntity('node', $params);
      $nid = $node['nid'][0]['value'];
    }


    if ($nid) {

      $viewId = $config['block_tabbed_blocks']['view_id'];

      $tabbed_blocks = [];

      foreach ($config['block_tabbed_blocks'] as $key => $view) {
        if (strpos($key, 'display_') !== FALSE && !empty($view['display_id'])) {
          $tabbed_blocks[$view['display_id']] = $view['label'];
        }
      }

      $elements = self::tabbedBlocks($nid, $viewId, $tabbed_blocks);
    }

    $build = [
      //'#cache' => ['max-age' => 0],
      '#theme' => isset($params['theme']) ? $params['theme'] : 'item_list',
      '#items' => $elements && isset($elements['tabbed_blocks']) ? $elements['tabbed_blocks'] : [],
      '#attributes' => [
        'class' => isset($params['class']) ? $params['class'] : [],
      ],
    ];

    return $build;

  }
*/

}