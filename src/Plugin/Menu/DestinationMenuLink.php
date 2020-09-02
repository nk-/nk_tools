<?php

namespace Drupal\nk_tools\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Url;

/**
 * Modifies the menu link to add destination.
 */
class DestinationMenuLink extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    $options = parent::getOptions();
    // Append the current path as destination to the query string.
    $options['query']['destination'] = Url::fromRoute('<current>')->toString();
    $options['attributes']['class'][] = 'toolbar-icon-system-themes-page';
    return $options;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Make cacheable once https://www.drupal.org/node/2582797 lands.
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
