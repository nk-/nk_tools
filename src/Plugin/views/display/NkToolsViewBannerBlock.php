<?php

namespace Drupal\nk_tools\Plugin\views\display;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\Block;
use Drupal\views\Plugin\Block\ViewsBlock;

/**
 * The plugin that handles a block.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "nk_tools_view_banner_block",
 *   title = @Translation("Nk tools View Banner Block"),
 *   help = @Translation("A View display as a block, with some custom features such as uploadable banner image."),
 *   theme = "view_banner_image",
 *   register_theme = FALSE,
 *   uses_hook_block = TRUE,
 *   contextual_links_locations = {"block"},
 *   admin = @Translation("Nk tools View Banner Block")
 * )
 *
 * @see \Drupal\views\Plugin\Block\ViewsBlock
 * @see \Drupal\views\Plugin\Derivative\ViewsBlock
 */
class NkToolsViewBannerBlock extends Block {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['block_hide_mobile'] = ['default' => FALSE];
    $options['block_banner_image'] = ['default' => TRUE];
    $options['block_category'] = ['default' => $this->t('Nk tools (Views)')];
    
    $options['allow'] = [
      'contains' => [
        //'items_per_page' => ['default' => 'items_per_page'],
        'block_hide_mobile' => ['default' => TRUE],
        'block_banner_image' => ['default' => TRUE],
      ],
    ];

