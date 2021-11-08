<?php

namespace Drupal\dx8_addtoany\Plugin\CustomElement;

use Drupal\cohesion_elements\CustomElementPluginBase;

/**
 * Creates a DX8 element for AddToAny buttons.
 *
 * @package Drupal\cohesion\Plugin\CustomElement
 *
 * @CustomElement(
 *   id = "addtoany_element",
 *   label = @Translation("AddToAny")
 * )
 */
class AddToAny extends CustomElementPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFields()
  {
    return [
      'icon_size' => [
        // This is the bootstrap class name that will be applied to the wrapping column.
        'htmlClass' => 'col-xs-12',
        // All form elements require a title.
        'title' => 'Icons size (px)',
        // The field type.
        'type' => 'textfield',
        // These fields are specific to this form field type.
        'placeholder' => '32',
      ],
      'universal' => [
        'htmlClass' => 'col-xs-3',
        'type' => 'checkbox',
        'title' => 'Universal',
        // These fields are specific to this form field type.
        'notitle' => false,
        'defaultValue' => true,
      ],
      'facebook' => [
        'htmlClass' => 'col-xs-3',
        'type' => 'checkbox',
        'title' => 'Facebook',
        // These fields are specific to this form field type.
        'notitle' => false,
        'defaultValue' => true,
      ],
      'twitter' => [
        'htmlClass' => 'col-xs-3',
        'type' => 'checkbox',
        'title' => 'Twitter',
        // These fields are specific to this form field type.
        'notitle' => false,
        'defaultValue' => true,
      ],
      'google_plus' => [
        'htmlClass' => 'col-xs-3',
        'type' => 'checkbox',
        'title' => 'Google+',
        // These fields are specific to this form field type.
        'notitle' => false,
        'defaultValue' => true,
      ],
      'linkedin' => [
        'htmlClass' => 'col-xs-3',
        'type' => 'checkbox',
        'title' => 'Linkedin',
        // These fields are specific to this form field type.
        'notitle' => false,
        'defaultValue' => false,
      ],
      'pinterest' => [
        'htmlClass' => 'col-xs-3',
        'type' => 'checkbox',
        'title' => 'Pinterest',
        // These fields are specific to this form field type.
        'notitle' => false,
        'defaultValue' => false,
      ],
      'whatsapp' => [
        'htmlClass' => 'col-xs-3',
        'type' => 'checkbox',
        'title' => 'WhatsApp',
        // These fields are specific to this form field type.
        'notitle' => false,
        'defaultValue' => false,
      ],
      'email' => [
        'htmlClass' => 'col-xs-3',
        'type' => 'checkbox',
        'title' => 'Email',
        // These fields are specific to this form field type.
        'notitle' => false,
        'defaultValue' => false,
      ],

      'universal_placement' => [
        'htmlClass' => 'col-xs-12',
        'type' => 'select',
        'title' => 'Universal button placement',
        // These fields are specific to this form field type.
        'nullOption' => true,
        'options' => [
          'before' => 'Before service icons',
          'after' => 'After service icons',
        ]
      ],
      'icon_color' => [
        'htmlClass' => 'col-xs-3',
        'type' => 'textfield',
        'title' => 'Icon Color',
        // These fields are specific to this form field type.
        'notitle' => false,
        'defaultValue' => '',
        'placeholder' => '#000000',
      ],
      'icon_background' => [
        'htmlClass' => 'col-xs-3',
        'type' => 'textfield',
        'title' => 'Icon Background',
        // These fields are specific to this form field type.
        'notitle' => false,
        'defaultValue' => '',
        'placeholder' => '#000000',
        ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render($element_settings, $element_markup, $element_class)
  {
    // Get page title.
    $request = \Drupal::request();
    $page_title = '';
    if ($route = $request->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT)) {
      $page_title = \Drupal::service('title_resolver')->getTitle($request, $route);
    }

    // Sanitize the icon size.
    if (!isset($element_settings['icon_size']) || !is_numeric($element_settings['icon_size']) ||
      $element_settings['icon_size'] < 1 ||
      $element_settings['icon_size'] > 200) {
      // Set a sensible default for the icon size.
      $element_settings['icon_size'] = 32;
    }

    // Combine all the classes.
    $element_classes = [];
    if ($element_class != '') {
      $element_classes[] = $element_class;
    }

    if ($element_markup['classes'] != '') {
      $element_classes[] = $element_markup['classes'];
    }

    // Set up all the element attributes.
    $element_attributes = [];
    if (isset($element_markup['attributes'])) {
      foreach ($element_markup['attributes'] as $attribute) {
        $element_attributes[] = $attribute['attribute'] . '=' . json_encode($attribute['value'], JSON_UNESCAPED_UNICODE);
      }
    }

    // Send this to the template.
    return [
      '#theme' => 'addtoany_template',
      '#linkTitle' => $page_title,
      '#elementAttributes' => implode(' ', $element_attributes),
      '#elementSettings' => $element_settings,
      '#elementClasses' => implode(' ', $element_classes),
    ];
  }
}
