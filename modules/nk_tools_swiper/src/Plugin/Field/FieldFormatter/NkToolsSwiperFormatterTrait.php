<?php

namespace Drupal\nk_tools_swiper\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;
use Drupal\Component\Utility\Xss;
use Drupal\nk_tools_swiper\Entity\NkToolsSwiper; 


# use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A common Trait for swiper formatters.
 *
 * Currently, only image based formatters exist for swiper but this trait
 * could apply to any type formatter.
 *
 * @see \Drupal\Core\Field\FormatterBase
 */
trait NkToolsSwiperFormatterTrait {

    
  /**
   * Returns the swiper specific default settings.
   *
   * @return array
   *   An array of default settings for the formatter.
   */
  protected static function getDefaultSettings() {
    return [
      'options' => 'images_swiper',
      'caption' => '',
    ];
  }

  /**
   * Builds the swiper settings summary.
   *
   * @param \Drupal\Core\Field\FormatterBase $formatter
   *   The formatter having this trait.
   *
   * @return array
   *   The settings summary build array.
   */
  protected function buildSettingsSummary(FormatterBase $formatter) {
    $summary = [];

    // Load the selected options.
    // #orig $options = $this->loadOptions($formatter->getSetting('options'));
    //$options = $this->loadStorage($formatter->getSetting('options'));
    $options = NkToolsSwiper::load($formatter->getSetting('options'))->toArray();    


    // Build the options summary.
    $os_summary = !empty($options) && isset($options['label']) ? $options['label'] : $formatter->t('Default settings');
    $summary[] = $formatter->t('Option: %os_summary', ['%os_summary' => $os_summary]);

    return $summary;
  }

  /**
   * Builds the swiper settings form.
   *
   * @param \Drupal\Core\Field\FormatterBase $formatter
   *   The formatter having this trait.
   *
   * @return array
   *   The render array for Options settings.
   */
  protected function buildSettingsForm(FormatterBase $formatter) {

    // Get list of option sets as an associative array.
    $swiper_storage = NkToolsSwiper::loadStorage();
    $options = [];
    foreach ($swiper_storage as $swiper_template => $option) {
      $options[$swiper_template] = $option['label'];
    }

    if (empty($options)) {
      $options[''] = t('No defined option sets');
    }
    
       
    $element['options'] = [
      '#title' => $formatter->t('Swiper template'),
      '#type' => 'select',
      '#default_value' => $formatter->getSetting('options'),
      '#options' => $options,
      '#weight' => 0,

    ];

       
    $element['links'] = [
      '#theme' => 'links',
      '#links' => [
        [
          'title' => $formatter->t('Create new option set'),
          'url' => Url::fromRoute('entity.nk_tools_swiper.add_form', [], [
            'query' => \Drupal::destination()->getAsArray(),
          ]),
        ],
        [
          'title' => $formatter->t('Manage options'),
          'url' => Url::fromRoute('entity.nk_tools_swiper.collection', [], [
            'query' => \Drupal::destination()->getAsArray(),
          ]),
        ],
      ],
      '#access' => \Drupal::currentUser()->hasPermission('administer swiper'),
    ];
    /*
    $element['css_class'] = [
      '#title' => $formatter->t('Extra CSS class'),
      '#type' => 'textfield',
      '#default_value' => $formatter->getSetting('css_class'),
      '#weight' => 10,
    ];
    */

    return $element;
  }

  /**
   * The swiper formatted view for images.
   *
   * @param array $images
   *   Images render array from the Image Formatter.
   * @param array $formatter_settings
   *   Render array of settings.
   *
   * @return array
   *   Render of swiper formatted images.
   */
  protected function viewImages(array $images, array $formatter_settings) {

    // Bail out if no images to render.
    if (empty($images)) {
      return [];
    }

    $swiper_storage = NkToolsSwiper::loadStorage($formatter_settings['options']);
    $formatter_settings['swiper_options'] = $swiper_storage['swiper_options']; 
 
    
    // Get cache tags for the option set.
    if ($options = NkToolsSwiper::load($formatter_settings['options'])) {
      $cache_tags = $options->getCacheTags();
   
      $swiper_id = &drupal_static('swiper_id', 0);
      $id = 'swiper-' . $options->id() . '-' . ++$swiper_id;
    }
    else {
      $cache_tags = [];
      $id = 'swiper-node-default';
    }

    $formatter_settings['swiper_options']['id'] = $id;
    $formatter_settings['swiper_options']['attributes'] = [
      'id' => $id,
      'class' => 'todo',
    ];

    $items = [];

    foreach ($images as $delta => &$image) {

      // Merge in the cache tags.
      if ($cache_tags) {
        $image['#cache']['tags'] = Cache::mergeTags($image['#cache']['tags'], $cache_tags);
      }
 
      //$image['attributes']['class'][] = 'swiper-lazy';
      //$image['attributes']['data-src'][] = 'something';

      // Prepare the slide item render array.
      $item = [];
      $item['slide'] = $swiper_storage['swiper_options']['lazy_load'] ? '<div data-background="' . $image['#attributes']['src'] . '" class="swiper-lazy"><div class="swiper-lazy-preloader"></div></div>' : render($image);
      $item['loader'] = $swiper_storage['swiper_options']['lazy_load'];

      // Check caption settings.
      if ($formatter_settings['caption'] == 1) {
        $item['caption'] = [
          '#markup' => Xss::filterAdmin($image['#item']->title),
        ];
      }
      elseif ($formatter_settings['caption'] == 'alt') {
        $item['caption'] = [
          '#markup' => Xss::filterAdmin($image['#item']->alt),
        ];
      }

      $items[$delta] = $item;
    }

    $images['#theme'] = 'nk_tools_swiper';
    $images['#swiper'] = [
      'settings' => $formatter_settings,
      'items' => $items,
    ];

    if (!isset($images['#attached']['drupalSettings']['nk_tools_swiper'])) {
      $images['#attached']['drupalSettings']['nk_tools_swiper'] = [
        'swipers' => [],
      ];
    }

    $images['#attached']['drupalSettings']['nk_tools_swiper']['swipers'][$id] = $formatter_settings['swiper_options']; 
    $images['#attached']['library'][] = 'nk_tools_swiper/swiper';
    $images['#attached']['library'][] = 'nk_tools_swiper/nk_tools_swiper'; 


    return $images;
  }