    return $options;
  }

  /**
   * Returns plugin-specific settings for the block.
   *
   * @param array $settings
   *   The settings of the block.
   *
   * @return array
   *   An array of block-specific settings to override the defaults provided in
   *   \Drupal\views\Plugin\Block\ViewsBlock::defaultConfiguration().
   *
   * @see \Drupal\views\Plugin\Block\ViewsBlock::defaultConfiguration()
   */
  public function blockSettings(array $settings) {
    $settings = parent::blockSettings($settings);
    $settings['block_hide_mobile'] = FALSE;
    $settings['block_banner_image'] = TRUE;
    return $settings;
  }

  /**
   * The display block handler returns the structure necessary for a block.
   */
  public function execute() {
    // Prior to this being called, the $view should already be set to this
    // display, and arguments should be set on the view.
    $element = $this->view->render();
    if ($this->outputIsEmpty() && $this->getOption('block_hide_empty') && empty($this->view->style_plugin->definition['even empty'])) {
      return [];
    }
    else {
      return $element;
    }
  }
 

  /**
   * Provide the summary for page options in the views UI.
   *
   * This output is returned as an array.
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    //['override', 'block_hide_mobile']

    $options['block_hide_mobile'] = [
      'category' => 'block',
      'title' => $this->t('Do not display on mobile'),
      'value' => $this->getOption('block_hide_mobile') ? $this->t('Yes') : $this->t('No'),
    ];
  
    $options['block_banner_image'] = [
      'category' => 'block',
      'title' => $this->t('Set Banner Image'),
      'value' => $this->getOption('block_banner_image') ? $this->t('Yes') : $this->t('No'),
    ];

    $filtered_allow = array_filter($this->getOption('allow'));

    $options['allow'] = [
      'category' => 'block',
      'title' => $this->t('Allow settings'),
      'value' => empty($filtered_allow) ? $this->t('None') : $this->t('Some'),
    ];

  }

  /**
   * Provide the default form for setting options.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state->get('section')) {
      
      case 'block_hide_mobile':
        $form['#title'] .= $this->t('Do not display on mobile settings');

        $form['block_hide_mobile'] = [
          '#title' => $this->t('Do not display on mobile'),
          '#type' => 'checkbox',
          '#description' => $this->t('Hide the block on smaller screens, it\'s basically just a single CSS rule that should apply globally so here too'),
          '#default_value' => $this->getOption('block_hide_mobile'),
        ];

      break;

      case 'allow':
        $form['#title'] .= $this->t('Allow settings in the block configuration');

        $options = [
         // 'items_per_page' => $this->t('Items per page'),
          'block_hide_mobile' => $this->t('Do not display on mobile'),
        ];

        $allow = array_filter($this->getOption('allow'));
        $form['allow'] = [
          '#type' => 'checkboxes',
          '#default_value' => $allow,
          '#options' => $options,
        ];
       break; 


       case 'block_banner_image':
          $form['#title'] .= $this->t('Set banner image for other displays');
          $form['block_banner_image'] = [
            '#title' => $this->t('Set banner image'),
            '#type' => 'checkbox',
            '#description' => $this->t('Set dynamic banner image via uploadable field on block.'),
            '#default_value' => $this->getOption('block_banner_image'),
        ];
 
       break; 


    }
  }

  /**
   * Perform any necessary changes to the form values prior to storage.
   * There is no need for this function to actually store the data.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);
    $section = $form_state->get('section');
    switch ($section) {
      case 'allow':
      case 'block_hide_mobile':
      case 'block_banner_image':
        $this->setOption($section, $form_state->getValue($section));
      break;
    }
  }

  /**
   * Adds the configuration form elements specific to this views block plugin.
   *
   * This method allows block instances to override the views items_per_page.
   *
   * @param \Drupal\views\Plugin\Block\ViewsBlock $block
   *   The ViewsBlock plugin.
   * @param array $form
   *   The form definition array for the block configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The renderable form array representing the entire configuration form.
   *
   * @see \Drupal\views\Plugin\Block\ViewsBlock::blockForm()
   */
  public function blockForm(ViewsBlock $block, array &$form, FormStateInterface $form_state) {
    $allow_settings = array_filter($this->getOption('allow'));

    $block_configuration = $block->getConfiguration();

    foreach ($allow_settings as $type => $enabled) {
      if (empty($enabled)) {
        continue;
      }
      switch ($type) {

          case 'block_hide_mobile':
            $form['override']['block_hide_mobile'] = [
              '#type' => 'checkbox',
              '#title' => $this->t('Do not show on mobile'),
              '#default_value' =>  $block_configuration['block_hide_mobile'],
            ];

          break;

          case 'block_banner_image':
            $form['override']['block_banner_image'] = [
              '#type' => 'managed_file',
              '#title' => t('Banner image'),
              '#multiple' => TRUE,
              '#default_value' => isset($block_configuration['block_banner_image']) ? $block_configuration['block_banner_image'] : NULL, 
              '#upload_validators' => [
                'file_validate_extensions' => ['gif png jpg jpeg'],
                 //'file_validate_size' => [25600000],
              ],
              '#theme' => 'image_widget',
              '#preview_image_style' => 'medium',
              '#upload_location' => 'public://',
              '#required' => FALSE,
              //'#process' => [get_class($this) .'::process'], //::process($form['banner_image'], $form_state, $form),
              '#title_field' => TRUE, 
              '#title_field_required' => TRUE,
            ];


          break; 

       }
    }

    return $form;
  }

  /**
   * Handles form validation for the views block configuration form.
   *
   * @param \Drupal\views\Plugin\Block\ViewsBlock $block
   *   The ViewsBlock plugin.
   * @param array $form
   *   The form definition array for the block configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\views\Plugin\Block\ViewsBlock::blockValidate()
   */
  public function blockValidate(ViewsBlock $block, array $form, FormStateInterface $form_state) {
  }

  /**
   * Handles form submission for the views block configuration form.
   *
   * @param \Drupal\views\Plugin\Block\ViewsBlock $block
   *   The ViewsBlock plugin.
   * @param array $form
   *   The form definition array for the full block configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\views\Plugin\Block\ViewsBlock::blockSubmit()
   */
  public function blockSubmit(ViewsBlock $block, $form, FormStateInterface $form_state) {
    
    parent::blockSubmit($block, $form, $form_state);

    $block_hide_mobile = $form_state->getValue(['override', 'block_hide_mobile']);
    $block_banner_image = $form_state->getValue(['override', 'block_banner_image']);
    
    if ($block_hide_mobile > -1) {
      $block->setConfigurationValue('block_hide_mobile', $block_hide_mobile);
    }
    $form_state->unsetValue(['override', 'block_hide_mobile']);
  
    $block->setConfigurationValue('block_banner_image', $block_banner_image);

  }

  /**
   * Allows to change the display settings right before executing the block.
   *
   * @param \Drupal\views\Plugin\Block\ViewsBlock $block
   *   The block plugin for views displays.
   */
  /*
  public function preBlockBuild(ViewsBlock $block) {
    parent::preBlockBuild($block);
    $config = $block->getConfiguration();
  }
  */

}
