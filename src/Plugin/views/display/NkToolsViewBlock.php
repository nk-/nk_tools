<?php

namespace Drupal\nk_tools\Plugin\views\display;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\Block\ViewsBlock;
// use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\Block;

/**
 * The plugin that handles a block.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "nk_tools_view_block",
 *   title = @Translation("Nk tools View block"),
 *   help = @Translation("Display the view as a block, with some custom features."),
 *   theme = "views_view",
 *   register_theme = FALSE,
 *   uses_hook_block = TRUE,
 *   contextual_links_locations = {"block"},
 *   admin = @Translation("Nk tools View block")
 * )
 *
 * @see \Drupal\views\Plugin\Block\ViewsBlock
 * @see \Drupal\views\Plugin\Derivative\ViewsBlock
 */
class NkToolsViewBlock extends Block {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['block_hide_mobile'] = ['default' => NULL];
    $options['block_hide_init'] = ['default' => NULL];
    $options['block_collapsible'] = ['default' => TRUE];
    $options['block_collapsible_item'] = ['default' => NULL];  
    $options['block_additional_class'] = ['default' => NULL];
    //$options['block_label'] = ['default' => ['format' => 'basic_html', 'value' => NULL]];
    $options['block_category'] = ['default' => $this->t('Nk tools (Views)')];

