<?php

namespace Drupal\nk_tools\Ajax;

//use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * Provides an Ajax command for refreshing an ajax View.
 */
class NkToolsReloadViewCommand extends InvokeCommand {

  /**
   * Implements \Drupal\Core\Ajax\CommandInterface:render().
   */
  /*
  public function render() {
    return [
      'command' => 'nkToolsReloadView',
      //'url' => $this->url,
      'message' => 'whatever',
    ];
  }
  */
}