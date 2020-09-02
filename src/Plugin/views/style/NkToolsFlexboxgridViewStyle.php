<?php

namespace Drupal\nk_tools\Plugin\views\style;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Component\Utility\Html;

use Drupal\node\Entity\Node;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\views\ResultRow;

/**
 * Style plugin to render each item in an ordered or unordered list.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "nk_tools_flexboxgrid",
 *   title = @Translation("Nk tools Flexbox Grid"),
 *   help = @Translation("Display the results in a Flexbox Grid."),
 *   theme = "views_view_unformatted",
 *   display_types = {"normal"}
 * )
 */
class NkToolsFlexboxgridViewStyle extends StylePluginBase {
  
  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE; // Essential

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
  protected function defineOptions() {
    $options = parent::defineOptions();
    
    $options['columns'] = ['default' => 2];
    $options['additional_class'] = ['default' => NULL];
    // Ensure unique id attribute for each instance of Flexbox grid (when multiple on the same page)
    // User can change this on settings but we try to make sure some unique id is auto assigned
    $view_id = $this->view->id();
    $current_display = $this->view->current_display;
    $options['id'] = ['default' => 'flexboxgrid-' . $view_id . '-' . $current_display]; 
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['flexboxgrid'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Flexbox grid'),
      '#description' => $this->t('Implementation of <a href="http://flexboxgrid.com">Flexbox Grid</a>'),
    ];

    $form['flexboxgrid']['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unique ID attribute'),
      '#description' => $this->t('This is should make possible/easier to handle multiple grids of this kind on the same page'),
      '#size' => 40,
      '#maxlength' => 255,
      '#default_value' => $this->options['id'],
    ];
   
    $form['flexboxgrid']['icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Set icon on grid item'),
      '#description' => $this->t('Icon name or CSS class base name for collapsible pane\'s toggle. In case of icon enter only its name like <em>updates</em> else if it is CSS class then please enter with a leading dot like <em>.link-updates</em>.'),
      '#size' => 40,
      '#maxlength' => 255,
      '#default_value' => $this->options['icon'],
    ];

    $form['flexboxgrid']['columns'] = [
      '#type' => 'radios',
      '#title' => $this->t('Number of columns'),
      '#description' => $this->t('Choose a number of columns in the grid'),
      '#options' => [
        2 =>  $this->t('Two'),
        3 =>  $this->t('Three'),
        4 =>  $this->t('Four'),
        5 =>  $this->t('Five'),
        6 =>  $this->t('Six'),
       ],
       '#default_value' => $this->options['columns'],
    ];

    $form['flexboxgrid']['additional_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Additional class'),
      '#description' => $this->t('Additional CSS class for each grid item. Without dot.'),
      '#default_value' => $this->options['additional_class'],
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
    
    // Get parent set 
    $sets = parent::render();
  
    $attributes = [

    ];
    
    $build = [
      '#theme' => 'nk_tools_flexboxgrid',
      '#view' => $this->view,
      '#rows' => [],
      '#config' => [
        'icon' => $this->options['icon'],
        'id' => NULL,
        'columns' => $this->options['columns'] ? $this->options['columns'] : 2,
        'class' => $this->options['additional_class'],
      ],
      '#attributes' => new Attribute($attributes),
    ];
    
    foreach ($sets as $key => &$set) {
      
      // Group the rows according to the grouping field, if specified.
      $id = !empty($this->options['grouping']) && !empty($this->options['grouping'][0]) ? Html::getUniqueId('flexboxgrid-group-' . $this->options['grouping'][0]['field'] . '-' . $key) : $this->options['id'];
      $build['#grid']['id'] = $id;
 
      foreach ($set['#rows'] as $index => $row) {
        $this->view->row_index = $index;
       
        if (isset($row['#row']) && $row['#row'] instanceof ResultRow) {
          $set['#rows'][$index] = $this->view->rowPlugin->render($row);
        }
        else {
          if (isset($row['#view_mode'])) { // This should be "Content" set for rendering in the view
            $set['#rows'][$index] = $row;
          } 
        }
      }      
      $build['#rows'] = $set['#rows'];
    }
    
    //$build['#attached']['library'][] = 'nk_tools/flexboxgrid';
    
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);

    $options = $form_state->getValue(['style_options', 'flexboxgrid']);

    foreach ($options as $key => $value) {
      $form_state->setValue(['style_options', $key], $value);
    }

    $form_state->setValue(['style_options', 'flexboxgrid'], NULL);
  }
}