<?php

namespace Drupal\media_entity_download\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_entity_download\Plugin\Field\FieldType\MediaDownloadPathItem;
use Drupal\path\Plugin\Field\FieldWidget\PathWidget;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'path' widget.
 *
 * @FieldWidget(
 *   id = "media_download_path",
 *   label = @Translation("Download URL alias"),
 *   field_types = {
 *     "media_download_path"
 *   }
 * )
 */
class MediaDownloadPathWidget extends PathWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $entity = $items->getEntity();
    $element = parent::formElement($items, $delta, $element,$form, $form_state);

    $element['alias']['#description'] = $this->t('Specify an alternative path by which this data can be accessed. For example, type "/my-document.pdf" for an PDF document.');
    $element['source']['#value'] = !$entity->isNew() ? '/' . MediaDownloadPathItem::getMediaDownloadPath($entity) : NULL;

    // If the advanced settings tabs-set is available (normally rendered in the
    // second column on wide-resolutions), place the field as a details element
    // in this tab-set.
    if (isset($form['advanced'])) {
      $element['#title'] = $this->t('Download URL alias');
      $element['#attributes']['class'] = ['media-download-path-form'];
      $element['#attached']['library'] = ['media_entity_download/download_path.tabs'];
      $element['#weight'] = 31;
    }

    return $element;
  }

}
