<?php

namespace Drupal\nk_tools\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Link;
use Drupal\Core\Url;
// use Drupal\Core\Render\Markup;
use Drupal\node\NodeInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\views\Entity\View;

use Drupal\nk_tools\Plugin\Block\NkToolsBlockBase;

/**
 * Provides block with Media entity downloads
 *
 * @Block(
 *   id = "nk_tools_views_filters_block",
 *   admin_label = @Translation("Views filters"),
 *   category = @Translation("Nk tools"),
 * )
 */
class NkToolsViewsFiltersBlock extends NkToolsBlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'view_id' => NULL,
      'display_id' => NULL,
      'argument' => NULL,
      'theme' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) { 
    
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    // Check current form state values (if coming from ajax or not) 
    if ($form_state instanceof SubformStateInterface) {
      $values = $form_state->getCompleteFormState()->getValues();
    }
    else {
      $values = $form_state->getValues();
    }

    $view_id = NULL;
    $display_id = NULL;

    if (isset($values['settings']) && isset($values['settings']['view']) && isset($values['settings']['view']['view_id'])) {
      $view_id = !empty($values['settings']['view']['view_id']) ? $values['settings']['view']['view_id'] : NULL;
      $display_id = isset($values['settings']['view']['display_id']) && !empty($values['settings']['view']['display_id']) ? $values['settings']['view']['display_id'] : NULL;
      $argument = isset($values['settings']['view']['argument']) && !empty($values['settings']['view']['argument']) ? $values['settings']['view']['argument'] : NULL;
    }
    else {
      $view_id = $config['view_id'];
      $display_id = $config['display_id'];
      $argument = $config['argument'];
    }

    // Custom composite element
    $form['view'] = [ 
      '#type' => 'nk_tools_views_reference',
      '#title' => $this->t('Reference a View'),
      '#default_value' => [
        'view_id' => $view_id,
        'display' => [
          'display_id' => $display_id,
          'argument' => $argument,
          'filter' => NULL,
        ]
      ],
    ];

    $form['theme_ui'] = [
      '#type' => 'details',
      '#title' => $this->t('Entity or entities to render'),
      '#description' => $this->t('Choose a target entity, or fields, to load as a primary content of this block.'),
      '#open' => TRUE, 
    ];

    $form['theme_ui']['theme'] = [
      '#type' => 'radios',
      '#title' => $this->t('Theme UI'),
      '#description' => $this->t('<em>Unformatted</em> is just linear, <em>Buttons</em> are minimal lines in CSS, <em>Collapsible Panel</em> is based on main theme\'s implementation'),
      '#default_value' => $config['theme'],
      '#required' => TRUE,
      '#options' => [
        'none' => $this->t('Unformatted'),
        'buttons' => $this->t('Buttons'),
        'panel' => $this->t('Collapsible Panel'),
      ],
    ];



    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $values = $form_state->getValues();

    $this->configuration['view_id'] = isset($values['view']['view_id']) && !empty($values['view']['view_id']) ? $values['view']['view_id'] : NULL;
    $this->configuration['display_id'] = isset($values['view']['display']['display_id']) && !empty($values['view']['display']['display_id']) ? $values['view']['display']['display_id'] : NULL;
    $this->configuration['argument'] = isset($values['view']['display']['argument']) && !empty($values['view']['display']['argument']) ? $values['view']['display']['argument'] : NULL;
   
    $this->configuration['theme'] = isset($values['theme_ui']['theme']) && !empty($values['theme_ui']['theme']) ? $values['theme_ui']['theme'] : NULL;
  }
  
  /**
   * {@inheritdoc}
   */
  public function build() {
   
    $config = $this->getConfiguration();
    $build = [];
/*
      '#theme' => 'nk_tools_ajax_links',
      '#ajax_links' => 
    ];
*/

    if ($config['view_id'] && $config['display_id']) {
      $build = $this->nkToolsFactory->renderViewFilter($config['view_id'], $config['display_id']); //, $render = FALSE, array &$data = []);
      $build['#view'] = View::load($config['view_id']);
      $build['#display'] = $build['#view']->getDisplay($config['display_id']);
      $build['#argument'] = $config['argument'];
      //ksm($build);


    }
    return parent::build() + $build;
  }
 
}