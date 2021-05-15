<?php

namespace Drupal\nk_tools\EventSubscriber; 

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\views\Ajax\ViewAjaxResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface; 
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Response subscriber to handle AJAX responses.
 */
class AjaxResponseSubscriber implements EventSubscriberInterface {

  /**
   * Renders the ajax commands right before preparing the result.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The response event, which contains the possible AjaxResponse object.
   */
  public function onResponse(FilterResponseEvent $event) {
 
    $response = $event->getResponse();

    if ($response instanceof ViewAjaxResponse) { // Only alter views ajax responses.
      $commands = &$response->getCommands();
      $view = $response->getView();

      $display = $view->display_handler->display;

/*
      $filters = [];
      if (!empty($view->getExposedInput())) {
        $skip = ['content_identifier', 'single_filter'];
        foreach ($view->getExposedInput() as $key => $value) {
          if (!in_array($key, $skip) && !empty($value) && $value != 'All') {
            $filters[$key] = $value;
          }
        }
      }

      if ($view->display_handler->hasPath()) { // && $view->pager->usePager()) {
        $commands[] = [
          'command' => 'diploAjaxPager',
          'selector' => '.js-view-dom-id-' . $view->dom_id,
          'view_dom_id' =>  $view->dom_id,
          'view_arg' => $view->args,
          'view_filters' => $filters,
          'view_pager' => $view->pager->current_page,
          'view_path' => '/' . $view->display_handler->getPath(),
        ];
      }
    
      $commands[] = [
        'command' => 'diploHighlight',
        'selector' => '.js-view-dom-id-' . $view->dom_id,
        'view_dom_id' =>  $view->dom_id,
        'view_arg' => $view->args
      ];
*/
      $commands[] = [
        'command' => 'nkToolsAjaxArguments',
        'selector' => '.js-view-dom-id-' . $view->dom_id,
        'view_dom_id' =>  $view->dom_id,
        'view_args' => $view->args
      ];

    }
    else if ($response instanceof AjaxResponse) {
      $commands = &$response->getCommands();
      $commands[] = [
        'command' => 'nkToolsAjax',
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::RESPONSE => [['onResponse']]];
  }
}
