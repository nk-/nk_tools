<?php

namespace Drupal\nk_tools\Plugin\views\style;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

use Drupal\node\Entity\Node;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render each item in an ordered or unordered list.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "nk_tools_collapsible_pane",
 *   title = @Translation("Nk tools Collapsible pane"),
 *   help = @Translation("Display the results as collapsible pane."),
 *   theme = "views",
 *   display_types = {"normal"}
 * )
 */
class NkToolsCollapsiblePaneViewStyle extends StylePluginBase {
  
  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesFields = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * {@inheritdoc}
   */
  public function evenEmpty() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->definition = $plugin_definition + $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    
    $options['group_by'] = ['default' => 'field'];
    $options['group_by_icon'] = ['default' => NULL];
    $options['group_by_class'] = ['default' => NULL];
    
    // Ensure unique id attribute for each instance of Flexbox grid (when multiple on the same page)
    // User can change this on settings but we try to make sure some unique id is auto assigned
    $view_id = $this->view->id();
    $current_display = $this->view->current_display;
    $options['id'] = ['default' => 'collapsible_pane-' . $view_id . '-' . $current_display]; 

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['collapsible_pane'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Collapsible pane'),
      '#description' => $this->t('Implementation of Nk tools collapsible pane widget'),
    ];

    $form['collapsible_pane']['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unique ID attribute'),
      '#description' => $this->t('This is should make possible/easier to handle multiple grids of this kind on the same page'),
      '#size' => 40,
      '#maxlength' => 255,
      '#default_value' => $this->options['id'],
    ];

    $form['collapsible_pane']['group_by'] = [
      '#type' => 'radios',
      '#default_value' => $this->options['group_by'],
      '#options' => [
        'field' => 'Field',
        'icon' => 'Icon',
        'class' => 'Class', 
      ],
      '#attributes' => [
        'id' => 'group-by', 
      ]
    ];

    $form['collapsible_pane']['group_by_icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Grouping icon'),
      '#description' => $this->t('If collapsible toggle is icon or image specify its name here.'),
      '#default_value' => $this->options['group_by_icon'],
        '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
        'visible' => [
          ':input[id="group-by"]' => ['value' => 'icon'],
        ],
      ],
    ];

    $form['collapsible_pane']['group_by_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Grouping class'),
      '#description' => $this->t('If collapsible toggle is CSS defined enter the class here, without leading dot'),
      '#default_value' => $this->options['group_by_class'],
      '#states' => [ // @see https://www.drupal.org/docs/8/api/form-api/conditional-form-fields
        'visible' => [
          ':input[id="group-by"]' => ['value' => 'class'],
        ],
      ],

    ];

  }

  /**
   * {@inheritdoc}
   */
  /*
  public function preRender($result) {
    if (!empty($this->view->rowPlugin)) {
      $this->view->rowPlugin->preRender($result);
    }
  }
  */

  /**
   * {@inheritdoc}
   */
  public function render() {

    // Group the rows according to the grouping field, if specified.
    $sets = parent::render();

    // Render each group separately and concatenate.
    $output = $sets;
     //$sets[0]['#view']->getDisplay('page_2')); //$this->view->style_plugin->options);
    $items = [];
    $id = !empty($this->options['grouping']) && !empty($this->options['grouping'][0]) ? Html::getUniqueId('collapsible_pane-group-' . $this->options['grouping'][0]['field'] . '-' . $key) : $this->options['id'];
    
    $items[0] = [
      'id' => 'collapsible-' . $id,
      'label' => 'Test',
      'target' => $id,
      'content' => [],
      'value' => isset($content[$date_group]) && !empty($content[$date_group]) ? Markup::create(implode('', $content[$date_group])) : '', //Markup::create($content),
      'toggle_attributes' => [],
      'pane_wrapper_attributes' => NULL,
      'hook' => NULL,
      'list_type' => 'ul',
    ]; 

    ksm($sets);

    foreach ($sets as $key => &$set) {
      foreach ($set['rows'] as $index => $row) {
        $this->view->row_index = $index;
        $items[0]['content'][] = $this->view->rowPlugin->render($row);
      }
   }
   
   $build = [
     '#theme' => 'nk_tools_collapsible_pane',
     '#block_id' => $id,
     '#count_items' => count($items),
     '#items' => $items,
     '#attributes' => [
       'class' => ['nk-tools-collapsible-pane'],
     ],
   ];
   
    // Add some libraries
    //$output[0]['#attached']['library'][] = 'nk_tools/collapsible_pane';
   return $build;
  
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);

    $options = $form_state->getValue(['style_options', 'collapsible_pane']);

    foreach ($options as $key => $value) {
      $form_state->setValue(['style_options', $key], $value);
    }

    $form_state->setValue(['style_options', 'collapsible_pane'], NULL);
  }
}