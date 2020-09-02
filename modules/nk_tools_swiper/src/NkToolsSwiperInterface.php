<?php

namespace Drupal\nk_tools_swiper;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Nk tools swiper entity type.
 */
interface NkToolsSwiperInterface extends ConfigEntityInterface {

  /**
   * Returns an array with all of the entity's properties that are Swiper.js options.
   */
  public function getSwiperOptions();
  
  /**
   * Sets all of the entity's properties that are Swiper.js options into an array
   */
  public function setSwiperOptions(array $swiper_options = []);
  
  /**
   * Load storage (data) for single or multiple Swiper configuration entities
   *
   * @param string $storage_name Entity's id, if empty all of Swiper entities are loaded
   */
  public static function loadStorage(string $storage_name = '');
 
}