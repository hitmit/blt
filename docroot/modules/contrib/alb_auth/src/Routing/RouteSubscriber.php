<?php

namespace Drupal\alb_auth\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('user.logout')) {
      $route->setDefault('_controller', '\\Drupal\\alb_auth\\Controller\\UserController::logout');
    }
  }

}
