<?php

namespace Drupal\Tests\media_entity_download\Functional;

use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\media\Entity\Media;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group media_entity_download
 */
class DownloadAliasTest extends BrowserTestBase {

  use MediaTypeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'node',
    'media',
    'file',
    'path',
    'media_entity_download',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'seven';

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setUp() {
    parent::setUp();

    // Create test user and log in.
    $web_user = $this->drupalCreateUser(['create media', 'update any media', 'create url aliases']);
    $this->drupalLogin($web_user);
  }


  /**
   * Tests the media form UI.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testMediaForm() {
    $assert_session = $this->assertSession();

    $media_type_id = 'testMediaForm';
    $this->createMediaType('file', ['id' => $media_type_id]);

    $this->drupalGet('media/add/' . $media_type_id);

    // Make sure we have a vertical tab fieldset and 'Download Path' field.
    $assert_session->elementContains('css', '.form-type-vertical-tabs #edit-media-download-path-0 summary', 'Download URL alias');
    $assert_session->fieldExists('media_download_path[0][alias]');

    // Disable the 'Download Path' field for this content type.
    \Drupal::service('entity_display.repository')->getFormDisplay('media', $media_type_id, 'default')
      ->removeComponent('media_download_path')
      ->save();

    $this->drupalGet('media/add/' . $media_type_id);

    // See if the whole fieldset is gone now.
    $assert_session->elementNotExists('css', '.form-type-vertical-tabs #edit-media-download-path-0');
    $assert_session->fieldNotExists('media_download_path[0][alias]');
  }


  /**
   * Tests if download alias get saved via media edit form
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testDownloadAlias() {

    $media_type_id = 'testDownloadAlias';
    $media_type = $this->createMediaType('file', ['id' => $media_type_id]);

    $src_field_definition = $media_type->getSource()->getSourceFieldDefinition($media_type);
    $src_field_name = $src_field_definition->getName();
    $src_field_value = FileItem::generateSampleValue($src_field_definition);

    $media_name = 'test media';
    $media = Media::create([
      'name' => $media_name,
      'bundle' => $media_type_id,
      $src_field_name => $src_field_value['target_id'],
    ]);
    $media->save();

    $test_alias = '/' . $this->randomMachineName();
    $edit = [];
    $edit['media_download_path[0][alias]'] = $test_alias;
    $this->drupalPostForm('media/' . $media->id() . '/edit', $edit, t('Save'));

    $alias_manager = $this->container->get('path_alias.manager');
    $resolved_path = $alias_manager->getPathByAlias($test_alias);
    $this->assertEquals($test_alias, $resolved_path, 'Download alias saved.');
  }

}
