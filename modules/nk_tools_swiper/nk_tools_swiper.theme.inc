<?php

/**
 * Prepares variables for swiper template.
 *
 * Default template: templates/swiper.html.twig.
 */
function template_preprocess_swiper(&$variables) {

  $view = isset($variables['view']) ? $variables['view'] : NULL;

  if ($view) {
    $style = $view->style_plugin;
    $options = $style->options;

    $variables['view'] = $view;

    $settings = [
      'options' => $style->options['swiper_template'],
    ];
    $settings['swiper_options'] = isset($style->swiper_storage['swiper_options']) ? $style->swiper_storage['swiper_options'] : [];
    $settings['swiper_options']['attributes'] = [
      'id' => isset($variables['swiper']) && !empty($variables['swiper']) && !empty($variables['swiper']['swiper_id']) ?  $variables['swiper']['swiper_id'] : $style->options['id'], 
      'class' =>  $style->options['css_class'],     
    ];
    foreach ($variables['rows'] as $row) {
      // Set row into a slide.
      $item['slide'] = $row; //render($row);
      $items[] = $item;
    }
  }
  else {
    // Reference configuration variables.
    $settings = $variables['swiper']['settings'];
    $items = &$variables['swiper']['items'];
  }

  $config = isset($settings['swiper_options']) ? $settings['swiper_options'] : $settings;

  // Take care of Swiper navigation, unset other properties if main flag (checkbox) is on
  $navigation = $config['navigation']['navigation_enabled'];
  if (!$navigation) {
    unset($config['navigation']);
  }

  // Take care of Swiper pagination, unset other properties if main flag (checkbox) is on
  $pagination = $config['pagination']['pagination_enabled'];
  if (!$pagination) {
    unset($config['pagination']);
  }

  $settings['attributes'] = isset($config['attributes']) && !empty($config['attributes']) ? $config['attributes'] : $settings['attributes'];
  $settings['image_style'] = isset($settings['image_style']) && !empty($settings['image_style']) ? $settings['image_style'] : NULL;

  // Configure attributes for containing elements.
  $attributes = $variables['attributes'];

  // Merge with defined attributes.
  if (isset($settings['attributes']) && is_array($settings['attributes'])) {
    $attributes = NestedArray::mergeDeep($attributes, $settings['attributes']);
  }

  // Add the swiper class to be namespaced.
  $attributes['class'] = [];
  $attributes['class'][] = 'swiper-container';

  // Add the options name as a class to the container.
  if (isset($config['id'])) {
   $attributes['class'][] = 'options-' . Html::getClass($config['id']); 
  }

  // Add the image style name as a class to the container.
  if (!empty($settings['image_style'])) {
    $attributes['class'][] = 'imagestyle-' . Html::getClass($settings['image_style']);
  }

  // Pass attributes to twig.
  $variables['attributes'] = new Attribute($attributes);

  // Add the list render array.
  $variables['content']['list'] = [
    '#theme' => 'swiper_list',
    '#items' => $items,
    '#settings' => $config,
  ];

  $variables['options'] = $config;

  if ($navigation) {
    $variables['options']['navigation_classes'] = [
      'prev' => ltrim($config['navigation']['prevEl'], '.'), 
      'next' => ltrim($config['navigation']['nextEl'], '.'), 
    ];
  }

  $variables['options']['pagination_classes'] = $pagination ? ltrim($config['pagination']['el'], '.') : ''; 
  $variables['options']['pagination_classes'] .= $pagination && $config['direction'] == 'vertical' ? ' right' : '';
  //$variables['#attached']['library'][] = 'nk_tools_swiper/swiper';
  //$variables['#attached']['library'][] = 'nk_tools_swiper/nk_tools_swiper_swiper'; 

}

/**
 * Prepares variables for swiper list template.
 *
 * Default template: templates/swiper-list.html.twig.
 */
function template_preprocess_swiper_list(&$variables) {
  // Reference configuration variables.
  $options = &$variables['settings']['options'];
  
  $items = &$variables['items'];

  $attributes = &$variables['attributes'];
  $attributes['class'][] = 'swiper-wrapper';
  $attributes = new Attribute($attributes);

  // Build the list.
  $variables['content']['list'] = [];
  if (!empty($items)) {
    foreach ($items as $i => $item) {
      $caption = '';
      if (!empty($item['caption'])) {
        $caption = $item['caption'];
      }

      // Add the list item render array.
      $variables['content']['list'][$i] = [
        '#theme' => 'swiper_list_item',
        '#item' => $item['slide'],
        '#loader' => isset($item['loader']) ? $item['loader'] : NULL,
        '#caption' => $caption,
        '#settings' => [
          'options' => $options,
        ],
      ];
    }
  }
}

/**
 * Prepares variables for swiper list item template.
 *
 * Default template: templates/swiper-list-item.html.twig.
 */
function template_preprocess_swiper_list_item(&$variables) {

  // Reference configuration variables.
  $item = &$variables['item'];
  $settings = &$variables['settings'];
  $caption = &$variables['caption'];
  $attributes = &$variables['attributes'];
  $options = $settings['options'];

  // Add swiper slide item class.
  $attributes['class'][] = 'swiper-slide';

  $variables['attributes'] = new Attribute($attributes);

   // Generated thumbnail support.
  // @TODO review this piece of code.
  /*
  if ($options->getOption('controlNav') === "thumbnails") {
    // If the thumbnails are enabled in the options,
    // extract the url to set as the thumbnail data.
    $src = [];
    if (!preg_match("/<img.+?src=[\"'](.+?)[\"'].+?>/", $item, $src)) {
      preg_match("/<img.+?srcset=[\"'](.+?)[\"'].+?>/", $item, $src);
    }

    if (!empty($src[1])) {
      $attributes['data-thumb'] = $src[1];
    }

    // Let's also get the alt attribute to apply to thumbnails.
    // This only works in library version 2.6+.
    $alt = [];
    preg_match("/<img.+?alt=[\"'](.+?)[\"'].+?>/", $item, $alt);

    if (!empty($alt)) {
      $attributes['data-thumb-alt'] = $alt[1];
    }
  }

  if ($options->getOption('thumbCaptions') and !empty($caption)) {
    $attributes['data-thumbcaption'] = $caption;
    // Prevent captions from appearing in the slider as well.
    if (FALSE === $options->getOption('thumbCaptionsBoth')) {
      $caption = '';
    }
  }
  */
}