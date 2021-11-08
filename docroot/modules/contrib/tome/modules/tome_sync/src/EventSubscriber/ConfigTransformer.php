<?php

namespace Drupal\tome_sync\EventSubscriber;

use Psr\Log\LoggerInterface;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\StorageTransformEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\tome_sync\TomeSyncHelper;

class ConfigTransformer implements EventSubscriberInterface {

  /**
   * The module logger
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs the ConfigTransformer object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::STORAGE_TRANSFORM_IMPORT][] = ['onImportTransform'];
    $events[ConfigEvents::STORAGE_TRANSFORM_EXPORT][] = ['onExportTransform'];
    return $events;
  }
  /**
   * The storage is transformed for importing.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The config storage transform event.
   */
  public function onImportTransform(StorageTransformEvent $event) {
    /** @var \Drupal\Core\Config\StorageInterface $storage */
    $storage = $event->getStorage();

    // Since it's unlikley that we can successfully lookup any referenced pages
    // by their UUID yet as config is imported before content, we will remove
    // the current paths. Once importing is complete, another event will fire
    // that will set the denormalized paths then.
    $siteConfig = $storage->read('system.site');
    if ($siteConfig) {
      $result = [];
      foreach ($siteConfig['page'] as $type=>$path) {
        $result[$type] = '';
      }
      $siteConfig['page'] = $result;
      $storage->write('system.site', $siteConfig);
    }
  }

  /**
   * The storage is transformed for exporting.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The config storage transform event.
   */
  public function onExportTransform(StorageTransformEvent $event) {
    /** @var \Drupal\Core\Config\StorageInterface $storage */
    $storage = $event->getStorage();

    // Normalize site config
    $siteConfig = $storage->read('system.site');
    if (!isset($siteConfig)) {
      return;
    }

    $systemPages = $siteConfig['page'];
    if (isset($systemPages)) {
      $result = [];
      foreach ($systemPages as $type => $path) {
        $result[$type] = TomeSyncHelper::normalizePathReference($path);
      }

      $this->logger->debug('Normalizing site paths from: ' . json_encode($systemPages) . ' to: ' . json_encode($result));

      $siteConfig['page'] = $result;
      $storage->write('system.site', $siteConfig);
    }
  }
}
