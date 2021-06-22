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
      'view' => NULL,
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

    $form['view'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include view argument'),
      '#description' => $this->t('It will check if this is a View\'s page and with argument in URL. If such, we append argument value to the page title.'),
      '#default_value' => $config['view'],
    ];

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

    $append_args = NULL;
    if ($config['view'] && $this->currentRoute->getParameters()->has('view_id') && $this->currentRoute->getParameters()->has('display_id')) {
      $view_id_route =  $this->currentRoute->getParameter('view_id');
      $display_id_route = $this->currentRoute->getParameter('display_id');
      $current_args = [];
      foreach ($this->currentRoute->getParameters() as $arg_key => $arg) {
        if (strpos($arg_key, 'arg_') !== FALSE && $arg) {
          $current_args[$arg_key] = $arg;
        }
      }
      if (!empty($current_args)) {
        $append_args = count($current_args) > 1 ? implode(', ', $current_args) : reset($current_args);
      }
    }

    $base_attributes = [
      'class' => []
    ];

    $build = parent::build() + [
      '#theme' => 'nk_tools_page_title',
      '#title' => $this->title,
      '#title_attributes' => new Attribute($base_attributes)
    ];

    if ($append_args) {
      $build['#append_args'] = $append_args; //FilteredMarkup::create(' <em class="text-highlight highlighted">' . $append_args . '</em>');
      $build['#append_args_attributes'] = new Attribute($base_attributes);
    }

    $description = !empty($config['description']) && isset($config['description']['value']) && !empty($config['description']['value']) ? $config['description']['value'] : NULL; 
    if ($description) {
      $build['#description'] =  FilteredMarkup::create($description);
      $build['#description_attributes'] = new Attribute($base_attributes);
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
    $this->configuration['view'] = $values['view']; 
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