<?php

namespace Drupal\nk_tools_menu\Plugin\Block;

use Drupal\system\Plugin\Block\SystemMenuBlock;

use Drupal\Core\Template\Attribute;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Link;


use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides block with links to social networks.
 *
 * @Block(
 *   id = "nk_tools_menu_expandable_menu_block",
 *   admin_label = @Translation("Expandable menu"),
 *   category = @Translation("Nk tools menu"),
 *   deriver = "Drupal\system\Plugin\Derivative\SystemMenuBlock",
 *   forms = {
 *     "settings_tray" = "\Drupal\system\Form\SystemMenuOffCanvasForm",
 *   }   
 * )
 */
class NkToolsExpandableMenu extends SystemMenuBlock {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'block_hide_mobile' => NULL,
      'icon' => 'menu',
      'close_icon' => 'close',
      'sliding' => TRUE,
      'back_icon' => 'chevron_left',
      'toggle_icon' => 'chevron_right',
      'fonts' => 'fs-1-25, fs-1-1, fs-default',
      'additional_class' => NULL,
      'animation_in' => NULL,
      'animation_out' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['block_hide_mobile'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not display on mobile'),
      '#description' => $this->t('It ends up on just a single CSS rule eventually.'), 
      '#default_value' => $config['block_hide_mobile'], 
    ];
  
