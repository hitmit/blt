<?php

namespace Drupal\alb_auth\Controller;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Routing\UrlGeneratorInterface;

/**
 * User routes controller.
 *
 * Used to replace the default /user/logout page.
 *
 * @see \Drupal\alb_auth\Routing\RouteSubscriber
 */
class UserController extends ControllerBase {

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs the UserController object.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The module configuration.
   */
  public function __construct(ImmutableConfig $config, UrlGeneratorInterface $urlGenerator) {
    $this->config = $config;
    $this->urlGenerator = $urlGenerator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')->get('alb_auth.settings'),
      $container->get('url_generator')
    );
  }

  /**
   * Logs the current user out.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirection to home page.
   */
  public function logout() {
    user_logout();
    if ($this->config->get('enabled')) {
      // TODO: Inject page cache kill switch as a service.
      \Drupal::service('page_cache_kill_switch')->trigger();

      // TODO: Set cookies Drupal/Symfony way.
      // TODO: See https://symfony.com/doc/current/components/http_foundation.html#setting-cookies
      $cookie_name = $this->config->get('cookie_name');
      setcookie("{$cookie_name}", '', -1, '/', '', TRUE, TRUE);

      // TODO: Do not concat URL parts. Construct a URL object, add query
      // TODO: parameters, then convert it to string URL.
      $url = rtrim($this->config->get('cognito.base_url'), '/') . '/logout'
        . '?client_id=' . $this->config->get('cognito.client_id')
        // seriously? this can't be the best equivalent to D7's $base_url?
        . '&logout_uri=' . $this->urlGenerator->generateFromRoute('<front>', [], ['absolute' => TRUE], TRUE)->getGeneratedUrl();
      return new TrustedRedirectResponse($url, 302);
    }
    else {
      return $this->redirect('<front>');
    }
  }

}
