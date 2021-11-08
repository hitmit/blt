<?php

namespace Drupal\tome_sync;

use Drupal\Core\Site\Settings;
use Drupal\file\FileInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\File\FileSystemInterface;
use Drupal\tome_base\PathTrait;
use Drupal\Core\Config\StorageException;
use Drupal\Component\FileSecurity\FileSecurity;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;

/**
 * Handles file import and exports by keeping a file export directory in sync.
 *
 * @internal
 */
class FileSync implements FileSyncInterface {

  use PathTrait;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
   */
  protected $streamWrapperManager;

  /**
   * Schemas to blacklist
   *
   */
  protected $blacklistSchemes;

  /**
   * Creates an FileSync object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   */
  public function __construct(FileSystemInterface $file_system, StreamWrapperManagerInterface $streamWrapperManager) {
    $this->fileSystem = $file_system;
    $this->blacklistSchemes = Settings::get('tome_scheme_blacklist', ['private']);
    $this->streamWrapperManager = $streamWrapperManager;
  }

  /**
   * {@inheritdoc}
   */
  public function importFiles() {
    $base_file_directory = $this->getFileDirectory();
    /** @var \Drupal\file\FileInterface $file */


    foreach ($this->fileSystem->scanDirectory($base_file_directory, '/.*/', [ 'recurse' => FALSE ]) as $schemeDir) {
      $scheme = $schemeDir->name;

      if (!$this->streamWrapperManager->isValidScheme($scheme)) {
        \Drupal::logger('tome_sync')->notice('Unsupported file scheme: ' . $scheme . ', skipping import of files');
      } else {

        if (!in_array($scheme, $this->blacklistSchemes)) {

          $scheme_file_directory = $base_file_directory . '/' . $scheme;
          foreach ($this->fileSystem->scanDirectory($scheme_file_directory, '/.*/') as $file) {
            $destination = $scheme . '://' . ltrim(str_replace($scheme_file_directory, '', $file->uri), '/');
            $directory = $this->fileSystem->dirname($destination);
            $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
            $this->fileSystem->copy($file->uri, $destination, FileSystemInterface::EXISTS_REPLACE);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteExportDirectory() {
    $base_file_directory = $this->getFileDirectory();
    if (file_exists($base_file_directory)) {
      if (!$this->fileSystem->deleteRecursive($base_file_directory)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function exportFile(FileInterface $file) {
    $scheme = $this->streamWrapperManager->getScheme($file->getFileUri());
    $scheme = !$scheme ? 'public' : $scheme;

    if (in_array($scheme, $this->blacklistSchemes)) {
      \Drupal::logger('tome_sync')->info('Blacklisted file scheme: ' . $scheme . ', skipping export of files');
    } else {
      $this->ensureFileDirectory($scheme);
      $scheme_file_directory = $this->getFileDirectory() . '/' . $scheme;

      if (!file_exists($file->getFileUri())) {
        \Drupal::logger('tome_sync')->notice('Failed to export file: ' . $file->getFileUri() . ', as it does not exist on disk');
      } else {
        $destination = $this->joinPaths($scheme_file_directory, $this->streamWrapperManager->getTarget($file->getFileUri()));
        $directory = $this->fileSystem->dirname($destination);
        $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
        $this->fileSystem->copy($file->getFileUri(), $destination, FileSystemInterface::EXISTS_REPLACE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteFileExport(FileInterface $file) {
    $base_file_directory = $this->getFileDirectory();
    // if (strpos($file->getFileUri(), 'public://') === 0) {
      $path = $this->joinPaths($base_file_directory, StreamWrapperManager::getTarget($file->getFileUri()));
      if (file_exists($path)) {
        $this->fileSystem->delete($path);
      }
    // }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteFile($filename) {
    $path = $this->joinPaths($this->getFileDirectory(), $filename);
    if (file_exists($path)) {
      $this->fileSystem->delete($path);
    }
  }

  /**
   * Gets the file directory.
   *
   * @return string
   *   The file directory.
   */
  protected function getFileDirectory() {
    return Settings::get('tome_files_directory', '../files');
  }

  /**
   * Ensures that the file directory exists.
   */
  protected function ensureFileDirectory($scheme) {
    $base_file_directory = $this->getFileDirectory();

    if (!file_exists($base_file_directory)) {
      $this->fileSystem->prepareDirectory($base_file_directory, FileSystemInterface::CREATE_DIRECTORY);
      FileSecurity::writeHtaccess($base_file_directory);
    }

    $scheme_file_directory = $base_file_directory . '/' . $scheme;
    $this->fileSystem->prepareDirectory($scheme_file_directory, FileSystemInterface::CREATE_DIRECTORY);
    if (!file_exists($scheme_file_directory)) {
      throw new StorageException('Failed to create file directory: ' . $scheme_file_directory);
    }
  }

}