    $form['icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Menu icon'),
      '#description' => $this->t('Usage of <a href="https://material.io/resources/icons/" target="blank_">material icons </a>, can be any from that set.'), 
      '#default_value' => $config['icon'],
    ];

    $form['close_icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Close menu icon'),
      '#description' => $this->t('Usage of <a href="https://material.io/resources/icons/" target="blank_">material icons </a>, can be any from that set.'), 
      '#default_value' => $config['close_icon'],
    ];
    
    $form['block_effects'] = [
      '#type' => 'details',
      '#title' => $this->t('DOM and markup'),
      '#open' => TRUE,
    ];

    $form['block_effects']['sliding'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sliding menu'),
      '#description' => $this->t('Use sliding (lef-right) effect for entering into submenu view, rather than expand-down collapsible\'s pane default.'),
      '#default_value' => $config['sliding'],
    ];

    $form['block_effects']['back_icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Back icon'),
      '#description' => $this->t('Define and icon for sliding back from submenu view. Usage of <a href="https://material.io/resources/icons/" target="blank_">material icons </a>, can be any from that set.'),
      '#default_value' => $config['back_icon'],
    ];

    $form['block_effects']['toggle_icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Toggle icon'),
      '#description' => $this->t('Define and icon for sliding in submenu view. Usage of <a href="https://material.io/resources/icons/" target="blank_">material icons </a>, can be any from that set.'),
      '#default_value' => $config['toggle_icon'],
    ];

    $form['block_effects']['fonts'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fonts'),
      '#description' => $this->t('Enter comma separated CSS class names that control font-size of the nested links, from parents (biggest) down to submenu items children, smaller.'),
      '#default_value' => $config['fonts'],
    ];

    $form['block_effects']['additional_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Additional CSS class for the menu itself. It goes on each expanding down or sliding in container.'),
      '#description' => $this->t('Just a class name, without "."'),
      '#default_value' => $config['additional_class'],
    ];
    
    $form['block_effects']['animations'] = [
      '#type' => 'details',
      '#title' => $this->t('Animation classes'),
      '#description' => $this->t('In & Out CSS animation classes for this block. Seel list of animations/effects of <a href="https://daneden.github.io/animate.css/" target="blank_">Animate.css</a> which is integrated and supported here.') ,
      '#open' => TRUE,
    ];
    $form['block_effects']['animations']['animation_in'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Animation "in"'),
      '#description' => $this->t('A CSS class name, without ".", f.ex. <em>bounceInUp</em>'),
      '#default_value' => $config['animation_in'],
    ];
    $form['block_effects']['animations']['animation_out'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Animation "out"'),
      '#description' => $this->t('A CSS class name, without ".", f.ex. <em>bounceOutDown</em>'),
      '#default_value' => $config['animation_out'],
    ];

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $values = $form_state->getValues();
    $this->configuration['block_hide_mobile'] = $values['block_hide_mobile'];
    $this->configuration['icon'] = $values['icon'];
    $this->configuration['close_icon'] = $values['close_icon'];

    $this->configuration['sliding'] = $values['block_effects']['sliding'];
    $this->configuration['fonts'] = $values['block_effects']['fonts'];
    $this->configuration['back_icon'] = $values['block_effects']['back_icon'];
    $this->configuration['toggle_icon'] = $values['block_effects']['toggle_icon'];
    $this->configuration['additional_class'] = $values['block_effects']['additional_class'];
    $this->configuration['animation_in'] = $values['block_effects']['animations']['animation_in'];
    $this->configuration['animation_out'] = $values['block_effects']['animations']['animation_out'];


    /*$this->configuration['level'] = $form_state->getValue('level');
    $this->configuration['depth'] = $form_state->getValue('depth');
    $this->configuration['expand_all_items'] = $form_state->getValue('expand_all_items');*/
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    //$items = $this->buildItems($tree, $tree_access_cacheability, $tree_link_cacheability);
    
   // $diplo_config = \Drupal::config('diplo_formatters.settings');
    $config = $this->getConfiguration();  
 
    $menu_name = $this->getDerivativeId();
    if ($this->configuration['expand_all_items']) {
      $parameters = new MenuTreeParameters();
      $active_trail = $this->menuActiveTrail->getActiveTrailIds($menu_name);
      $parameters->setActiveTrail($active_trail);
    }
    else {
      $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($menu_name);
    }

    // Adjust the menu tree parameters based on the block's configuration.
    $level = $this->configuration['level'];
    $depth = $this->configuration['depth'];
    $parameters->setMinDepth($level);
    // When the depth is configured to zero, there is no depth limit. When depth
    // is non-zero, it indicates the number of levels that must be displayed.
    // Hence this is a relative depth that we must convert to an actual
    // (absolute) depth, that may never exceed the maximum depth.
    if ($depth > 0) {
      $parameters->setMaxDepth(min($level + $depth - 1, $this->menuTree->maxDepth()));
    }

    // For menu blocks with start level greater than 1, only show menu items
    // from the current active trail. Adjust the root according to the current
    // position in the menu in order to determine if we can show the subtree.
    if ($level > 1) {
      if (count($parameters->activeTrail) >= $level) {
        // Active trail array is child-first. Reverse it, and pull the new menu
        // root based on the parent of the configured start level.
        $menu_trail_ids = array_reverse(array_values($parameters->activeTrail));
        $menu_root = $menu_trail_ids[$level - 1];
        $parameters->setRoot($menu_root)->setMinDepth(1);
        if ($depth > 0) {
          $parameters->setMaxDepth(min($level - 1 + $depth - 1, $this->menuTree->maxDepth()));
        }
      }
      else {
        return [];
      }
    }

    $tree = $this->menuTree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    
    
    
    $menu_attributes = [
      'role' => 'expandable-menu',
      'class' => [
        'pl-0',
        'list-none',
      ],
    ];

    if (isset($config['additional_class']) && !empty($config['additional_class'])) {
      $menu_attributes['class'][] = $config['additional_class'];
    }

    $links_attributes = [
      'data-list' => 'nk_tools_burger_collapsible',
      'class' => [
        'no-p',
        'no-m',
        'navigation',
        'list-none',
        'nk-tools-collapsible-links',
      ]
    ];

    $fonts = ['fs-1-25'];
    if (isset($config['fonts']) && !empty($config['fonts'])) {
      $fonts = strpos($config['fonts'], ', ') !== FALSE ? explode(', ', $config['fonts']) : [$config['fonts']];
    }
    $toggle_attributes = [ 
      'data-bg-active' => 'bg-grey pt-8 pb-8',
      'class' => [
        $fonts[0],
      ]
    ];
 
    if (isset($config['sliding']) && $config['sliding']) {
      $toggle_attributes['data-sliding'] = 1;
    }
    if (isset($config['animation_in']) && !empty($config['animation_in'])) {
      $toggle_attributes['data-target-in'] = $config['animation_in'];
    }
    if (isset($config['animation_out']) && !empty($config['animation_out'])) {
      $toggle_attributes['data-target-out'] = $config['animation_out'];
    }
    if (isset($config['back_icon']) && !empty($config['back_icon'])) {
      $toggle_attributes['data-icon-back'] = $config['back_icon'];
    }
   
    if (isset($config['toggle_icon']) && !empty($config['toggle_icon'])) {
      $toggle_attributes['data-icon'] = $config['toggle_icon'];
    }

    $pane_wrapper = [
      'class'=> [
        'sliding-item',
        'pb-16',
        'pt-8',
        'col-xs-12'
      ]
    ];
   
    $tree_attributes = [
      'fonts' => $fonts,
      'menu_name' => $menu_name,
      'menu' => $menu_attributes,
      'links' => $links_attributes,
      'toggle' => $toggle_attributes,
      'pane_wrapper' => $pane_wrapper
    ];

    
    //$diplo_factory = \Drupal::service('diplo_formatters.main_service');
    
    $build_menu_tree = []; //$diplo_factory->renderCollapsibleMenu($menu_name, 3, NULL, $tree_attributes);
    //$build_menu_tree['#toggle_attributes'] = new Attribute($toggle_attributes); 
    //$build_menu_tree['#pane_wrapper_attributes'] = new Attribute($pane_wrapper);


    $tree = $this->menuTree->transform($tree, $manipulators);
    $build_tree = $this->menuTree->build($tree);
    $items = $build_tree['#items'];

    if (!empty($items)) {
    
      $menu_level = 0;
      $build_menu_tree = $this->renderMenuItems($menu_name, $items, $menu_level, $tree_attributes);

      $build_menu_tree['#toggle_attributes'] = new Attribute($toggle_attributes); 
      $build_menu_tree['#pane_wrapper_attributes'] = new Attribute($pane_wrapper);
    }
      
    $build = [
      '#theme' => 'nk_tools_expandable_menu',
      '#config' => [
        'toggle_attributes' => $toggle_attributes,
        //'layout' => $diplo_config->get('layout'),
        //'widgets' => $diplo_config->get('widgets'),
      ],
      '#target_id' => 'nk-tools-burger-menu',
      '#icon' => isset($config['icon']) ? $config['icon'] : 'menu',
      '#close_icon' => isset($config['close_icon']) ? $config['close_icon'] : 'menu',
      '#menu_tree' => $build_menu_tree,
      '#attributes' => [
        'role' => 'expandable-menu-block',
        'class' => [
          'expandable-menu',
        ],
      ],
    ];
    
    return $build;
    //$this->menuTree->build($tree);
  
  }

 

  public function renderMenuItems($menu_name, $items, &$menu_level, array $attributes = []) {
  
    // Prepare items array for our custom theme
    $build_menu_tree = [
      '#theme' => 'nk_tools_collapsible_pane',
      '#hook' => 'menu_' . $menu_name,
      '#level' => $menu_level,
      '#block_id' =>  Html::getUniqueId($menu_name . '-' . $menu_level), 
      '#items' => [],
      '#attributes' => [
        'role' => 'expandable-menu',
        'class' => [
          'pl-0',
          'list-none',
        ],
      ],
    ];

    foreach ($items as $menu_link_key => $menu_link) {
               
      $collapsible_key = Html::getUniqueId($menu_link_key);
      $menu_attributes = isset($attributes['menu']) ? $attributes['menu'] : ['class' => []];
      $toggle_attributes = isset($attributes['toggle']) && !empty($attributes['toggle']) ? $attributes['toggle'] : [];
          
      $toggle_attributes['class'] = [
        'pb-4',
        'pt-4',
        'pr-4',
        'pl-4',
      ];
      

      $pane_wrapper = [
        'class'=> [
          'sliding-item',
          'pb-8',
          'pt-4',
          'col-xs-12'
        ]
      ];

      if (isset($menu_link['below']) && !empty($menu_link['below'])) {

        $build_menu_tree['#items'][$collapsible_key] = [
          'target' => 'pane-' . $collapsible_key,
          'label' => $menu_link['title'],
          'is_link' => FALSE,
          'attributes' =>  $menu_attributes,   
          'content' => [],
        ];

        if (isset($attributes['fonts']) && !empty($attributes['fonts'])) {
          $toggle_attributes['class'][] = $menu_level == 0 ? $attributes['fonts'][1] : $attributes['fonts'][2]; 
        }

        $tree_attributes = [
          'fonts' => isset($attributes['fonts']) ? $attributes['fonts'] : NULL,
          'menu_name' => $menu_name,
          'menu' => isset($attributes['menu']) ? $attributes['menu'] : ['role' => 'expandable-menu'],
          'links' => [] , //$links_attributes,
          'toggle' => $toggle_attributes,
          'pane_wrapper' => $pane_wrapper,
        ];

        $build_menu_tree['#items'][$collapsible_key]['is_link'] = FALSE;
        $menu_level += 1;
        $build_menu_tree['#items'][$collapsible_key]['content'] = $this->renderMenuItems($menu_name, $menu_link['below'], $menu_level, $tree_attributes); //, array $attributes = []) 
          
        $build_menu_tree['#items'][$collapsible_key]['content']['#toggle_attributes'] = new Attribute($tree_attributes['toggle']);
        $build_menu_tree['#items'][$collapsible_key]['content']['#pane_wrapper_attributes'] = new Attribute($tree_attributes['pane_wrapper']);

       }
        else {
       
          $fields[$collapsible_key] = $this->getLinkExtraFields($menu_link['original_link'], ['highlighted', 'icon']); 
          

          $link_attributes = isset($attributes['links']) && !empty($attributes['links']) ? $attributes['links'] : ['class' => []];
          $link_attributes['class'][] = 'fw-400'; 
          $link_attributes['class'][] = 'expandable-menu-link';

          if (isset($attributes['fonts']) && !empty($attributes['fonts'])) {
            $link_attributes['class'][] = $menu_level == 0 ? $attributes['fonts'][0] : $attributes['fonts'][2];
          }

          

          //$uri = Url::fromRoute($menu_link['original_link']->getRouteName(), $menu_link['original_link']->getRouteParameters());
          // $menu_link['url']->setOption('attributes', $link_attributes);
          // $label = Link::fromTextAndUrl($menu_link['title'], $menu_link['url']); 

          $build_menu_tree['#items'][$collapsible_key] = [
            'is_link' => $menu_link['url'],
            'label' => $menu_link['title'],
            'link_attributes' => [], //$link_attributes
          ];
                    
          if (isset($fields[$collapsible_key]['highlighted']) && !empty($fields[$collapsible_key]['highlighted'])) {
            //ksm($fields[$collapsible_key]);
            $build_menu_tree['#items'][$collapsible_key]['highlighted'] = $fields[$collapsible_key]['highlighted'];
          }

          $build_menu_tree['#items'][$collapsible_key]['#toggle_attributes'] = new Attribute($link_attributes); 
          $build_menu_tree['#items'][$collapsible_key]['#pane_wrapper_attributes'] = new Attribute($pane_wrapper);
          
          //$build_menu_tree['#items'][$collapsible_key]['is_link'] = TRUE;
          //$build_menu_tree['#items'][$collapsible_key]['content'] = $links;
          //$build_menu_tree['#items'][$collapsible_key]['content']['#toggle_attributes'] = new Attribute($toggle_attributes);
          //$build_menu_tree['#items'][$collapsible_key]['content']['#pane_wrapper_attributes'] = new Attribute($pane_wrapper);

          //$build_menu_tree['#items'][$collapsible_key]['content'][] = $menu_link;
        }
     }


     return $build_menu_tree;
 
  }

