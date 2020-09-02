<?php

namespace Drupal\nk_tools\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Render\Markup;
use Drupal\node\NodeInterface;
use Drupal\field\Entity\FieldConfig;

use Drupal\nk_tools\Plugin\Block\NkToolsBlockBase;

/**
 * Provides block with Media entity downloads
 *
 * @Block(
 *   id = "nk_tools_media_downloads_block",
 *   admin_label = @Translation("Media downloads"),
 *   category = @Translation("Nk tools"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Node")
 *     )
 *   } 
 * )
 */
class NkToolsMediaDownloadsBlock extends NkToolsBlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) { 
    return parent::blockForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  public function build() {
   
    $config = $this->getConfiguration();

    /* 
     * Pick our node object from context
     * var \Drupal\node\Entity\Node 
     */
    $node = $this->getContextValue('node');

    if (!$node instanceof NodeInterface) {
      return [];
    }

    $media_items = $this->loadMediaItems($node);
    $build = [];
     
    if (!empty($media_items)) {
    
      $build['content'] = [
        '#theme' => 'item_list',
        '#items' => [], 
        '#attributes' => [
          'class' => [
            'list-none',
            'navigation',
            'media-downloads'
          ]
        ], 
      ];
    
      if (isset($config['block_additional_class']) && !empty($config['block_additional_class'])) {
        $build['content']['#attributes']['class'][] = $config['block_additional_class'];
      }

      $file_storage = $this->entityTypeManager->getStorage('file');
      foreach ($media_items as $mid => $media_item) {
        $file_source = $media_item->getSource();
        $file_source_field = $file_source->configuration['source_field'];
        $file = $media_item->get($file_source_field)->entity;
        $url = $file ? file_create_url($file->getFileUri()) : $media_item->getName();
         
        $target_id = $file_source->getSourceFieldValue($media_item);

        $options = [
           'attributes' => [
            'target' => 'blank_',
            //'download' => $media_item->getName(), // Does NOT work because of the redirection route we need to direct to file itself
            'title' => $media_item->getName(),
            'alt' => $url,
            'class' => [
              'media-downloads-link'
            ]
          ]
        ];

        $title =  $media_item->getName();

        // Temp diplo hardcode, @see diplo_forms_block_view_nk_tools_media_downloads_block_alter()
        if ($media_item->hasField('field_language') && !empty($media_item->get('field_language')->getValue())) { 
          $title = isset($media_item->get('field_language')->getValue()[0]['value']) && !empty($media_item->get('field_language')->getValue()[0]['value']) ? strtoupper($media_item->get('field_language')->getValue()[0]['value']) : $media_item->getName();
        }
        else {
          $title =  $media_item->getName();
        }
        $build['content']['#items'][] = $media_item->toLink($title, 'canonical', $options);
      }
 
    }
   
    return $build; 
  }

  /**
   * A custom method, load and render targetted Media items
   */
  protected function loadMediaItems(NodeInterface $node) {

    $fields = $this->nkToolsFactory->getEntityFields('node', $node->getType());
    $media_items = [];
    $media_storage = $this->entityTypeManager->getStorage('media');

    foreach ($fields as $field_name => $field_values) { //field_pdf_media_path'
      if ($field_values['field_type'] == 'entity_reference' && isset($field_values['settings']) && isset($field_values['settings']['handler']) && strpos($field_values['settings']['handler'], 'media') !== FALSE) {
        foreach ($node->get($field_name)->getValue() as $delta => $value) {
          if (isset($value['target_id']) && !empty($value['target_id'])) {
            $media_items[$value['target_id']] = $media_storage->load($value['target_id']); 
          }
        }
      }
    } 
    return $media_items;
  }

}
