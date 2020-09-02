<?php
 
/**
 * @file
 * Definition of Drupal\nk_tools\Plugin\views\field\NkToolsEntityView
 */
 
namespace Drupal\nk_tools\Plugin\views\field;
 
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
 
/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("nk_tools_entity_view")
 */
class NkToolsEntityView extends FieldPluginBase {
 
  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }
 
  /**
   * Define the available options
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['view_mode'] = ['default' => 'full'];
    return $options;
  }
 
  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
   
    $options = [];
    $modes = \Drupal::service('entity_display.repository')->getViewModes('node');
    foreach ($modes as $key => $option) {
      $options[$key] = $key;  
    }
    
    $form['view_mode'] = array(
      '#title' => $this->t('Display (a view mode)'),
      '#type' => 'select',
      '#default_value' => $this->options['view_mode'],
      '#options' => $options,
    );
 
    parent::buildOptionsForm($form, $form_state);
  }
 
  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $node = $values->_entity;
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $node_view =  $view_builder->view($node, $this->options['view_mode']);
    return \Drupal::service('renderer')->render($node_view);
  }
}