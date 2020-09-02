<?php

namespace Drupal\nk_tools\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\nk_tools\NkToolsBase;

/**
 * Returns responses for nk tools module routes.
 */
class NkToolsController extends ControllerBase {

  /**
   * Nk tools main service.
   *
   * @var \Drupal\nk_tools\NkToolsBase
   */
  protected $nkToolsService;

  /**
   * Constructor.
   *
   * @param \Drupal\nk_tools\NkToolsBase $nk_tools_service
   */
  public function __construct(NkToolsBase $nk_tools_service) {
    $this->nkToolsService = $nk_tools_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('nk_tools.main_service'));
  }

  /**
   * Clears all caches, then redirects to the previous page.
   */
  public function cacheClear() {
    drupal_flush_all_caches();
    $this->messenger()->addMessage($this->t('Cache cleared.'));
    return $this->redirect('<front>');
  }

} 