<?php

namespace Drupal\alb_auth\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\alb_auth\Claims\ClaimsExtractorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The module configuration form.
 */
class ConfigurationForm extends ConfigFormBase {

  /**
   * The claims extractor.
   *
   * @var \Drupal\alb_auth\Claims\ClaimsExtractorInterface
   */
  protected $claimsExtractor;

  /**
   * The HTTP request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs the ConfigurationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\alb_auth\Claims\ClaimsExtractorInterface $claims_extractor
   *   The claims extractor service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClaimsExtractorInterface $claims_extractor) {
    parent::__construct($config_factory);
    $this->claimsExtractor = $claims_extractor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = new static(
      $container->get('config.factory'),
      $container->get('alb_auth.claims_extractor')
    );
    // TODO: Inject the 'request_stack' service, get the current request at
    // TODO: runtime. Only services should be injected.
    if ($request = \Drupal::request()) {
      $form->setRequest($request);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'alb_auth_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['alb_auth.settings'];
  }

  /**
   * Sets the request.
   *
   * TODO: This method should be probably removed and the request_stack service
   * TODO: should be injected instead.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   */
  public function setRequest(Request $request) {
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('alb_auth.settings');

    $claims = NULL;
    if ($this->request) {
      $claims = $this->claimsExtractor->getClaimsFromRequest($this->request);
    }

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t("Enable ALB authentication. Check that it's working first."),
      '#default_value' => $config->get('enabled'),
      '#disabled' => empty($claims),
    ];
    $form['aws_region'] = [
      '#type' => 'textfield',
      '#title' => $this->t('AWS region'),
      '#description' => $this->t('AWS region of the Application Load Balancer'),
      '#default_value' => $config->get('aws_region'),
    ];
    $form['cookie_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cookie name'),
      '#description' => $this->t('ALB authentication cookie name. Note that the ALB seems to add a prefix of -0 to what you define.'),
      '#default_value' => $config->get('cookie_name'),
    ];
    $form['provision'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Provisioning'),
      '#description' => $this->t("Create users automatically if they don't already exist in Drupal."),
      '#default_value' => $config->get('provision'),
    ];
    $form['logout'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Logout settings'),
      '#collapsible' => FALSE,
    ];
    $form['logout']['cognito_base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cognito base URL'),
      '#default_value' => $config->get('cognito.base_url'),
    ];
    $form['logout']['cognito_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cognito client ID'),
      '#default_value' => $config->get('cognito.client_id'),
    ];
    $form['claims'] = [
      '#type' => 'item',
      '#title' => $this->t('Claims'),
      '#description' => $this->t('Claims available from the current request'),
      'list' => [
        '#type' => 'table',
        '#header' => [$this->t('Name'), $this->t('Value')],
        '#empty' => $this->t('No claims found - is everything set up correctly?'),
      ],
    ];

    if ($claims) {
      foreach ((array) $claims as $name => $value) {
        $form['claims']['list'][$name] = [
          'name'  => ['#plain_text' => $name],
          'value' => ['#plain_text' => $value],
        ];
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('alb_auth.settings')
      ->set('aws_region', $values['aws_region'])
      ->set('enabled', $values['enabled'])
      ->set('cookie_name', $values['cookie_name'])
      ->set('provision', $values['provision'])
      ->set('cognito.base_url', $values['cognito_base_url'])
      ->set('cognito.client_id', $values['cognito_client_id'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
