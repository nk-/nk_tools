<?php

namespace Drupal\nk_tools_cer\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class AutocompleteRouteSubscriber extends RouteSubscriberBase {

  public function alterRoutes(RouteCollection $collection) {
    /*
    if ($route = $collection->get('system.entity_autocomplete')) {
      // $defaults = $route->getDefaults();
      // $defaults['_controller'] = '\Drupal\nk_tools_cer\Controller\EntityAutocompleteController::handleAutocomplete';
      // $defaults['selection_handler'] = 'default:corresponding_entity_reference_selection';
      // $route->addDefaults($defaults);
      \Drupal::logger('RouteDefaults')->notice('<pre>' . print_r($route->getDefaults(), 1) . '<pre>');
    }
    */
  }

}
