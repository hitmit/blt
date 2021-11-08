<?php

namespace Drupal\tome_sync;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\File\FileSystemInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\path_alias\PathAliasInterface;
use Drupal\Core\TypedData\Type\UriInterface;

/**
 * Provides methods for reading and writing the index file.
 *
 * @todo Move to a service?
 *
 * @internal
 */
trait ContentIndexerTrait {

  /**
   * Writes content to the index.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An entity to be indexed.
   */
  protected function indexContent(ContentEntityInterface $entity) {
    $entityTypeManager = \Drupal::service('entity_type.manager');
    $dependencies = [];

    # Handle dependencies of entities for type MenuLinkContent
    # - who implement a parent child relationship but don't implement the EntityReferenceFieldItemListInterface
    # - linked entities
    if ($entity instanceof MenuLinkContentInterface) {

      // Add a dependency to a menu items parent
      $parentId = $entity->getParentId();
      if ($parentId) {
        list($menu_name, $parent) = explode(':', $parentId, 2);
        $dependencies[] = "{$menu_name}.{$parent}";
      }

      // Add dependency to a menu items linked entity
      $linkUrl = $entity->getUrlObject();
      if ($linkUrl && $linkUrl->isRouted()) {
        list($type, $subType, $ref) = explode('.', $linkUrl->getRouteName(), 3);
        if ($type === 'entity') {
          $routeParams = $linkUrl->getRouteParameters();
          $entityId = $routeParams[$subType];
          $linkedEntity = $entityTypeManager->getStorage($subType)->load($entityId);
          $dependencies[] = "{$subType}.{$linkedEntity->uuid()}";
        }
      }
    }

    # Handle dependencies of entities for type PathAlias
    if ($entity instanceof PathAliasInterface) {
      $path = $entity->getPath();
      if (isset($path) && preg_match('/^\/node\/[0-9]+$/', $path)) {
        list($entityType, $nodeId) = explode('/', ltrim($path, '/'), 2);
        $linkedEntity = $entityTypeManager->getStorage($entityType)->load($nodeId);
        if (isset($linkedEntity)) {
          $dependencies[] = "{$entityType}.{$linkedEntity->uuid()}";
        }
      }
    }

    foreach ($entity as $field) {
      if ($field instanceof EntityReferenceFieldItemListInterface) {
        foreach ($field->referencedEntities() as $referenced_entity) {
          // There are times when a node has a reference to it's menu link, even though the owner of the
          // relationship is the menu link. In this case we skip adding it as a dependency to prevent cycles
          // in the relationship graph
          if ($entity->getEntityTypeId() === 'node' && $referenced_entity instanceof MenuLinkContentInterface) {
            continue;
          }

          if ($referenced_entity instanceof ContentEntityInterface) {
            $dependencies[] = TomeSyncHelper::getContentName($referenced_entity);
          }
        }
      }
      elseif ($field instanceof FieldItemListInterface) {
        foreach ($field as $item) {
          /** @var \Drupal\Core\Field\FieldItemInterface $item */
          foreach ($item as $property) {
            // @see \Drupal\tome_sync\Normalizer\UriNormalizer
            if ($property instanceof UriInterface && strpos($property->getValue(), 'entity:') === 0) {
              $parts = explode('/', str_replace('entity:', '', $property->getValue()));
              if (count($parts) >= 2 && $entityTypeManager->hasDefinition($parts[0]) && is_numeric($parts[1])) {
                if ($referenced_entity = $entityTypeManager->getStorage($parts[0])->load($parts[1])) {
                  $dependencies[] = TomeSyncHelper::getContentNameFromParts($referenced_entity->getEntityTypeId(), $referenced_entity->uuid());
                }
              }
            }
          }
        }
      }
    }
    if (!$entity->isDefaultTranslation()) {
      $dependencies[] = TomeSyncHelper::getContentNameFromParts($entity->getEntityTypeId(), $entity->uuid());
    }
    if (is_a($entity, '\Drupal\path_alias\PathAliasInterface')) {
      foreach (['path', 'alias'] as $key) {
        if (!empty($entity->get($key)->value)) {
          $parts = explode('/', $entity->get($key)->value);
          if (count($parts) >= 3 && $entityTypeManager->hasDefinition($parts[1]) && is_numeric($parts[2])) {
            if ($referenced_entity = $entityTypeManager->getStorage($parts[1])->load($parts[2])) {
              $dependencies[] = TomeSyncHelper::getContentName($referenced_entity);
            }
          }
        }
      }
    }
    $handle = $this->acquireContentIndexLock();
    $contents = stream_get_contents($handle);
    if (empty($contents)) {
      $index = [];
    }
    else {
      $index = json_decode($contents, TRUE);
    }
    $dependencies = array_values(array_unique($dependencies));
    $index[TomeSyncHelper::getContentName($entity)] = $dependencies;
    ftruncate($handle, 0);
    rewind($handle);
    ksort($index);
    fwrite($handle, json_encode($index, JSON_PRETTY_PRINT));

    flock($handle, LOCK_UN);
  }

  /**
   * Removes content from the index.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An entity to be indexed.
   */
  protected function unIndexContent(ContentEntityInterface $entity) {
    $name = TomeSyncHelper::getContentName($entity);
    $this->unIndexContentByName($name);
  }

  /**
   * Removes content from the index.
   *
   * @param string $name
   *   A content name.
   */
  protected function unIndexContentByName($name) {
    $handle = $this->acquireContentIndexLock();
    $contents = stream_get_contents($handle);
    if (empty($contents)) {
      return;
    }
    $index = json_decode($contents, TRUE);
    if (isset($index[$name])) {
      unset($index[$name]);
    }
    foreach ($index as &$dependencies) {
      $dependencies = array_diff($dependencies, [$name]);
    }
    ftruncate($handle, 0);
    rewind($handle);
    ksort($index);
    fwrite($handle, json_encode($index, JSON_PRETTY_PRINT));

    flock($handle, LOCK_UN);
  }

  /**
   * Acquires a lock for writing to the index.
   *
   * @return resource
   *   A file pointer resource on success.
   *
   * @throws \Exception
   *   Throws an exception when the index file cannot be written to.
   */
  protected function acquireContentIndexLock() {
    $destination = $this->getContentIndexFilePath();
    $directory = dirname($destination);
    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
    $handle = fopen($destination, 'c+');
    if (!flock($handle, LOCK_EX)) {
      throw new \Exception('Unable to acquire lock for the index file.');
    }
    return $handle;
  }

  /**
   * Gets the contents of the index.
   *
   * @return bool|array
   *   The index, or FALSE if there was an error.
   */
  protected function getContentIndex() {
    $destination = $this->getContentIndexFilePath();
    if (!file_exists($destination)) {
      return FALSE;
    }
    $contents = file_get_contents($destination);
    return json_decode($contents, TRUE);
  }

  /**
   * Deletes the index file.
   */
  protected function deleteContentIndex() {
    $destination = $this->getContentIndexFilePath();
    if (is_file($destination)) {
      unlink($destination);
    }
  }

  /**
   * Gets the index file path.
   *
   * @return string
   *   The index file path.
   */
  protected function getContentIndexFilePath() {
    return Settings::get('tome_content_directory', '../content') . '/meta/index.json';
  }

}
