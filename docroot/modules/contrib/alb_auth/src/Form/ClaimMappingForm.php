<?php

namespace Drupal\alb_auth\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the claim mapping add and edit forms.
 */
class ClaimMappingForm extends EntityForm {

  /**
   * Constructs a ClaimMappingForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $mapping = $this->entity;

    $form['label'] = [
      // Is this really necessary or can we infer it somehow?
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $mapping->label(),
      '#description' => $this->t("Label for the claim mapping."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $mapping->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$mapping->isNew(),
    ];

    $form['claim_source'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Claim source'),
      '#default_value' => $mapping->getClaimSource(),
      '#description' => $this->t('Name of the JWT claim to map from'),
      '#required' => TRUE,
    ];
    $form['mapping_target'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mapping target'),
      '#default_value' => $mapping->getMappingTarget(),
      '#description' => $this->t('Field to map to'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $claim_mapping = $this->entity;
    $status = $claim_mapping->save();

    if ($status) {
      $this->messenger()->addStatus($this->t('Saved the %label claim mapping.', [
        '%label' => $claim_mapping->label(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('The %label claim mapping was not saved.', [
        '%label' => $claim_mapping->label(),
      ]));
    }

    $form_state->setRedirect('entity.claim_mapping.collection');
  }

  /**
   * Helper function to check whether a ClaimMapping entity exists.
   *
   * TODO: Check if this method should be public.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager->getStorage('claim_mapping')->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}