    $options['allow'] = [
      'contains' => [
        'items_per_page' => ['default' => 'items_per_page'],
        'block_hide_mobile' => ['default' => 'block_hide_mobile'],
        'block_hide_init' => ['default' => 'block_hide_init'],
        'block_collapsible' => ['default' => 'block_collapsible'],
        'block_collapsible_item' => ['default' => 'block_collapsible_item'],
        'block_additional_class' => ['default' => 'block_additional_class'], 
        //'block_label' => ['default' => ['format' => 'basic_html', 'value' => NULL]],
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
    $settings['block_hide_mobile'] = NULL;
    $settings['block_hide_init'] = NULL;
    $settings['block_collapsible'] = NULL;
    $settings['block_collapsible_item'] = NULL;
    $settings['block_additional_class'] = NULL; 
    /*
    $settings['block_label'] = [
      'format' => 'basic_html',
      'value' => NULL, 
    ];
    */
    return $settings;
  }

  /**
   * The display block handler returns the structure necessary for a block.
   */
  /*
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
  */

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

    $options['block_hide_init'] = [
      'category' => 'block',
      'title' => $this->t('Hide initially'),
      'value' => $this->getOption('block_init') ? $this->t('Yes') : $this->t('No'),
    ];

    $options['block_collapsible'] = [
      'category' => 'block',
      'title' => $this->t('Collapsible pane'),
      'value' => $this->getOption('block_collapsible') ? $this->t('Yes') : $this->t('None'),
    ];

    $options['block_collapsible_item'] = [
      'category' => 'block',
      'title' => $this->t('Collapsible pane toggle'),
      'value' => !empty($this->getOption('block_collapsible_item')) ? $this->getOption('block_collapsible_item') : $this->t('None'),
    ];

    $options['block_additional_class'] = [
      'category' => 'block',
      'title' => $this->t('Additional CSS class for this block'),
      'value' => !empty($this->getOption('block_additional_class')) ? $this->getOption('block_additional_class') : $this->t('None'), 
    ];

    /*
    $options['block_label'] = [
      'category' => 'block',
      'title' => $this->t('A real title for this block'),
      'value' => isset($this->getOption('block_label')['value']) && !empty($this->getOption('block_label')['value']) ? $this->getOption('block_label')['value'] : $this->t('None'),
    ];
    */

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
        $form['block_hide_mobile'] = [
          '#title' => $this->t('Do not display on mobile'),
          '#type' => 'checkbox',
          '#description' => $this->t('Hide the block on smaller screens, it\'s basically just a single CSS rule that should apply globally so here too'),
          '#default_value' => $this->getOption('block_hide_mobile'),
        ];

      break;

      case 'block_hide_init':
        $form['block_hide_init'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Hide initially'),
          '#description' => $this->t('This block is initially hidden in CSS, meant to be shown upon some "action", i.e. playing a video that was previously hidden.'),
          '#default_value' => $this->getOption('block_hide_init'),
        ];
      break;

      case 'block_collapsible':
        $form['block_collapsible'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Collapsible pane'),
          '#description' => $this->t('Render this block as collapsible pane. Note, to achieve such UI only a twig template applies, provided via hook_theme_suggestions_block_alter()'),
          '#default_value' => $this->getOption('block_collapsible'),
          '#attributes' => ['id' => 'collapsible-pane-use'], 
        ];
      break;

      case 'block_collapsible_item':
        $form['block_collapsible_item'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Collapsible pane toggle'),
          '#description' => $this->t('Icon name or CSS class base name for collapsible pane\'s toggle. In case of icon enter only its name like <em>updates</em> else if it is CSS class then please enter with a leading dot like <em>.link-updates</em>.'),
          '#default_value' => $this->getOption('block_collapsible_item'),
          //'#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
          //  'visible' => [
           //   ':input[id="collapsible-pane-use"]' => ['checked' => TRUE],
          //  ],
         // ],
        ];
      break;

      case 'block_additional_class':
        $form['block_additional_class'] = [
          '#title' => $this->t('Additional CSS class for this block'),
          '#type' => 'textfield',
          '#description' => $this->t('Similar setting in a View applies class on view itself and not on the parent block as we achieve thought this setting. Enter just a class name, without "."'),
          '#default_value' => $this->getOption('block_additional_class'),
        ];
      break; 

      /*
      case 'block_label': 
        $form['block_label'] = [
          '#base_type' => 'textfield',
          '#type' => 'text_format',
          '#title' => $this->t('Block label'),
          '#description' => $this->t('The real title for this block.'),
          '#format' => $this->getOption('block_label')['format'],
          '#default_value' => $this->getOption('block_label')['value'],  
        ];
      break; 
      */

      case 'allow':
        $options = [
          'items_per_page' => $this->t('Items per page'),
          //'block_label' => $this->t('Block label'),
          'block_hide_mobile' => $this->t('Do not display on mobile'),
          'block_hide_init' => $this->t('Hide initially'),
          'block_collapsible' => $this->t('Collapsible pane'),
          'block_collapsible_item' => $this->t('Collapsible pane toggle'),
          'block_additional_class' => $this->t('Additional CSS class for this block'),
        ];

        $allow = array_filter($this->getOption('allow'));
        $form['allow'] = [
          '#title' =>  $this->t('Allow settings in the block configuration'),
          '#type' => 'checkboxes',
          '#default_value' => $allow,
          '#options' => $options,
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
      case 'block_hide_init':
      case 'block_additional_class':
      case 'block_collapsible':
      case 'block_collapsible_item':
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
  //public function blockForm($form, FormStateInterface $form_state) {
  public function blockForm(ViewsBlock $block, array &$form, FormStateInterface $form_state) {
    $form = parent::blockForm($block, $form, $form_state);
    $allow_settings = array_filter($this->getOption('allow'));

    $block_configuration = $block->getConfiguration();

    /*
    $form['label']['#type'] = 'text_format';
    $form['label']['#base_type'] = 'textfield';
    $form['label']['#format'] = isset($block_configuration['label']) && isset($block_configuration['label']['format']) && !empty($block_configuration['label']['format']) ? $block_configuration['label']['format'] : 'basic_html';
    $form['label']['#default_value'] =  isset($block_configuration['label']) && isset($block_configuration['label']['value']) && !empty($block_configuration['label']['value']) ? $block_configuration['label']['value'] : $this->view->getTitle();
    $form['label']['#value'] = $form['label']['#default_value'];
    */

    foreach ($allow_settings as $type => $enabled) {
      if (empty($enabled)) {
        continue;
      }
      switch ($type) {
        case 'items_per_page':
          $form['override']['items_per_page'] = [
            '#type' => 'select',
            '#title' => $this->t('Items per block'),
            '#options' => [
              'none' => $this->t('@count (default setting)', ['@count' => $this->getPlugin('pager')->getItemsPerPage()]),
              5 => 5,
              10 => 10,
              20 => 20,
              40 => 40,
            ],
            '#default_value' => $block_configuration['items_per_page'],
          ];
        break;


        case 'block_hide_mobile':
          $form['override']['block_hide_mobile'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Do not show on mobile'),
            '#description' => $this->t('Hide the block on smaller screens, it\'s basically just a single CSS rule that should apply globally so here too'),
            '#default_value' =>  $block_configuration['block_hide_mobile'],
          ];

        break;

        case 'block_hide_init':
          $form['override']['block_hide_init'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Hide initially'),
            '#description' => $this->t('This block is initially hidden in CSS, meant to be shown upon some "action", i.e. playing a video that was previously hidden.'),
            '#default_value' =>  $block_configuration['block_hide_init'],
          ];
        break;

        case 'block_collapsible':
          $form['override']['block_collapsible'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Collapsible pane'),
            '#description' => $this->t('Render this block as collapsible pane. Note, to achieve such UI only a twig template applies, provided via hook_theme_suggestions_block_alter()'),
            '#default_value' => $block_configuration['block_collapsible'],
            '#attributes' => ['id' => 'collapsible-pane-use'],
          ];
        break;

        case 'block_collapsible_item':
          $form['override']['block_collapsible_item'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Collapsible pane toggle'),
            '#description' => $this->t('Icon name or CSS class base name for collapsible pane\'s toggle. In case of icon enter only its name like <em>updates</em> else if it is CSS class then please enter with a leading dot like <em>.link-updates</em>.'),
            '#default_value' => $block_configuration['block_collapsible_item'],
            '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
              'visible' => [
                ':input[id="collapsible-pane-use"]' => ['checked' => TRUE],
              ],
            ],
          ];
        break;

         /*
        case 'block_label':  
          $form['override']['block_label'] = [
            '#base_type' => 'textfield',
            '#type' => 'text_format',
            '#title' => $this->t('Block label'),
            '#description' => $this->t('The real title for this block.'),
            '#format' => isset($block_configuration['block_label']['format']) ? $block_configuration['block_label']['format'] : 'basic_html',
            '#default_value' => isset($block_configuration['block_label']['value']) ? $block_configuration['block_label']['value'] : '',  
          ];

        break;
        */

        case 'block_additional_class':
          $form['override']['block_additional_class'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Additional CSS class for this block'),
            '#default_value' =>  $block_configuration['block_additional_class'],
            '#description' => $this->t('Similar setting in a View applies class on view itself and not on the parent block as we achieve thought this setting. Enter just a class name, without "."'),
          ];
        break; 
      }
    }



    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit(ViewsBlock $block, $form, FormStateInterface $form_state) {
    //parent::blockSubmit($block, $form, $form_state);
    $values = $form_state->getValues();
    $values = $form_state->getValues();
    if (isset($values['override'])) { 
      foreach ($values['override'] as $key => $property) {
        $block->setConfigurationValue($key, $property);
      }
    }
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
  /*
  public function blockValidate(ViewsBlock $block, array $form, FormStateInterface $form_state) {
  }
  */

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
  /*
  public function blockSubmit(ViewsBlock $block, $form, FormStateInterface $form_state) {


    $values = $form_state->getValues();
    if (isset($values['override'])) {
      foreach ($values['override'] as $key => $property) {
        $this->configuration['settings'][$key] = $property;
      }
    }
    ksm($this->configuration);
    parent::blockSubmit($block, $form, $form_state);
  }
  */

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
    if ($config['block_hide_mobile'] !== 'none') {
      $this->view->setItemsPerPage($config['items_per_page']);
    }
  }
  */
}