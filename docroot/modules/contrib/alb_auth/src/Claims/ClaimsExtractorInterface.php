<?php

namespace Drupal\alb_auth\Claims;

use Symfony\Component\HttpFoundation\Request;

/**
 * The claims extractor interface.
 */
interface ClaimsExtractorInterface {

  /**
   * Get ALB claims for the given request.
   *
   * TODO: Document exceptions thrown by method (once we add exceptions).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Page request.
   *
   * @return object
   *   The stdclass object with a list of claims.
   */
  public function getClaimsFromRequest(Request $request);

  /**
   * Get claims for a given token.
   *
   * TODO: Document exceptions thrown by method (once we start throwing
   * TODO: exceptions).
   *
   * TODO: Do not return stdclass. Make a data class for storing claims.
   *
   * @param string $token
   *   The token value.
   *
   * @return object
   *   The stdclass object with a list of claims.
   */
  public function getClaimsFromToken($token);

}
