<?php

namespace Drupal\tome_sync\Commands;

use Drupal\tome_sync\TomeSyncHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Contains the tome:import-files command.
 *
 * @internal
 */
class ImportFilesCommand extends ImportCommand {

  /**
   * {@inheritdoc}
   */
  protected  function configure() {
    $this->setName('tome:import-files')
      ->setDescription('Imports files.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->importer->importFiles();
  }

}
