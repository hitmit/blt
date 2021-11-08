<?php

namespace Drupal\tome_sync\Commands;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\tome_base\CommandBase;
use Drupal\tome_sync\ImporterInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Contains the tome:import-config command.
 *
 * @internal
 */
class ImportConfigCommand extends CommandBase {

  /**
   * The importer.
   *
   * @var \Drupal\tome_sync\ImporterInterface
   */
  protected $importer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The state system.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs an ImportCommand instance.
   *
   * @param \Drupal\tome_sync\ImporterInterface $importer
   *   The importer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state system.
   */
  public function __construct(ImporterInterface $importer, EntityTypeManagerInterface $entity_type_manager, StateInterface $state) {
    parent::__construct();
    $this->importer = $importer;
    $this->entityTypeManager = $entity_type_manager;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  protected  function configure() {
    $this->setName('tome:import-config')
      ->setDescription('Installs site and imports config.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $this->prepareConfigForImport();
    if (!$this->runCommand($this->executable . " config:import -y", NULL, NULL)) {
      # Ignore errors from here 
      // return 1;
    }

  }

  /**
   * Prepares config for import by copying some directly from the source.
   */
  protected function prepareConfigForImport() {
    /** @var \Drupal\Core\Config\StorageInterface $source_storage */
    $source_storage = \Drupal::service('config.storage.sync');
    if ($site_data = $source_storage->read('system.site')) {
      \Drupal::configFactory()->getEditable('system.site')->setData($site_data)->save(TRUE);
      if (!empty($site_data['default_langcode']) && $language_data = $source_storage->read('language.entity.' . $site_data['default_langcode'])) {
        \Drupal::configFactory()->getEditable('language.entity.' . $site_data['default_langcode'])->setData($language_data)->save(TRUE);
      }
    }
  }

}
