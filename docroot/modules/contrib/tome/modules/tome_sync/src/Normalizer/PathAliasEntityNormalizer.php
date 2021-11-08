<?php

namespace Drupal\tome_sync\Normalizer;

use Drupal\tome_sync\TomeSyncHelper;

/**
 * Normalizes/denormalizes Drupal PathAlias entities to store references to entities by UUID
 *
 * @internal
 */
class PathAliasEntityNormalizer extends ContentEntityNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = 'Drupal\path_alias\Entity\PathAlias';

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    $entityRepository = \Drupal::service('entity.repository');

    if (isset($data['path'])) {
      $result = [];
      foreach ($data['path'] as $pathItem) {
        $pathItem['value'] = TomeSyncHelper::denormalizePathReference($pathItem['value']);
        $result[] = $pathItem;
      }
      $data['path'] = $result;
    }

    return parent::denormalize($data, $class, $format, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    $values = parent::normalize($entity, $format, $context);

    if (isset($values['path'])) {
      $result = [];
      foreach ($values['path'] as $pathItem) {
        $pathItem['value'] = TomeSyncHelper::normalizePathReference($pathItem['value']);
        $result[] = $pathItem;
      }
      $values['path'] = $result;
    }

    return $values;
  }

}
