<?php

namespace Drupal\nk_tools_cer\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Controller\ControllerBase;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;
//use Drupal\system\Controller\EntityAutocompleteController; 

use Drupal\nk_tools_cer\CorrespondingEntityAutocompleteMatcher;
use Drupal\nk_tools_cer\Plugin\Validation\ValidCorrespondingReferenceTrait;


class CorrespondingEntityAutocompleteController extends ControllerBase {

  use ValidCorrespondingReferenceTrait;

  /**
   * The autocomplete matcher for entity references.
   */
  protected $matcher; 

  /**
   * {@inheritdoc}
   */
  public function __construct(CorrespondingEntityAutocompleteMatcher $matcher, KeyValueStoreInterface $key_value) {
    $this->matcher = $matcher;
    $this->keyValue = $key_value;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('nk_tools_cer.autocomplete_matcher'),
      $container->get('keyvalue')->get('entity_autocomplete')
    );
  }

  /**
   * Autocomplete the label of an entity.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request 
   *   The request object that contains the typed tags.
   * @param string $target_type
   *   The ID of the target entity type.
   * @param string $selection_handler
   *   The plugin ID of the entity reference selection handler.
   * @param string $selection_settings_key
   *   The hashed key of the key/value entry that holds the selection handler
   *   settings.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The matched entity labels as a JSON response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown if the selection settings key is not found in the key/value store
   *   or if it does not match the stored data.
   */
  public function handleAutocomplete(Request $request, $field_name, $target_type, $selection_handler, $selection_settings_key) { 
    
    $matches = [];
    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = mb_strtolower(array_pop($typed_string)); 

      // Selection settings are passed in as a hashed key of a serialized array stored in the key/value store.
      $selection_settings = $this->keyValue->get($selection_settings_key, FALSE); 

      if ($selection_settings !== FALSE) {
        $selection_settings_hash = Crypt::hmacBase64(serialize($selection_settings) . $target_type . $selection_handler, Settings::getHashSalt());
        if (!hash_equals($selection_settings_hash, $selection_settings_key)) {
          // Disallow access when the selection settings hash does not match the passed-in key.
          throw new AccessDeniedHttpException('Invalid selection settings key.');
        }
      }
      else {
        // Disallow access when the selection settings key is not found in the key/value store.
        throw new AccessDeniedHttpException();
      }

      $valid = $this->validFields($field_name, $target_type, $selection_settings);
      
      $matches = $this->getMatches($target_type, $selection_handler, $selection_settings, $field_name, $valid, $typed_string);
 
    }

    return new JsonResponse($matches);
  }

  /**
   * Gets matched labels based on a given search string.
   */
  protected function getMatches($target_type, $selection_handler, $selection_settings, $field_name, array $valid = [], $string = '') {
    
    $matches = [];

    $options = [
      'target_type'      => $target_type,
      'handler'          => $selection_handler,
      'handler_settings' => $selection_settings,
    ];

    $handler = \Drupal::service('plugin.manager.entity_reference_selection')->getInstance($options);
    //$handler =$this->selectionManager->createInstance('corresponding_entity_reference_selection', $options);
    // \Drupal::logger('THIS')->notice('<pre>' . print_r(get_class($handler), 1) . '<pre>');

    if (isset($string)) {
      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, 10);
      //$custom_labels = $custom_handler->getReferenceableEntities($string, $match_operator, 10);

      // Loop through the entities and convert them into autocomplete output.
      foreach ($entity_labels as $values) {
        foreach ($values as $entity_id => $label) {

          $entity = \Drupal::entityTypeManager()->getStorage($target_type)->load($entity_id);
          $entity = \Drupal::entityManager()->getTranslationFromContext($entity);
          
          $is_valid = FALSE;

          if (!empty($valid)) {
            foreach ($valid as $field_name => $bundles) {
              foreach ($bundles as $bundle) {
                if ($entity->getType() == $bundle && $entity->hasField($field_name)) {
                  $is_valid = TRUE;
                }
              }
            }
          }
 
          if ($is_valid) {
          
            $type = !empty($entity->type->entity) ? $entity->type->entity->label() : $entity->bundle();
            $status = '';
            if ($entity->getEntityType()->id() == 'node') {
              $status = ($entity->isPublished()) ? ", Published" : ", Unpublished";
            }

            $key = $label . ' (' . $entity_id . ')';
            // Strip things like starting/trailing white spaces, line breaks and tags.
            $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));
            // Names containing commas or quotes must be wrapped in quotes.
            $key = Tags::encode($key);
            $label = $label . ' (' . $entity_id . ') [' . $type . $status . ']';
            
            $matches[] = ['value' => $key, 'label' => $label];
          }

        }
      }
    }

    return $matches;
  }

}