/*
  public function linkLabel($menu_link, array $extra_fields = []) {
    ksm($menu_link['attributes']);

    $is_link = FALSE;
    $label = $menu_link->link->getTitle();
    $class = 'expandable-menu-link';
    $menu_link_storage = \Drupal::service('entity_type.manager')->getStorage('menu_link_content');

    if (!$menu_link->hasChildren && !empty($menu_link->link->getRouteName())) {
 
      $menu_link['attributes']->addClass();

      $uri = Url::fromRoute($menu_link->link->getRouteName(), $menu_link->link->getRouteParameters());
      $label = Link::fromTextAndUrl($menu_link->link->getTitle(), $uri);
      $is_link = TRUE;
      $values = $this->getLinkExtraFields($menu_link->link, ['highlighted', 'icon']);
        //$fields = ['highlighted', 'icon']; 
        //$metadata = $menu_link->link->getMetaData();
        //$entity = is_array($metadata) && isset($metadata['entity_id']) && !empty($metadata['entity_id']) ? $menu_link_storage->load($metadata['entity_id']) : NULL;
        //$values = [];
        //foreach ($fields as $field) {
        //  if ($entity->hasField($field) && !empty($entity->get($field)->getValue())) {
         ////   $values[$field] = $entity->get($field)->getValue()[0]['value'];
          //}
        //}
        if (isset($values['highlighted']) && !empty($values['highlighted'])) {
          $class .= ' ' . $values['highlighted'];   
        }
      } 
    }
    return [
      'is_link' => $is_link,
      'label' => $label,
      'class' => $class,
    ];
     
  }
*/

  public function getLinkExtraFields($menu_link_content, array $fields) {
    $values = [];
    $metadata = $menu_link_content->getMetaData();
    $menu_link_storage = \Drupal::service('entity_type.manager')->getStorage('menu_link_content');
    $entity = is_array($metadata) && isset($metadata['entity_id']) && !empty($metadata['entity_id']) ? $menu_link_storage->load($metadata['entity_id']) : NULL;
    if ($entity && !empty($fields)) {
     foreach ($fields as $field) {
       if ($entity->hasField($field) && !empty($entity->get($field)->getValue())) {
          $values[$field] = $entity->get($field)->getValue()[0]['value'];
        }
      }
    }
    return $values;
  }


}
