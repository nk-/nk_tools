<?php

namespace Drupal\nk_tools_swiper\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\nk_tools_swiper\NkToolsSwiperInterface;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 * Defines the Nk tools swiper entity type.
 *
 * @ConfigEntityType(
 *   id = "nk_tools_swiper",
 *   label = @Translation("Swiper"),
 *   label_collection = @Translation("Swipers"),
 *   label_singular = @Translation("swiper"),
 *   label_plural = @Translation("swipers"),
 *   label_count = @PluralTranslation(
 *     singular = "@count swiper",
 *     plural = "@count swipers",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\nk_tools_swiper\NkToolsSwiperListBuilder",
 *     "form" = {
 *       "add" = "Drupal\nk_tools_swiper\Form\NkToolsSwiperForm",
 *       "edit" = "Drupal\nk_tools_swiper\Form\NkToolsSwiperForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "nk_tools_swiper",
 *   admin_permission = "administer nk_tools_swiper",
 *   links = {
 *     "collection" = "/admin/structure/nk-tools-swiper",
 *     "add-form" = "/admin/structure/nk-tools-swiper/add",
 *     "edit-form" = "/admin/structure/nk-tools-swiper/{nk_tools_swiper}",
 *     "delete-form" = "/admin/structure/nk-tools-swiper/{nk_tools_swiper}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "direction",
 *     "slidesPerView",
 *     "loopedSlides",
 *     "lazy_load",
 *     "effect",
 *     "delay",
 *     "autoHeight",
 *     "grabCursor",
 *     "noSwipingSelector",
 *     "navigation_enabled",
 *     "nextEl",
 *     "prevEl",
 *     "pagination_enabled",
 *     "type",
 *     "el",
 *     "dynamicBullets",
 *     "clickable",
 *     "swiper_options"
 *   }
 * )
 */
class NkToolsSwiper extends ConfigEntityBase implements NkToolsSwiperInterface {

  /**
   * The swiper ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Swiper entity label.
   *
   * @var string
   */
  protected $label;

  /**
   * Swiper entity status.
   *
   * @var bool
   */
  protected $status;

  /**
   * Swiper entity description.
   *
   * @var string
   */
  protected $description;

  /**
   * Sliding direction.
   *
   * @var string
   */
  protected $direction;

  /**
   * Number of slides per view (slides visible at the same time on slider's container).
   *
   * @var integer
   */
  protected $slidesPerView;

  /**
   * Number of slides looped at once.
   *
   * @var integer
   */
  protected $loopedSlides;

  /**
   * Lazy load instruction.
   *
   * @var bool
   */
  protected $lazy_load;

  /**
   * Sliding effect.
   *
   * @var string
   */
  protected $effect;

  /**
   * Autoplay delay.
   *
   * @var string
   */
  protected $delay;

  /**
   * Swiper autoHeight config.
   *
   * @var bool
   */
   protected $autoHeight;

  /**
   * Swiper grabCursor config.
   *
   * @var bool
   */
   protected $grabCursor;
  
  /**
   * Swiper's no swiping child element selectors.
   *
   * @var string
   */
   protected $noSwipingSelector;

  /**
   * Enable/disable Swiper navigation buttons
   *
   * @var bool
   */
   protected $navigation_enabled;

  /**
   * Swiper navigation next button config.
   *
   * @var string
   */
   protected $nextEl;

  /**
   * Swiper navigation prev button config.
   *
   * @var string
   */
   protected $prevEl;

  /**
   * Enable/disable Swiper pagination.
   *
   * @var bool
   */
   protected $pagination_enabled;

  /**
   * Swiper pagination type
   *
   * @var string
   */
   protected $type;

  /**
   * Swiper pagination element selector.
   *
   * @var string
   */
   protected $el; 
   
  /**
   * Swiper's dynamicBullets config.
   *
   * @var bool
   */
   protected $dynamicBullets;

  /**
   * Swiper pagination clickable config.
   *
   * @var bool
   */
   protected $clickable;

   /**
   * A collection of all of the Swiper's properties into a single array
   *
   * @var array
   */
   protected $swiper_options = [];

  /**
   * @inheritdoc
   */
  public function getSwiperOptions() {
    return $this->swiper_options;
    // Special handling for image
    /*
    if (isset($this->podcast_elements['podcast_image'])) {
      $fids = is_array($this->podcast_elements['podcast_image']['fids']) ? $this->podcast_elements['podcast_image']['fids'] : []; 
      if (!empty($fids)) {
        foreach ($fids as $fid) {   
          $image = File::load($fid);
          // Make sure to apply image_style (1400x1400px)
          $style = \Drupal::entityTypeManager()->getStorage('image_style')->load('podcast_list');
          if ($image) {
            $this->podcast_elements['rendered_image'] = $style->buildUrl($image->getFileUri());
          }
        }
      }
    }
    return $this->podcast_elements;
    */
  }


  /**
   * @inheritdoc
   */
  public function setSwiperOptions(array $swiper_options = []) {
    $this->swiper_options = $swiper_options;
  }

  public function setPaginationType($pagination_type) {
    $this->pagination_type = $pagination_type;
  }

  /**
   * Private callback, load our custom config entities (swipers templates)
   */
   public static function loadStorage(string $storage_name = '') {
    if (empty($storage_name)) {
      $swipers_storage = self::loadMultiple();
      $swipers = [];
      foreach ($swipers_storage as $swiper) {
        $swipers[$swiper->id()] = $swiper->load($swiper->id())->toArray();
        $swipers[$swiper->id()]['swiper_options'] = $swiper->getSwiperOptions(); 
      }
      return $swipers;
    }
    else {
      $swiper = static::load($storage_name);
      $output = $swiper->toArray();
      $output['swiper_options'] = $swiper->getSwiperOptions(); 
      return $output;
    }
  }

}