<?php

namespace Drupal\alb_auth\Claims;

/**
 * The ALB key resolver service interface.
 */
interface KeyResolverInterface {

  /**
   * Get the public ALB key for a given key ID.
   *
   * TODO: Document exceptions thrown by method (once we start throwing
   * TODO: exceptions).
   *
   * @param string $kid
   *   The key ID.
   *
   * @return string
   *   The public ALB key.
   */
  public function getKey(string $kid);

}
