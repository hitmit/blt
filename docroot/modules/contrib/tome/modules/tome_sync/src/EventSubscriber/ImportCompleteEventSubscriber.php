<?php

namespace Drupal\tome_sync\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\tome_sync\Event\TomeSyncEvents;
use Drupal\tome_sync\TomeSyncHelper;

class ImportCompleteEventSubscriber implements EventSubscriberInterface {

  /**
   * The module logger
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The primary drupal config storage
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * The exported drupal config storage
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $exportedConfigStorage;

  /**
   * Creates a BookEventSubscriber object.
   *
   * @param \Drupal\Core\Config\StorageInterface $configStorage
   *   The primary drupal config storage
   * @param \Drupal\Core\Config\StorageInterface $exportedConfigStorage
   *   The exported drupal config storage
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger
   */
  public function __construct(StorageInterface $configStorage, StorageInterface $exportedConfigStorage, LoggerInterface $logger) {
    $this->configStorage = $configStorage;
    $this->exportedConfigStorage = $exportedConfigStorage;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[TomeSyncEvents::IMPORT_ALL][] = ['onImportComplete'];
    return $events;
  }

  /**
   * Fired once we have completed importing all config and content
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The event.
   */
  public function onImportComplete(Event $event) {
    // After we have completed importing a sites config and content we need to set
    // the default home and error pages. Since drupal wants these as node id's
    // and we have exported them to disk using it's UUID, we need to denormalize
    // the paths and write them to the current config
    //
    // We can only do this after we have completed importing to ensure the entity
    // we need to lookup exists in the database
    $siteConfig = $this->exportedConfigStorage->read('system.site');
    if (!isset($siteConfig)) {
      return;
    }

    $result = [];

    $systemPages = $siteConfig['page'];
    if (isset($systemPages)) {
      foreach ($systemPages as $type => $path) {
        $result[$type] = TomeSyncHelper::denormalizePathReference($path);
      }

      $this->logger->debug('Denormalizing site paths from: ' . json_encode($systemPages) . ' to: ' . json_encode($result));

      $siteConfig['page'] = $result;
      $this->configStorage->write('system.site', $siteConfig);
    }
  }

}
