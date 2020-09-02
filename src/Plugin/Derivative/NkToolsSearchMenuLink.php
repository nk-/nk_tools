<?php

namespace Drupal\nk_tools\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\views\Plugin\views\display\DisplayMenuInterface;
use Drupal\views\Views;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\nk_tools\NkToolsBase;

/**
 * Provides menu links for Views.
 *
 * @see \Drupal\views\Plugin\Menu\ViewsMenuLink
 */
class NkToolsSearchMenuLink extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The view storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $viewStorage;

  /**
   * The current user.
   *
   * @var \Drupal\nk_tools\NkToolsBase
   */
  public $nkToolsFactory;

   /**
   * Constructs a \Drupal\views\Plugin\Derivative\ViewsLocalTask instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $view_storage
   *   The view storage.
   */
  public function __construct(EntityStorageInterface $view_storage, NkToolsBase $nk_tools_factory) {
    $this->viewStorage = $view_storage;
    $this->nkToolsFactory = $nk_tools_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')->getStorage('view'),
      $container->get('nk_tools.main_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $links = [];
    $nk_tools_config = \Drupal::config('nk_tools.settings');
    $widgets = $nk_tools_config->get('widgets');
    if (is_array($widgets) && isset($widgets['use_search']) && isset($widgets['search']['menu']) && !empty($widgets['search']['menu'])) {
      $menu_name = isset($widgets['search']['menu_item']) && !empty($widgets['search']['menu_item']) ? str_replace(':', '', $widgets['search']['menu_item']) : NULL;
      //$links[$view_id .'_' . $display_id] = $this->nkToolsFactory->renderViewFilter($view_id, $display_id) + $base_plugin_definition;
    }
    return $links;
  }

}
