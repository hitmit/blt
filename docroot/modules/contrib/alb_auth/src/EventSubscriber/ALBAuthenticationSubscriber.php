<?php

namespace Drupal\alb_auth\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Drupal\Core\Config\ConfigFactory;
use Drupal\user\Entity\User;
use Drupal\alb_auth\Claims\ClaimsExtractorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * The request subscriber service.
 */
class ALBAuthenticationSubscriber implements EventSubscriberInterface {

  /**
   * The entity manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module config.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The claims extractor.
   *
   * @var \Drupal\alb_auth\Claims\ClaimsExtractorInterface
   */
  protected $claimsExtractor;

  /**
   * Constructs new ALBAuthenticationSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\alb_auth\Claims\ClaimsExtractorInterface $claims_extractor
   *   The claims extractor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactory $config_factory, ClaimsExtractorInterface $claims_extractor) {
    $this->entityTypeManager = $entity_type_manager;
    $this->config = $config_factory->get('alb_auth.settings');
    $this->claimsExtractor = $claims_extractor;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['prepareSession', 301];
    return $events;
  }

  /**
   * Reacts on the HTTP request.
   *
   * Seamlessly log in user authenticated via ALB.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The kernel event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function prepareSession(GetResponseEvent $event) {
    if (!$this->config->get('enabled')) {
      return;
    }
    
    $request = $event->getRequest();
    $token = $request->headers->get('X_AMZN_OIDC_DATA');

    // Ignore request without 'X_AMZN_OIDC_DATA' header set
    if (!isset($token)) {
      return;
    }
    
    $claims = $this->claimsExtractor->getClaimsFromRequest($request);
    $username = $claims->username;
    if ($accounts = $this->entityTypeManager
      ->getStorage('user')
      ->loadByProperties(array(
        'name' => $username,
        'status' => 1,
      ))) {
      // Fetch the user by username.
      $account = reset($accounts);
    }
    elseif ($this->config->get('provision')) {
      // Otherwise, create a new user, if enabled.
      $values = [
        'mail' => $claims->email,
        'name' => $username,
        'roles' => [],
        'pass' => user_password(),
        'status' => 1,
      ];
      $account = User::create($values);
      $account->save();
    }
    else {
      // Do nothing if user was not found and provisioning is disabled.
      return;
    }

    $old_uid = $request->getSession()->get('uid');
    $new_uid = $account->id();
    if ($old_uid != $new_uid) {
      \Drupal::logger('alb_auth')->notice('Switching from @from to @to', [
        '@from' => $old_uid,
        '@to' => $new_uid,
      ]);
      // TODO: find out if this is actually necessary.
      \Drupal::service('page_cache_kill_switch')->trigger();
      user_login_finalize($account);
    }
  }

}
