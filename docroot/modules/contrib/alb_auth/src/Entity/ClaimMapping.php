<?php

namespace Drupal\alb_auth\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the ClaimMapping entity.
 *
 * @ConfigEntityType(
 *   id = "claim_mapping",
 *   label = @Translation("Claim mapping"),
 *   handlers = {
 *     "list_builder" = "Drupal\alb_auth\Controller\ClaimMappingListBuilder",
 *     "form" = {
 *       "add" = "Drupal\alb_auth\Form\ClaimMappingForm",
 *       "edit" = "Drupal\alb_auth\Form\ClaimMappingForm",
 *       "delete" = "Drupal\alb_auth\Form\ClaimMappingDeleteForm",
 *     }
 *   },
 *   config_prefix = "claim_mapping",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/people/alb_auth/mappings/{example}",
 *     "delete-form" = "/admin/config/people/alb_auth/mappings/{example}/delete",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "claimSource",
 *     "mappingTarget"
 *   }
 * )
 */
class ClaimMapping extends ConfigEntityBase implements ClaimMappingInterface {

  /**
   * The config entity ID.
   *
   * @var string
   */
  public $id;

  /**
   * The claim mapping label.
   *
   * @var string
   */
  public $label;

  /**
   * The claim source.
   *
   * @var string
   */
  public $claimSource;

  /**
   * The destination field name.
   *
   * @var string
   */
  public $mappingTarget;

  /**
   * {@inheritdoc}
   */
  public function getClaimSource() {
    return $this->claimSource;
  }

  /**
   * {@inheritdoc}
   */
  public function setClaimSource(string $claim_name) {
    $this->claimSource = $claim_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingTarget() {
    return $this->mappingTarget;
  }

  /**
   * {@inheritdoc}
   */
  public function setMappingTarget(string $field_name) {
    $this->mappingTarget = $field_name;
  }

}
