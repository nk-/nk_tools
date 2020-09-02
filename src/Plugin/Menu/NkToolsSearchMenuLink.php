<?php

namespace Drupal\nk_tools\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\nk_tools\NkToolsBase;

/**
 * A menu link that shows search markup
 */
class NkToolsSearchMenuLink extends MenuLinkDefault {

 /**
   * The current user.
   *
   * @var \Drupal\nk_tools\NkToolsBase
   */
  public $nkTools;

  /**
   * Constructs a new Search menu link (obsolete in this version).
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\StaticMenuLinkOverridesInterface $static_override
   *   The static override storage.
   * @param \Drupal\nk_tools\NkToolsBase $nk_tools
   *   NkTools factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StaticMenuLinkOverridesInterface $static_override, NkToolsBase $nk_tools) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $static_override);
    $this->nkTools = $nk_tools;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu_link.static.overrides'),
      $container->get('nk_tools.main_service')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    $nk_tools_config = \Drupal::config('nk_tools.settings');
    $widgets = $nk_tools_config->get('widgets');
    if (is_array($widgets) && isset($widgets['use_search']) && isset($widgets['search']['menu']) && !empty($widgets['search']['menu'])) {
      $menu_name = isset($widgets['search']['menu_item']) && !empty($widgets['search']['menu_item']) ? str_replace(':', '', $widgets['search']['menu_item']) : NULL;
      if ($widgets['search']['view']) {
        $view_id = isset($widgets['search']['view_container']['view_id']) && !empty($widgets['search']['view_container']['view_id']) ? $widgets['search']['view_container']['view_id'] : NULL;
        $display_id = isset($widgets['search']['view_container']['display']['display_id']) && !empty($widgets['search']['view_container']['display']['display_id']) ? $widgets['search']['view_container']['display']['display_id'] : NULL;
        $view_route = $view_id && $display_id ? 'view.' . $view_id . '.' . $display_id : NULL;
        if ($view_route) {
          $view_filter = $this->nkTools->renderViewFilter($view_id, $display_id); // + $base_plugin_definition;
          return '';
        }
      }

    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return '<current>'; //'views.elastic.page_1'; //
  }

}
