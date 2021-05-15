<?php

namespace Drupal\nk_tools\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Block\TitleBlockPluginInterface;
use Drupal\Core\Template\Attribute;

use Drupal\filter\Render\FilteredMarkup;

use Drupal\nk_tools\Plugin\Block\NkToolsBlockBase;

/**
 * Provides a block to display the page title.
 *
 * @Block(
 *   id = "nk_tools_page_title_block",
 *   admin_label = @Translation("Nk tools Page title"),
 *   forms = {
 *     "settings_tray" = FALSE,
 *   },
 * )
 */
class NkToolsPageTitleBlock extends NkToolsBlockBase implements TitleBlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'description' => [
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

    $form['description'] = [
      '#base_type' => 'textarea',
      '#type' => 'text_format',
      '#title' => $this->t('Append content'),
      '#description' => $this->t('Enter any simple content here to append to page title.'), 
      '#format' => $config['description']['format'],
      '#default_value' => $config['description']['value'],  
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $config = $this->getConfiguration();
  
    $title_attributes = [
      'class' => []
    ];

    $build = parent::build() + [
      '#theme' => 'nk_tools_page_title',
      '#title' => $this->title,
      '#title_attributes' => new Attribute($title_attributes)
    ];

    $description = !empty($config['description']) && isset($config['description']['value']) && !empty($config['description']['value']) ? $config['description']['value'] : NULL; 
    if ($description) {
      $build['#description'] =  FilteredMarkup::create($description);
      $description_attributes = [
        'class' => []
      ];
      $build['#description_attributes'] = new Attribute($description_attributes);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['description'] = $values['description']; 
  }

  /**
   * {@inheritdoc}
   *
   * Mandatory parent method
   */
  public function setTitle($title) {
    $this->title = $title;
    return $this;
  }

}