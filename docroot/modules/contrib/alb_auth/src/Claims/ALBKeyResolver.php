<?php

namespace Drupal\alb_auth\Claims;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * The ALB key resolver service.
 */
class ALBKeyResolver implements KeyResolverInterface {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs ALBKeyResolver object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The http client.
   */
  public function __construct(CacheBackendInterface $cache, ConfigFactoryInterface $config_factory, ClientInterface $http_client) {
    $this->cache = $cache;
    $this->config = $config_factory->get('alb_auth.settings');
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public function getKey(string $kid) {
    if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $kid)) {
      // TODO: Review the exception thrown. Should we throw
      // TODO: InvalidArgumentException?
      // TODO: Update the \Drupal\alb_auth\Claims\KeyResolverInterface::getKey()
      // TODO: method definition once we do so.
      throw new \Exception('Malformed key identifier');
    }

    $region = $this->config->get('aws_region');
    $cid = "alb_auth:{$region}:{$kid}";
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }
    else {
      // @see https://docs.aws.amazon.com/elasticloadbalancing/latest/application/listener-authenticate-users.html
      // TODO: Validate the $region value, throw the exception.
      $url = "https://public-keys.auth.elb.{$region}.amazonaws.com/{$kid}";
      $response = $this->httpClient->get($url);
      $key = (string) $response->getBody();
      $this->cache->set($cid, $key);
      return $key;
    }
  }

}
