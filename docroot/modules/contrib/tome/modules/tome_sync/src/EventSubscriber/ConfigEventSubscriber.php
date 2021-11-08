<?php

namespace Drupal\tome_sync\EventSubscriber;

use Psr\Log\LoggerInterface;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigRenameEvent;
use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\StorageTransformEvent;
use Drupal\Core\Config\StorageTransformerException;
use Drupal\Core\Lock\LockBackendInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Keeps the config export directory synced with config CRUD operations.
 *
 * @internal
 */
class ConfigEventSubscriber implements EventSubscriberInterface {

  /**
   * The name used to identify the lock.
   */
  const LOCK_NAME = 'config_event_export';

  /**
   * The module logger
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The config storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * The used lock backend instance.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * Constructs the ConfigEventSubscriber object.
   *
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The config storage.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger
   */
  public function __construct(StorageInterface $config_storage, EventDispatcherInterface $eventDispatcher, LoggerInterface $logger, LockBackendInterface $lock) {
    $this->configStorage = $config_storage;
    $this->eventDispatcher = $eventDispatcher;
    $this->logger = $logger;
    $this->lock = $lock;
  }

  /**
   * Reacts to a save event.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The configuration event.
   */
  public function configSave(ConfigCrudEvent $event) {
    if (!\Drupal::isConfigSyncing() && !isset($GLOBALS['_tome_sync_installing'])) {
      $config = $event->getConfig();
      $configName = $config->getName();
      $configValue = $config->getRawData();

      // Acquire a lock for the request to assert that the storage does not change
      // when a concurrent request transforms the storage.
      if (!$this->lock->acquire(self::LOCK_NAME)) {
        $this->lock->wait(self::LOCK_NAME);
        if (!$this->lock->acquire(self::LOCK_NAME)) {
          throw new StorageTransformerException("Cannot acquire config export transformer lock.");
        }
      }

      $this->logger->debug('Saving config: ' . $configName);
      // $this->logger->debug('  -- initial value: ' . json_encode($configValue));

      // Add the updated config to a temporary storage so that we can have any config transformers manipulate it
      $tempStorage = new MemoryStorage();
      $tempStorage->write($configName, $configValue);

      // Allow others to transform the config before we export it to disk
      $this->eventDispatcher->dispatch(ConfigEvents::STORAGE_TRANSFORM_EXPORT, new StorageTransformEvent($tempStorage));

      $transformedConfig = $tempStorage->read($configName);
      // $this->logger->debug('  -- transformed value: ' . json_encode($transformedConfig));

      // Write the transformed config out to disk
      $this->configStorage->write($configName, $tempStorage->read($configName));

      $this->lock->release(self::LOCK_NAME);
    }
  }

  /**
   * Reacts to delete event.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The configuration event.
   */
  public function configDelete(ConfigCrudEvent $event) {
    if (!\Drupal::isConfigSyncing() && !isset($GLOBALS['_tome_sync_installing'])) {
      $this->configStorage->delete($event->getConfig()->getName());
    }
  }

  /**
   * Reacts to rename event.
   *
   * @param \Drupal\Core\Config\ConfigRenameEvent $event
   *   The configuration event.
   */
  public function configRename(ConfigRenameEvent $event) {
    if (!\Drupal::isConfigSyncing() && !isset($GLOBALS['_tome_sync_installing'])) {
      $this->configStorage->rename($event->getOldName(), $event->getConfig()->getName());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['configSave'];
    $events[ConfigEvents::DELETE][] = ['configDelete'];
    $events[ConfigEvents::RENAME][] = ['configRename'];
    return $events;
  }

}
