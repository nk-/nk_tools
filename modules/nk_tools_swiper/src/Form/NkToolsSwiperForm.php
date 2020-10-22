<?php

namespace Drupal\nk_tools_swiper\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Element;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Swiper entity form.
 *
 * @property \Drupal\nk_tools_swiper\NkToolsSwiperInterface $entity
 */
class NkToolsSwiperForm extends EntityForm {
 
  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */    
  protected $entityQuery;

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */    
  protected $entityManager;

  /**
   * Constructs an Swiper configuration entity.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactoryInterface $entity_query
   *   The entity query.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity Manager.
   */
   public function __construct(QueryFactoryInterface $entity_query, EntityTypeManagerInterface $entity_manager) {
     $this->entityQuery = $entity_query;
     $this->entityManager = $entity_manager;
   }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query.config'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);

    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t('Label for the swiper.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => array(
        'exists' => array($this, 'exist'),
      ),
      '#disabled' => !$entity->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $entity->status(),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $entity->get('description'),
      '#description' => $this->t('Description of the swiper.'),
    ];


    $form['swiper_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Swiper options'),
      '#description' => $this->t('For the extensive list of options for this class see: <a href="https://idangero.us/swiper/api/#initialize">Swiper.js API</a>'), 
      '#attributes' => [
       // 'class' => ['node-form-options']
      ],
      '#attached' => [
        // 'library' => ['ckeditor/drupal.ckeditor'],
      ],
      '#open' => TRUE,
    ];


    $form['swiper_options']['direction'] = [
      '#type' => 'radios',
      '#title' => $this->t('Direction'),
      '#description' => $this->t('Select sliding direction. Could be <em>horizontal</em> or <em>vertical</em>'),
      '#options' => [
        'horizontal' => $this->t('Horizontal'),
        'vertical' => $this->t('Vertical'),
      ],
      '#default_value' => $entity->get('direction') ? $entity->get('direction') : 'horizontal',
    ];
   
    
    $form['swiper_options']['slidesPerView'] = [
      '#title' => $this->t('Number of slides per view'),
      '#type' => 'textfield',
      '#description' => $this->t('Integer value - slides visible at the same time on slider\'s container.'),
      '#default_value' => $entity->get('slidesPerView') ? $entity->get('slidesPerView') : '1',
    ];

    $form['swiper_options']['loopedSlides'] = [
      '#title' => $this->t('Number of looped slides'),
      '#type' => 'textfield',
      '#description' => $this->t('Integer value - Number of slides looped at once. Probably must be set for the above config to work.'),
      '#default_value' => $entity->get('loopedSlides') ? $entity->get('loopedSlides') : '1',
    ];

    $form['swiper_options']['lazy_load'] = [
      '#title' => $this->t('Lazy load images'),
      '#type' => 'checkbox',
      '#description' => $this->t('Experimental; Includes CSS loader'),
      '#default_value' => $entity->get('lazy_load'),
      '#disabled' => TRUE, 
    ];


    $form['swiper_options']['effect'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Swipe effect'),
      '#default_value' =>  $entity->get('effect') ? $entity->get('effect') : 'slide',
      '#description' => $this->t('Choose one of few Swiper effects; Default: <em>slide</em>; Other possible: <em>fade</em> or <em>cube</em> or <em>coverflow</em> or <em>flip</em>'),
    ];

    $form['swiper_options']['autoplay'] = [
      '#type' => 'details',
      '#title' => $this->t('Autoplay delay in milliseconds'),
      '#open' => TRUE,
      '#description' => $this->t('If any of the values is entered in this section Swiper will autoplay after that value'),
    ];

    $form['swiper_options']['autoplay']['delay'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Delay in ms'),
      '#default_value' => $entity->get('delay'),
    ];

    $form['swiper_options']['autoHeight'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set Auto height'),
      '#default_value' => $entity->get('autoHeight'),
    ];
 
    $form['swiper_options']['grabCursor'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set Grab type of cursor'),
      '#default_value' => $entity->get('grabCursor'),
      '#description' => $this->t('This is basically CSS; <em>cursor: grab</em>'),
    ];

    $form['swiper_options']['noSwipingSelector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No swiping selectors'),
      '#maxlength' => 255,
      '#default_value' =>  $entity->get('noSwipingSelector') ? $entity->get('noSwipingSelector') : '.no-swipe, button, input',
      '#description' => $this->t('A comma separated list of css selectors for which swiping behaviour is disabled, when those are in focus; i.e. <em>.no-swipe, button, input</em>'),
    ];

    $form['swiper_options']['navigation'] = [
      '#type' => 'details',
      '#title' => $this->t('Prev/Next navigation'),
      '#open' => TRUE,
      '#description' => $this->t('Configure prev/next navigation elements'),
    ];

    $form['swiper_options']['navigation']['navigation_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable prev/next buttons'),
      '#default_value' => $entity->get('navigation_enabled'),
      '#description' => $this->t('Options below will not apply if this checkbox is not checked.'),
    ];

    $form['swiper_options']['navigation']['nextEl'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Next element selector'),
      '#maxlength' => 255,
      '#default_value' => $entity->get('nextEl') ? $entity->get('nextEl') : '.swiper-button-next',
      '#description' => $this->t('Css selector for navigation Next > element; default: <em>.swiper-button-next</em>'),
    ];
    
    $form['swiper_options']['navigation']['prevEl'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Previous element selector'),
      '#maxlength' => 255,
      '#default_value' => $entity->get('prevEl') ? $entity->get('prevEl') : '.swiper-button-prev',
      '#description' => $this->t('Css selector for navigation  < Previous element; default: <em>.swiper-button-prev</em>'),
    ];     
    
    $form['swiper_options']['pagination'] = [
      '#type' => 'details',
      '#title' => $this->t('Pagination'),
      '#open' => TRUE,
      '#description' => $this->t('Configuration of pagination object and elements'),
    ];
    
    $form['swiper_options']['pagination']['pagination_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable pagination'),
      '#default_value' => $entity->get('pagination_enabled'),
      '#description' => $this->t('Options below will not apply if this checkbox is not checked.'),
    ];

    $form['swiper_options']['pagination']['type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pagination type'),
      '#maxlength' => 255,
      '#default_value' => $entity->get('type') ? $entity->get('type') : 'bullets',
      '#description' => $this->t('Default: <em>bullets</em>; Other possible: <em>fraction</em> or <em>progressbar</em> or <em>custom</em><br />Setting to "custom" obviously requires implementation of renderBullet() callback'),
    ];

    $form['swiper_options']['pagination']['el'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Element'),
      '#maxlength' => 255,
      '#default_value' => $entity->get('el') ? $entity->get('el') : '.swiper-pagination',
      '#description' => $this->t('Pagination HTML element selector; default: <em>.swiper-pagination</em>'),
    ];

    $form['swiper_options']['pagination']['dynamicBullets'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Dynamic bullets'),
      '#default_value' => $entity->get('dynamicBullets'),
      '#description' => $this->t('May be handy and "fancy" with a bigger number of bullets/slides'),
    ];

    $form['swiper_options']['pagination']['clickable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Bullets clickable'),
      '#default_value' => $entity->get('clickable'),
    ];

    return $form;
  }

  /** 
   * {@inheritdoc}
   */
  /*
  public function submitForm(array &$form, FormStateInterface $form_state) {
   parent::submitForm($form, $form_state);
  }
  */

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->entity;
    
    // Set our data into a custom tree property (ideally it should match properies names of Swiper)
    $swiper_options = [];
    foreach (Element::children($form['swiper_options']) as $swiper_option) {
      if ($form['swiper_options'][$swiper_option]['#type'] == 'details') {
        foreach (Element::children($form['swiper_options'][$swiper_option]) as $option) {
           $swiper_options[$swiper_option][$option] = $form['swiper_options'][$swiper_option][$option]['#value'];
        }
      }
      else {
        $swiper_options[$swiper_option] = $form['swiper_options'][$swiper_option]['#value'];  
      }
    }
    $entity->setSwiperOptions($swiper_options);

    // Now save entity
    $status = $entity->save();
    
    if ($status) {
      $this->messenger()->addStatus($this->t('%label saved.', array(
         '%label' => $entity->label(),
      )));
    }
    else {
      $this->messenger()->addError($this->t('Error: %label was not saved.', array(
        '%label' => $entity->label(),
      )));
    }

    
    $renderCache = \Drupal::service('cache.render');
    $renderCache->invalidateAll();

    // Flush all Drupal cache
    drupal_flush_all_caches(); 
 
    // Go back to a page with collection of Swiper entities
    $form_state->setRedirect('entity.nk_tools_swiper.collection');
  }

  /**
   * Helper function to check whether an swiper configuration entity exists.
   */
   public function exist($id) {
     $entity = $this->entityQuery->get('nk_tools_swiper')
       ->condition('id', $id)
       ->execute();
     return (bool) $entity;
   }
}