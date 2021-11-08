<?php

namespace Drupal\alb_auth\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * The claim mapping config entity interface.
 */
interface ClaimMappingInterface extends ConfigEntityInterface {

  /**
   * Returns the claim source.
   *
   * @return string
   *   The claim source name.
   */
  public function getClaimSource();

  /**
   * Sets the claim source.
   *
   * @param string $claim_name
   *   The claim name.
   */
  public function setClaimSource(string $claim_name);

  /**
   * Returns the mapping target field name.
   *
   * @return string
   *   The mapping target field name.
   */
  public function getMappingTarget();

  /**
   * Sets the mapping target field name.
   *
   * @param string $field_name
   *   The mapping target field name.
   */
  public function setMappingTarget(string $field_name);

}
