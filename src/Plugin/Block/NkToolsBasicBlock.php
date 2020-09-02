<?php

namespace Drupal\nk_tools\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;

use Drupal\nk_tools\Plugin\Block\NkToolsBlockBase;

/**
 * Provides an example block.
 *
 * @Block(
 *   id = "nk_tools_basic_block",
 *   admin_label = @Translation("Basic block"),
 *   category = @Translation("Nk tools")
 * )
 */
class NkToolsBasicBlock extends NkToolsBlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'block_variable' => NULL,
      'block_content' => [
        'format' => 'basic_html',
        'value' => NULL,
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['block_variable'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Variable'),
      '#description' => $this->t('Can be any variable(s) we need, for instance for usage in twig template for this block or for theme suggestion. Comma+space separate multiple entries, like for example <em>fullwidth, bg</em>.'),
      '#default_value' => $config['block_variable'],  
    ];

    $form['block_content'] = [
      '#base_type' => 'textarea',
      '#type' => 'text_format',
      '#title' => $this->t('Content'),
      '#description' => $this->t('Enter any simple content here'),
      '#format' => $config['block_content']['format'],
      '#default_value' => $config['block_content']['value'],  
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    
    $config = $this->getConfiguration();

    if (isset($config['block_variable']) && !empty($config['block_variable'])) {
   
      $variables = strpos($config['block_variable'], ',') !== FALSE ? explode(', ', $config['block_variable']) : [$config['block_variable']];
    
      if (in_array('anchorlinks', $variables)) {
        
        $route = \Drupal::service('current_route_match');
    
        //$is_node = $route->getParameters()->has('node') ? $route->getParameter('node') : NULL;
        //$view_id = $route->getParameters()->has('view_id') ? $route->getParameter('view_id') : NULL;
        ksm($this->currentRoute); 


        if ($node = $route->getParameter('node')) {
          $paragraphs = [];
          $bundle = $node->getType();
          if (count($variables) > 2) {
            $paragraphs[$variables[1]] = [
              'title' => $variables[2],
              'teaser' => isset($variables[3]) && !empty($variables[3]) ? $variables[3] : NULL,
            ]; 
          }
          elseif (count($variables) == 2) {
             $paragraphs[$variables[1]] = [
               'title' => 'Link'
             ];
          }
          else {

          }
         //$nk_tools_factory = \Drupal::service('nk_tools.main_service');
         $build['content'] = $this->nkToolsFactory->paragraphLinks($node, $paragraphs);
      }
      else {
        $build['content'] = ['#markup' => $config['block_content']['value']];
      }

    }
    else {
      $build['content'] = ['#markup' => $config['block_content']['value']];
    }

   }
   else { // Just render content from long text field
     $build['content'] = ['#markup' => $config['block_content']['value']];
   }
   
   return parent::build() + $build;

  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $values = $form_state->getValues();

    $this->configuration['block_variable'] = $values['block_variable'];
    $this->configuration['block_content']['value'] = $values['block_content']['value'];
    $this->configuration['block_content']['format'] = $values['block_content']['format'];
  }

}