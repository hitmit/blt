<?php

namespace Drupal\tome_sync\Normalizer;

/**
 * Normalizes/denormalizes Drupal MenuContentLink entities to store references to entities by UUID
 *
 * @internal
 */
class MenuLinkContentEntityNormalizer extends ContentEntityNormalizer {




  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = 'Drupal\menu_link_content\Entity\MenuLinkContent';

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    $entityRepository = \Drupal::service('entity.repository');

    if (isset($data['link'])) {
      $result = [];
      foreach ($data['link'] as $linkItem) {
        if (substr($linkItem['uri'], 0, 5) === 'uuid:') {
          list($junk, $path) = explode(':', $linkItem['uri'], 2);
          list($entityType, $uuid) = explode('/', $path, 2);
          $linkedEntity = $entityRepository->loadEntityByUuid($entityType, $uuid);
          if ($linkedEntity) {
            $linkItem['uri'] = 'entity:'. $entityType .'/' . $linkedEntity->id();
          } else {
            $linkItem['uri'] = 'internal:#not-found';
          }
        }
        $result[] = $linkItem;
      }
      $data['link'] = $result;
    }

    return parent::denormalize($data, $class, $format, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    $values = parent::normalize($entity, $format, $context);

    if (isset($values['link'])) {
      $result = [];
      foreach ($values['link'] as $linkItem) {
        if (substr($linkItem['uri'], 0, 7) === 'entity:') {
          list($junk, $path) = explode(':', $linkItem['uri'], 2);
          list($entityType, $nodeId) = explode('/', $path, 2);
          $linkedEntity = $this->entityTypeManager->getStorage($entityType)->load($nodeId);
          if ($linkedEntity) {
            $uuid = $linkedEntity->uuid();
            $linkItem['uri'] = 'uuid:'. $entityType .'/' . $uuid;
          }
        }
        $result[] = $linkItem;
      }
      $values['link'] = $result;
    }

    return $values;
  }

}
