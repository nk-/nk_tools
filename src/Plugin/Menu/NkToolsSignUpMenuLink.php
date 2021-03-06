<?php

namespace Drupal\nk_tools\Plugin\Menu;

use Drupal\user\Plugin\Menu\LoginLogoutMenuLink;

/**
 * A menu link that shows "Log in" or "Log out" as appropriate.
 */
class NkToolsSignUpMenuLink extends LoginLogoutMenuLink {

 
  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    if ($this->currentUser->isAuthenticated()) {
      $acronym = substr($this->currentUser->getDisplayName(), 0, 2);
      return $acronym;
    }
    else {
      return $this->t('Sign Up');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    if ($this->currentUser->isAuthenticated()) {
      return 'user.page';
    }
    else {
      return 'user.register';
    }
  }
}