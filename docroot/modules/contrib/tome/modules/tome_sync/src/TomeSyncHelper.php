<?php

namespace Drupal\tome_sync;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\TranslatableInterface;

/**
 * Provides helpers for the Tome Sync module.
 *
 * @internal
 */
class TomeSyncHelper {

  /**
   * Gets the content name for a given entity.
   *
   * This can be used to read/write from the tome_sync.storage.content service.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   *
   * @return string
   *   A string representing the content name.
   */
  public static function getContentName(EntityInterface $entity) {
    if ($entity instanceof TranslatableInterface && !$entity->isDefaultTranslation()) {
      return "{$entity->getEntityTypeId()}.{$entity->uuid()}.{$entity->language()->getId()}";
    }
    else {
      return "{$entity->getEntityTypeId()}.{$entity->uuid()}";
    }
  }

  /**
   * Gets the content name based on parts.
   *
   * This can be used to read/write from the tome_sync.storage.content service.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $uuid
   *   The entity UUID.
   * @param string $langcode
   *   (optional) The langcode, for translations.
   *
   * @return string
   *   A string representing the content name.
   */
  public static function getContentNameFromParts($entity_type_id, $uuid, $langcode = NULL) {
    if ($langcode) {
      return "$entity_type_id.$uuid.$langcode";
    }
    else {
      return "$entity_type_id.$uuid";
    }
  }

  /**
   * Gets the parts from a content name.
   *
   * @param string $name
   *   A content name.
   *
   * @return array
   *   A 3-tuple in the format [entity_type_id, uuid, langcode].
   */
  public static function getPartsFromContentName($name) {
    $parts = explode('.', $name);
    return [
      $parts[0],
      $parts[1],
      isset($parts[2]) ? $parts[2] : NULL,
    ];
  }

  /**
   * Take a entity reference path in the form of '/{entity_type}/{pid}' and
   * return a normalized version using the referenced entities UUID in the
   * form of '/{entity_type}/{uuid}'
   *
   * @param string $path
   *   Path in the form of '/{entity_type}/{pid}'
   * @return string
   *   Path in the form of '/{entity_type}/{uuid}'
   */
  public static function normalizePathReference($path) {
    \Drupal::logger('tome_sync')->debug('Attempting to normalize path: ' . $path);

    // Ensure we have a valid path
    if (!preg_match('/^\/node\/.*$/', $path)) {
      return $path;
    }
    \Drupal::logger('tome_sync')->debug('Normalizing path: ' . $path);

    $entityTypeManager = \Drupal::entityTypeManager();
    list($entityType, $nodeId) = explode('/', ltrim($path, '/'), 2);

    \Drupal::logger('tome_sync')->debug('type: ' . $entityType . ' id: ' . $nodeId);

    $linkedEntity = $entityTypeManager->getStorage($entityType)->load($nodeId);
    if (isset($linkedEntity)) {
      $uuid = $linkedEntity->uuid();
      $result = '/'. $entityType .'/' . $uuid;
    } else {
      $result = $path;
    }

    \Drupal::logger('tome_sync')->debug('Normalized path: ' . $path . ' into: ' . $result);
    return $result;
  }

  /**
   * Take a normalized entity reference path in the form of
   * '/{entity_type}/{uuid}' and return a denormalized version using the
   * referenced entities node ID from the given local environment
   *
   * @param string $path
   *   Path in the form of '/{entity_type}/{uuid}'
   * @return string
   *   Path in the form of '/{entity_type}/{pid}'
   */
  public static function denormalizePathReference($path) {
    \Drupal::logger('tome_sync')->debug('Attempting to denormalize path: ' . $path);

    // Ensure we have a valid path
    if (!preg_match('/^\/node\/.*$/', $path)) {
      return $path;
    }

    \Drupal::logger('tome_sync')->debug('Denormalizing path: ' . $path);

    $entityRepository = \Drupal::service('entity.repository');
    list($entityType, $uuid) = explode('/', ltrim($path, '/'), 2);

    \Drupal::logger('tome_sync')->debug('type: ' . $entityType . ' id: ' . $uuid);

    // Check to see if the id is more than just an integer
    if (!preg_match('/^[0-9]+$/', $uuid)) {
      $linkedEntity = $entityRepository->loadEntityByUuid($entityType, $uuid);
      if (isset($linkedEntity)) {
        $result = '/'. $entityType .'/' . $linkedEntity->id();
      } else {
        \Drupal::logger('tome_sync')->notice('Unable to find entity to denormalize path: ' . $path);
        $result = $path;
      }
    } else {
      $result = $path;
    }

    \Drupal::logger('tome_sync')->debug('Denormalized path: ' . $path . ' into: ' . $result);
    return $result;
  }

}
