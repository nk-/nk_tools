<?php

namespace Drupal\nk_tools\Plugin\Menu;

use Drupal\user\Plugin\Menu\LoginLogoutMenuLink;

/**
 * A menu link that shows "Sign in" or "Log out" as appropriate.
 */
class NkToolsSignInLogoutMenuLink extends LoginLogoutMenuLink {

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    if ($this->currentUser->isAuthenticated()) {
      return $this->t('Sign Out');
    }
    else {
      return $this->t('Sign In');
    }
  }
}