<?php

namespace Drupal\nk_tools_swiper\Plugin\views\style;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

use Drupal\node\Entity\Node;
use Drupal\views\Plugin\views\style\StylePluginBase;


use Drupal\nk_tools_swiper\Entity\NkToolsSwiper;

/**
 * Style plugin to render each item in an ordered or unordered list.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "nk_tools_swiper",
 *   title = @Translation("Nk tools Swiper"),
 *   help = @Translation("Display the results in a Swiper widget."),
 *   theme = "nk_tools_swiper",
 *   display_types = {"normal"}
 * )
 */
class NkToolsSwiperViewStyle extends StylePluginBase {
  
  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * Swiper Configuration Entity
   *
   * @var \Drupal\nk_tools_swiper\Entity\ NkToolsSwiper
   */
   public $swiper_storage = NULL;

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
    $options['swiper_template'] = ['default' => 'views_swiper'];
    $options['css_class'] = ['default' => ''];
    
    // Ensure unique id attribute for each instance of Swiper on the same page
    // User can change this on settings but we try to make sure some unique id is auto assigned
    $view_id = $this->view->id();
    $current_display = $this->view->current_display;
    $options['id'] = ['default' => 'swiper-' . $view_id . '-' . $current_display]; 
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['swiper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Nk tools Swiper'),
    ];

    $form['swiper']['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unique ID attribute'),
      '#description' => $this->t('This is mandatory to set different for each Swiper that appears on the same page'),
      '#size' => 40,
      '#maxlength' => 255,
      '#default_value' => $this->options['id'],
    ];

    $form['swiper']['css_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CSS class'),
      '#description' => $this->t('Additional class on parent swiper element'),
      '#size' => 40,
      '#maxlength' => 255,
      '#default_value' => $this->options['css_class'],
    ];

    // Get list of option sets as an associative array.
    $options_storage =  NkToolsSwiper::loadStorage();

    $options = [];
    foreach ($options_storage as $swiper_template => $option) {
      $options[$swiper_template] = $option['label'];
    }

    if (empty($options)) {
      $options[''] = t('No defined option sets');
    }

    $form['swiper']['swiper_template'] = [
      '#type' => 'select',
      '#title' => $this->t('Swiper template'),
      '#description' => $this->t("Choose one of predefined swiper templates"),
      '#options' => $options,
      '#default_value' => $this->options['swiper_template'],
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

    if (isset($this->options['swiper_template']) && !empty($this->options['swiper_template'])) {
      $this->swiper_storage =  NkToolsSwiper::loadStorage($this->options['swiper_template']);
    }

    // Group the rows according to the grouping field, if specified.
    $sets = parent::render();

    // Render each group separately and concatenate.
    $output = $sets;
     //$sets[0]['#view']->getDisplay('page_2')); //$this->view->style_plugin->options);
 
    foreach ($sets as $key => &$set) {
       
       $swiper_id = !empty($this->options['grouping']) && !empty($this->options['grouping'][0]) ? Html::getUniqueId('swiper-group-' . $this->options['grouping'][0]['field'] . '-' . $key) : $this->options['id'];
       $output[$key] = [
        '#theme' => $this->themeFunctions(),
        '#view' => $this->view,
        '#options' => $this->options,
        '#rows' => $set['#rows'],
        '#title' => $set['#title'],
        '#swiper' => [
          'swiper_id' => $swiper_id,
          'storage' => $this->swiper_storage,
        ]
      ];
     

      // Add js settings
      if (!isset($output[$key]['#attached']['drupalSettings']['nk_tools_swiper'])) {
        $output[$key]['#attached']['drupalSettings']['nk_tools_swiper'] = [
          'swipers' => [],
        ];
      }
      $output[$key]['#attached']['drupalSettings']['nk_tools_swiper']['swipers'][$swiper_id] = $this->swiper_storage['swiper_options']; 
    }
    
    // Add some libraries
    //$output[0]['#attached']['library'][] = 'nk_tools_swiper/swiper';
    $output[0]['#attached']['library'][] = 'nk_tools_swiper/nk_tools_swiper';

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);

    /* Move swiper options to the parent array so that
     * values are saved properly.
     * Original: values['style_options']['swiper'] =
     *   ['options', 'caption', 'id'].
     */
    $swiper_options = $form_state->getValue(['style_options', 'swiper']);

    // Edit:  values['style_options'] += ['options', 'caption', 'id'].
    foreach ($swiper_options as $key => $value) {
      $form_state->setValue(['style_options', $key], $value);
    }

    // Edit:  values['style_options']['swiper'] = NULL.
    $form_state->setValue(['style_options', 'swiper'], NULL);
  }
}