  /**
   * Returns the form element for caption settings.
   *
   * @param \Drupal\Core\Field\FormatterBase $formatter
   *   The formatter having this trait.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The image field definition.
   *
   * @return array
   *   The caption settings render array.
   */
  protected function captionSettings(FormatterBase $formatter, FieldDefinitionInterface $field_definition) {
    $field_settings = $field_definition->getSettings();

    // Set the caption options.
    $caption_options = [
      0 => $formatter->t('None'),
      1 => $formatter->t('Image title'),
      'alt' => $formatter->t('Image ALT attribute'),
    ];

    // Remove the options that are not available.
    $action_fields = [];
    if ($field_settings['title_field'] == FALSE) {
      unset($caption_options[1]);
      // User action required on the image title.
      $action_fields[] = 'title';
    }
    if ($field_settings['alt_field'] == FALSE) {
      unset($caption_options['alt']);
      // User action required on the image alt.
      $action_fields[] = 'alt';
    }

    // Create the caption element.
    $element['caption'] = [
      '#title' => $formatter->t('Choose a caption source'),
      '#type' => 'select',
      '#options' => $caption_options,
    ];

    // If the image field doesn't have all of the suitable caption sources,
    // tell the user.
    if ($action_fields) {
      $action_text = $formatter->t('enable the @action_field field', ['@action_field' => implode(' and/or ', $action_fields)]);
      /* This may be a base field definition (e.g. in Views UI) which means it
       * is not associated with a bundle and will not have the toUrl() method.
       * So we need to check for the existence of the method before we can
       * build a link to the image field edit form.
       */
      if (method_exists($field_definition, 'toUrl')) {
        // Build the link to the image field edit form for this bundle.
        $rel = "{$field_definition->getTargetEntityTypeId()}-field-edit-form";
        $action = $field_definition->toLink($action_text, $rel,
          [
            'fragment' => 'edit-settings-alt-field',
            'query' => \Drupal::destination()->getAsArray(),
          ]
        )->toRenderable();
      }
      else {
        // Just use plain text if we can't build the field edit link.
        $action = ['#markup' => $action_text];
      }
      $element['caption']['#description']
        = $formatter->t('You need to @action for this image field to be able to use it as a caption.',
        ['@action' => render($action)]);

      // If there are no suitable caption sources, disable the caption element.
      if (count($action_fields) >= 2) {
        $element['caption']['#disabled'] = TRUE;
      }
    }
    else {
      $element['caption']['#default_value'] = $formatter->getSetting('caption');
    }

    return $element;
  }

  /**
   * Return the currently configured option set as a dependency array.
   *
   * @param \Drupal\Core\Field\FormatterBase $formatter
   *   The formatter having this trait.
   *
   * @return array
   *   An array of option set dependencies
   */
  protected function getOptionsDependencies(FormatterBase $formatter) {
    $dependencies = [];
    $option_id = $formatter->getSetting('options');
    if ($option_id && $options = NkToolsSwiper::load($option_id)) { // Add the options as dependency.
      $dependencies[$options->getConfigDependencyKey()][] = $options->getConfigDependencyName();
    }
    return $dependencies;
  }

  /**
   * If a dependency is going to be deleted, set the option set to default.
   *
   * @param \Drupal\Core\Field\FormatterBase $formatter
   *   The formatter having this trait.
   * @param array $dependencies_deleted
   *   An array of dependencies that will be deleted.
   *
   * @return bool
   *   Whether or not option set dependencies changed.
   */
  protected function optionsDependenciesDeleted(FormatterBase $formatter, array $dependencies_deleted) {
    $option_id = $formatter->getSetting('options');
    if ($option_id && $options = $options = NkToolsSwiper::load($option_id)) {
      if (!empty($dependencies_deleted[$options->getConfigDependencyKey()]) && in_array($options->getConfigDependencyName(), $dependencies_deleted[$options->getConfigDependencyKey()])) {
        $formatter->setSetting('options', 'homepage_swiper');
        return TRUE;
      }
    }
    return FALSE;
  }

}
