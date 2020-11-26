<?php

namespace Drupal\nk_tools\Plugin\Block;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Template\Attribute;

use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Drupal\Component\Utility\Html;

use Drupal\node\NodeInterface;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;
use Drupal\image\Entity\ImageStyle;

use Drupal\field\Entity\FieldConfig;

use Drupal\nk_tools\Plugin\Block\NkToolsBlockBase;

/**
 * Provides an example block.
 *
 * @Block(
 *   id = "nk_tools_nodes_banner_block",
 *   admin_label = @Translation("Nodes banner block"),
 *   category = @Translation("Nk tools"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
 * )
 */
class NkToolsoNodesBanner extends NkToolsBlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'banner_field_name' => NULL,
      'banner_style' => NULL,
      'banner_fixed_element' => NULL,
      'banner_offset' => NULL,
      'banner_set_fixed_width' => NULL,
      'banner_fixed_width' => NULL,
      'banner_background' => NULL,
      'banner_lazy' => NULL,
      'banner_caption_select' => NULL,
      'banner_caption_select_fields' => NULL,
      'video_block' => NULL,
      'video_width' => NULL,
      'video_height' => NULL,
      'video_offset' => NULL,

    ] + parent::defaultConfiguration();
  }


  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
  
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
 
    $fields = $this->nkToolsFactory->elementFieldReference($config['banner_field_name']);

    $form['banner_field_name'] = [
      '#title' => t('Banner image'),
      '#description' => t('A machine name of the field that serves as banner'),
      '#required' => TRUE,
      '#type' => 'entity_autocomplete',
      '#target_type' => 'field_config',
      '#tags' => TRUE,
      '#default_value' => $fields, // The #default_value can be either an entity object or an array of entity objects.
      '#multiple' => FALSE,
      '#maxlength' => '256',
    ];

    $form['banner_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Image style'),
      '#description' => $this->t('Choose an image style preset for banner(s).'), 
      '#options' => image_style_options(),
      '#default_value' => $config['banner_style'], 
    ];

    $form['banner_fixed_element'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Parent element'),
      '#description' => $this->t('A jQuery element selector, element that should grow too based on image current height in viewport. Usually this is an id or a class of a parent div or region div wrapper to which block is assigned.'),
      '#default_value' => $config['banner_fixed_element'],
    ];

    $form['banner_offset'] = [
      '#type' => 'number',
      '#title' => $this->t('Top offset'),
      '#description' => $this->t('Additional offset to add on calculated container\'s height. Due some padding or other CSS rules this may be necessary to set so feel free to tweak until deserved result. Value can be negative too.'),
      '#default_value' => $config['banner_offset'],
    ];

    $form['banner_set_fixed_width'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set fixed width'),
      '#description' => $this->t('Set fixed width to banner image, in pixels. In this case image will responsive re-scale only on sizes smaller than specified.'), 
      '#default_value' => $config['banner_set_fixed_width'],
      '#attributes' => [
        'id' => 'set-fixed-width',
      ],
    ];

    $form['banner_fixed_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Fixed width'),
      '#description' => $this->t('Enter the width for banner image. Note that the height will be auto calculated based on chosen Image style preset\'s width/height ratio.'),
      '#default_value' => $config['banner_fixed_width'],
      '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
        'visible' => [
          ':input[id="set-fixed-width"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['banner_background'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use as CSS background image'),
      '#description' => $this->t('Instead of rendering as <img> it will be a CSS background image.'), 
      '#default_value' => $config['banner_background'],
    ];

    $form['banner_lazy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Lazy load'),
      '#description' => $this->t('Lazy load banner image. Requires <a href="https://drupal.org/project/lazy">lazy</a> module or at least a custom code that would react on "data-lazy=true" attribute set on banner image rendition. It won\'t apply if "Use as CSS background image" is selected.'), 
      '#default_value' => $config['banner_lazy'],
    ];


    $form['banner_caption_select'] = [
      '#type' => 'radios',
      '#title' => $this->t('Caption source'),
      '#description' => $this->t('Set caption text for banner image. Can be either node title or values of a field(s) which are attached to this node/content type. Note that you can change position and style of a caption in general by overriding a few CSS rules that come along here and make caption position absolute at banner\'s bottom. You can still override this setting with completely custom one by creating a twig template for this block and setting "data-caption" attribute on parent div (i.e. something like this <em>{{ attributes.setAttribute(\'data-caption\', \'#content-tools\') }}</em>'),
      '#options' => [
        '' =>  $this->t('No caption'),
        'title' =>  $this->t('Node Title'),
        'field' => $this->t('Field values'),
      ],
      '#default_value' => $config['banner_caption_select'],
      '#attributes' => [
        'id' => 'caption-select',
      ], 
    ];

    $caption_fields = $this->nkToolsFactory->elementFieldReference($config['banner_caption_select_fields']);

    $form['banner_caption_select_fields'] = [
      '#title' => t('Field(s) for a caption'),
      '#description' => t('A machine name of the fields whose values show as banner\'s caption.'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'field_config',
      '#tags' => TRUE,
      '#default_value' => $caption_fields, // The #default_value can be either an entity object or an array of entity objects.
      '#multiple' => TRUE,
      '#maxlength' => '256',
      '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
        'visible' => [
          ':input[id="caption-select"]' => ['value' => 'field'],
        ],
      ],
    ];
       
    $form['banner_use_video'] = [
      '#type' => 'details',
      '#title' => $this->t('Video in banner'),
      '#description' => $this->t('Configuration related to possible video in the banner region.') 
    ];
    $form['banner_use_video']['video_block'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Video container'),
      '#description' => $this->t('If this banner has video, provide jQuery selector for video\'s parent. This way automatic and responsive size and ratio of video are set.'),
      '#default_value' => $config['video_block'],
      '#attributes' => [
        'id' => 'block-video',
      ], 
    ];
    
    $form['banner_use_video']['video_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Video max width'),
      '#description' => $this->t('A CSS property to set maximum width for the video within banner. Can be <em>560px</em> or <em>56%</em> for example'),
      '#default_value' => $config['video_width'],
      '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
        'visible' => [
          ':input[id="block-video"]' => ['filled' => TRUE],
        ],
      ],
    ];

   $form['banner_use_video']['video_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Video max height'),
      '#description' => $this->t('A CSS property to set maximum height for the video within banner. Can be <em>360px</em> or <em>56%</em> for example'),
      '#default_value' => $config['video_height'],
      '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
        'visible' => [
          ':input[id="block-video"]' => ['filled' => TRUE],
        ],
      ],
    ];

    $form['banner_use_video']['video_offset'] = [
      '#type' => 'number',
      '#title' => $this->t('Video offset'),
      '#description' => $this->t('A helper value when we need to adjust video height.'),
      '#default_value' => $config['video_offset'],
      '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
        'visible' => [
          ':input[id="block-video"]' => ['filled' => TRUE],
        ],
      ],
    ];
 
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();

   
    $this->configuration['banner_field_name'] = $values['banner_field_name'];
    $this->configuration['banner_style'] = $values['banner_style'];
    $this->configuration['banner_set_fixed_width'] = $values['banner_set_fixed_width'];
    $this->configuration['banner_fixed_width'] = $values['banner_fixed_width'];
    $this->configuration['banner_fixed_element'] = $values['banner_fixed_element'];
    $this->configuration['banner_background'] = $values['banner_background'];
    $this->configuration['banner_lazy'] = $values['banner_lazy'];
    $this->configuration['banner_offset'] = $values['banner_offset'];    
    $this->configuration['banner_caption_select'] = $values['banner_caption_select'];
    $this->configuration['banner_caption_select_fields'] = $values['banner_caption_select_fields'];

    $this->configuration['video_block'] = $values['banner_use_video']['video_block'];
    $this->configuration['video_width'] = $values['banner_use_video']['video_width'];
    $this->configuration['video_height'] = $values['banner_use_video']['video_height'];
    $this->configuration['video_offset'] = $values['banner_use_video']['video_offset'];
    
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $config = $this->getConfiguration();    
    $node = $this->getContextValue('node');

    if (!$node instanceof NodeInterface) {
      return [];
    }

    $build = [];
  
    $fields = $this->nkToolsFactory->elementFieldReference($config['banner_field_name']); 
    $field = is_array($fields) ? reset($fields) : NULL;

    if ($field instanceof FieldConfig && $node->hasField($field->getName())) { 

      $field_name = $field->getName();
      $attached = [];
      $caption = [];
    
      $config['block_id'] = 'block-'. Html::getUniqueId($config['id']); // . '-' . $node->id());
      //if (!isset($config['block_id']) || (isset($config['block_id']) && empty($config['block_id']))) {
        //$config['block_id'] = 'block-'. Html::getUniqueId($config['id']);
      //}

      $field_params = [
        //'url' => TRUE,
        'image_preset' => $config['banner_style'], 
        'alt' => $node->getTitle(),
        'title' => $node->getTitle(),
        'attributes' => [
          'height' => 'auto',  
          'width' => '100%',
          'class' => ['banner-image']
        ],
      ] + $config;
  
      if ($config['banner_lazy']) {
        $field_params['attributes']['data-lazy'] = TRUE; 
        $field_params['attributes']['data-sizes'] = 'auto';  
      }

      // Here is possibility to override default banner for each node. Simply if image was uploaded on the node form
      $custom_image = $node->get($field_name)->getValue();
      $fids = !empty($custom_image) && isset($custom_image[0]['target_id']) ? [$custom_image[0]['target_id']] : [];
  
      $images = $this->nkToolsFactory->renderFileField('node', $node, $field_name, $field_params, $fids, TRUE); 
      $image = is_array($images) && isset($images[0]) ? reset($images) : $images;

      if ($image) {
        $build =  $this->renderImage($image, $config, $node);
      }
    }
    
    return parent::build() + $build;
   
  }
  
  // Caption related processing
  protected function renderCaption(array $config, NodeInterface $node) {
  
    $caption = [];
    $caption_fields = $this->nkToolsFactory->elementFieldReference($config['banner_caption_select_fields']); 
    
    if (!empty($caption_fields)) {
      foreach ($caption_fields as $caption_field) { 
        if ($caption_field instanceof FieldConfig && $node->hasField($caption_field->getName())) {
          
          // A machine name of the field
          $caption_source = $caption_field->getName();
          
          // Imagine giving some "common" keys to some standard title and/or body fields
          if (strpos($caption_source, 'title') !== FALSE) {
            $caption_name = 'title';
          } 
          else if (strpos($caption_source, 'body') !== FALSE) {
            $caption_name = 'body';
          }
          else {
            $caption_name = $caption_source;
          }
          $field_values = $node->get($caption_source)->getValue();
          if (!empty($field_values) && isset($field_values[0]['value'])) {
            $caption[$caption_name] = [];
            foreach ($field_values as $delta => $field_value) {
              $caption[$caption_name][$delta] = [
                '#markup' => Markup::create($field_value['value']) 
              ];
            }
          }
        }
      }
    }

    return $caption;
  }

  protected function renderImage(array $image, array $config, NodeInterface $node) {
  
    $id = $config['block_id'];

    $image_data = [];
   
    foreach ($image as $key => $property) { 
      $clean_key = str_replace('#', '', $key);
      $image_data[$clean_key] = $property; 
    } 

    // Process and render any possible caption here
    $caption = $this->renderCaption($config, $node);

    $attached['fixed_banners'][$id] = [];
    
    // Image style (preset) is defined and should be used
    if (!empty($config['banner_style'])) {
            
      $style_storage = $this->entityTypeManager->getStorage('image_style');// ImageStyle::load($config['banner_style']);
      $style =  $style_storage->load($config['banner_style']);
      
      $width = NULL;
      $height = NULL;

      foreach ($style->getEffects() as $effect_uuid => $effect) {
        if (!empty($effect->getConfiguration()['data']) && (isset($effect->getConfiguration()['data']['width']) || isset($effect->getConfiguration()['data']['height']))) {
                
          $data = $effect->getConfiguration()['data'];

          $attached['fixed_banners'][$id] = $data;
                
          if ($config['banner_set_fixed_width'] && isset($config['banner_fixed_width']) && !empty($config['banner_fixed_width'])) {
            
            $width = $config['banner_fixed_width'];
            
            if (isset($data['height']) && !empty($data['height'])) {
              $temp_ratio = $data['width'] / $data['height'];
            }
            else {
              $temp_ratio = $data['width'] / $data;
            }
            $height = $width / $temp_ratio;
          }
          else {
            $width = isset($data['width']) ? $data['width'] : 1920;
            $height = isset($data['height']) && !empty($data['height']) ? $data['height'] : 720;
          }
                
          if ($width && $height) {
            $attached['fixed_banners'][$id]['ratio'] = $width / $height;
          }

          $attached['fixed_banners'][$id]['uri'] = $style->buildUri($image['#uri']);
          $attached['fixed_banners'][$id]['url'] = $style->buildUrl($image['#uri']);
        }
      }
      
      if ($config['banner_background']) {

        $uri = $image['#uri'];
        
        // In case we need createDerivate, uncomment the next line, maybe
        // $style->createDerivative($uri,  $style->buildUri($uri));
        $image = $style->buildUrl($uri);
        
        $attached['fixed_banners'][$id]['css_bg'] = TRUE;
        $config['css_bg'] = TRUE; 
      }
      else {
        $image = [
          '#theme' => 'image_style',
          '#uri' => $image['#uri'],
          '#style_name' => $config['banner_style'],
          '#width' => $width,
          '#height' => $height,
          '#attributes' => [
            'class' => []
          ] 
        ]; 
      }
    }
    else {
      $image = [
        '#theme' => 'image',
        '#uri' => $image['#uri'],
        '#width' => $image_data['width'], 
        '#height' => $image_data['height'],
        '#attributes' => [
          'class' => []
        ] 
      ]; 
    }
          
    $build['#theme'] = 'nk_tools_fixed_banner';
    $build['#image'] = $image;
    $build['#caption'] = $caption;
    $build['#node'] = $node;
    
    $config['caption_position'] = $node->hasField('field_banner_caption_position') && !empty($node->get('field_banner_caption_position')->getValue()) ? $node->get('field_banner_caption_position')->getValue()[0]['value'] : 'center_middle';
    $config['color'] = $node->hasField('field_banner_background') && !empty($node->get('field_banner_background')->getValue()) ? $node->get('field_banner_background')->getValue()[0]['color'] : 'transparent';
    $config['opacity'] = $node->hasField('field_banner_background') && !empty($node->get('field_banner_background')->getValue()) ? $node->get('field_banner_background')->getValue()[0]['opacity'] : '1';
        
    $build['#config'] = $config;

    $attributes = [
      'id' => $id,
      'class' => [
        'relative'  
      ]
    ];
    
    $attached['fixed_banners'][$id]['config'] = $config; 
    $build['#attached']['drupalSettings']['nk_tools'] = $attached;
        
    $build['#attached']['library'][] = 'nk_tools/fixed_banner'; 
        
    return $build; 

  }

}