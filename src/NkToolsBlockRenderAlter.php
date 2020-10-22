<?php

namespace Drupal\nk_tools;

use Drupal\Core\Render\Element\RenderCallbackInterface;
//use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\node\NodeInterface;

/**
 * Implements a trusted preRender callback.
 */
class NkToolsBlockRenderAlter extends NkToolsBase implements RenderCallbackInterface {

  /**
   * #pre_render callback for a block to alter its content.
   */
  public static function preRenderBanner(array $build) {
  
    $route = \Drupal::service('current_route_match');
    $node_object = $route->getParameters()->has('node') ? $route->getParameter('node') : NULL;
    $view_id = $route->getParameters()->has('view_id') ? $route->getParameter('view_id') : NULL;
    
    $video_config = [
      'context_mapping' => [ // Essential for target block that requires node object in context
        'node' => NULL,
      ],
      'set_config' => TRUE,
      'hide_init' => TRUE,
    ];

    if ($node_object && $node_object->getType() == 'course') {
      $node = $node_object;
      $video_config['context_mapping']['node'] = $node_object;
      if ($video = \Drupal::service('nk_tools.main_service')->getBlock('rtp_brightcove_players_block', 'plugin', $video_config)) {
        $build['content']['#video'] = $video;
      }
      return $build;  
    }
    else if ($view_id) {
      $nk_tools_factory = \Drupal::service('nk_tools.main_service');
      if (!$nk_tools_factory) {
        return $build;
      }

      $display_id = $route->getParameters()->has('display_id') ? $route->getParameter('display_id') : NULL;
      if ($display_id) {// && $display_id == 'banner_selection') {
        //ksm($build);

        $view = $nk_tools_factory->getView($view_id, 'banner_selection'); //, array $arguments = [], $render = NULL, $json = NULL, array &$data = [])    
        //$first_row = $view['#rows'][0]['#rows'][0];
        //$nid = isset($first_row['#node']) ? $first_row['#node']->getTitle() : NULL;
        //$build['#video'] = [];

        foreach ($view['#rows'] as $rows) {
         foreach($rows['#rows'] as $delta => $row) {
            if (isset($row['#node']) && $row['#node'] instanceof NodeInterface) {
              if ($row['#node']->hasField('field_brightcove_playlist') || $row['#node']->hasField('field_brightcove_audio_playlist')) {
                if ($row['#node']->getType() == 'course' && (!empty($row['#node']->get('field_brightcove_playlist')->getValue()) || !empty($row['#node']->get('field_brightcove_audio_playlist')->getValue()))) {
                   $video_config['context_mapping']['node'] = $row['#node'];
                   //$video_config['block_id'] = 'block-'. Html::getUniqueId($row['#node']->id());

                   if ($video = \Drupal::service('nk_tools.main_service')->getBlock('rtp_brightcove_players_block', 'plugin', $video_config)) {
            //$node = $row['#node']; 
                     $build['#video'] = $video;
                     // if ($item['#node']->id() == 93 || $item['#node']->id() == 154) {
                     //   ksm($item);
                    //  }


                         return $build; 
                   }
                   //$node = $row['#node'];
                   //break;      
                }
              }
            }
          } 
        }
/*
        if ($node) {
          $video_config['context_mapping']['node'] = $node;
          if ($video = \Drupal::service('nk_tools.main_service')->getBlock('rtp_brightcove_players_block', 'plugin', $video_config)) {
            //$node = $row['#node']; 
             $build['#video'] = $video;
          }
        }
*/
      }
      
    }
    //ksm($build['#video']);

    //return $build;
  }
}