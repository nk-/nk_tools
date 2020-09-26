<?php

namespace Drupal\nk_tools\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Extension\ModuleHandlerInterface;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\nk_tools\NkToolsBase;


class NkToolsBlockBase extends BlockBase implements ContainerFactoryPluginInterface {
  
  /**
   * Drupal\Core\Entity\EntityTypeManager definition
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Entity\EntityFieldManager definition
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Drupal\Core\Entity\EntityDisplay definition
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory; 

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;


  /**
   * The module handler.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRoute;


  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;
  
  /**
   * Drupal\nk_tools\NkToolsBase definition.
   *
   * @var \Drupal\nk_tools\NkToolsBase
   */
  protected $nkToolsFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, EntityDisplayRepositoryInterface $entity_display_repository, ConfigFactoryInterface $config_factory, RendererInterface $renderer, CurrentRouteMatch $current_route, ModuleHandlerInterface $module_handler, NkToolsBase $nk_tools_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->configFactory = $config_factory; 
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
    $this->currentRoute = $current_route;
    $this->nkToolsFactory = $nk_tools_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_display.repository'),
      $container->get('config.factory'),
      $container->get('renderer'),
      $container->get('current_route_match'),
      $container->get('module_handler'),
      $container->get('nk_tools.main_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'block_label' => [
        'value' => NULL,
        'format' => 'basic_html'
      ],
      'icon' => NULL,
      'hide_mobile' => NULL,
      'hide_init' => NULL,
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

    // Custom composite element
    $form['nk_tools_fields'] = [
      '#type' => 'nk_tools_block_fields',
      '#title' => $this->t('Base settings'),
      '#open' => TRUE,
      '#default_value' => $config
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    
    parent::blockSubmit($form, $form_state);
    
    $values = $form_state->getValues();
    
    if (isset($values['nk_tools_fields']) && !empty($values['nk_tools_fields'])) {
    
      foreach ($values['nk_tools_fields'] as $field_id => $field_value) {
        if (is_array($field_value)) {
          if (isset($field_value['value'])) {
            $this->configuration[$field_id]['value'] = !empty($field_value['value']) ? $field_value['value'] : NULL;
            if (isset($field_value['format'])) {
              $this->configuration[$field_id]['format'] = $field_value['format'];
            }
          }
          else {
            foreach ($field_value as $id => $value) {
              $this->configuration[$id] = $value ? $value : NULL;
            }
          }
        }
        else {
          $this->configuration[$field_id] = $field_value ? $field_value : NULL;
        } 
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
  
    $config = $this->getConfiguration();
    $nk_tools_config = $this->configFactory->get('nk_tools.settings')->getRawData();

    $build = [
      '#attributes' => [
        'class' => [
          'nk-tools-block'
        ]
      ],
    ]; 

    // Checkboxes
    if (isset($config['hide_init']) && $config['hide_init'] > 0) {
      $build['#attributes']['class'][] = isset($nk_tools_config['layout']['hidden_class']) ? $nk_tools_config['layout']['hidden_class'] : NULL;
    }
    if (isset($config['hide_mobile']) && $config['hide_mobile'] > 0) {
      $build['#attributes']['class'][] = isset($nk_tools_config['layout']['desktop_only_class']) ? $nk_tools_config['layout']['desktop_only_class'] : NULL;
    }

    // Textfields
    if (isset($config['icon']) && !empty($config['icon'])) {
      $build['#attributes']['data-icon'] = $config['icon'];
    }

    if (isset($config['additional_class']) && !empty($config['additional_class'])) {
      $build['#attributes']['class'][] = $config['additional_class'];
    }
  
    $animation_in = isset($config['animation_in']) && !empty($config['animation_in']) ? $config['animation_in'] : NULL;
    $animation_out = isset($config['animation_out']) && !empty($config['animation_out']) ? $config['animation_out'] : NULL;

    if ($animation_in || $animation_out) {
      // First set parent animation classs, as set on my module's config, i.e. "animated"
      $build['#attributes']['class'][] = isset($nk_tools_config['layout']['animate_class']) ? $nk_tools_config['layout']['animate_class'] : NULL;
    
      // Set in and out animations
      if ($animation_in) {
        // Note that it goes to data-target-in/out attribute rather than setting "in" class directly
        $build['#attributes']['data-target-in'] = $animation_in;
        // However, if block is omt initially hidden, as per config checkbox then we should standard run the animation on init
        if (!isset($config['hide_init']) || $config['hide_init'] < 1) {
          $build['#attributes']['class'][] = $animation_in;
        }
      }
      // Animation "out" certainly do not get set on init
      if ($animation_out) {
        $build['#attributes']['data-target-out'] = $animation_out;
      }
    }

    // Block label, formatted textfield element
    if (isset($config['block_label']) && isset($config['block_label']['value']) && !empty($config['block_label']['value'])) {
      $build['#title'] = Markup::create($config['block_label']['value']);
    }

    return $build;
  }

  
 
  /**
   * Returns entity (form) displays for the current entity display type.
   *
   * @return \Drupal\Core\Entity\Display\EntityDisplayInterface[]
   *   An array holding entity displays or entity form displays.
   */
/*
  protected function getDisplays($reference = NULL, $bundle = NULL) {
    
    if (!$bundle && $reference) {
      $bundle = $this->getBundle($reference);
    }

    if ($bundle) {
      $load_ids = [];
      $display_entity_type = 'entity_view_display'; //$node->getEntityTypeId();
      $entity_type = $this->entityTypeManager->getDefinition($display_entity_type);
      $config_prefix = $entity_type->getConfigPrefix();
      $ids = $this->configFactory->listAll($config_prefix . '.node.' . $bundle. '.');
      foreach ($ids as $id) {
        $config_id = str_replace($config_prefix . '.', '', $id);
        list(,, $display_mode) = explode('.', $config_id);
        if ($display_mode != 'default') {
          $load_ids[] = $config_id;
        }
      }
      return $this->entityTypeManager->getStorage($display_entity_type)->loadMultiple($load_ids);
    }
  }
  
*/

  protected function getBundle($reference) {
    $entities = [];
    if (isset($reference) && !empty($reference[0])) { 
      foreach ($reference as $index => $entity) {
        if (!empty($entity) && !empty($entity['target_id'])) {
          $split = explode('.', $entity['target_id']);
          if (count($split) > 2) {
            //str_replace('node.issue.', '', $field['target_id']); // : NULL;
            $entities[] = isset($split[2]) && !empty($split[2]) ? ['entity_type' => $split[0], 'bundle' => $split[1], 'field_name' => $split[2]] : [];
          }
        }
      }
    }
    return $entities;
  }


}