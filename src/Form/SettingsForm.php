<?php

namespace Drupal\nexteuropa_newsroom\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nexteuropa_newsroom\Helper\UniverseHelper;

class SettingsForm extends ConfigFormBase {

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Guzzle HTTP client.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client) {
    parent::__construct($config_factory);
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'nexteuropa_newsroom.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nexteuropa_newsroom_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('nexteuropa_newsroom.settings');

    $form['universe_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Universe settings'),
    ];
    $form['universe_settings']['universe_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Universe ID:'),
      '#default_value' => $config->get('universe_id'),
      '#description' => $this->t('Universe ID.'),
      '#required' => TRUE,
      '#disabled' => !empty(UniverseHelper::getUniverseId()),
    ];
    $form['universe_settings']['base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base newsroom URL:'),
      '#default_value' => $config->get('base_url'),
      '#description' => $this->t('Base newsroom URL.'),
      '#required' => TRUE,
    ];
    $form['universe_settings']['subsite'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of the subsite:'),
      '#default_value' => $config->get('subsite'),
      '#description' => $this->t('The value you enter here will be used to filter the items belonging to this website.'),
    ];
    $form['universe_settings']['app'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application:'),
      '#default_value' => $config->get('app'),
      '#description' => $this->t('Application name.'),
    ];
    $form['universe_settings']['app_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application key:'),
      '#default_value' => $config->get('app_key'),
      '#description' => $this->t('Application key (hash sha256).'),
    ];
    $form['universe_settings']['allowed_ips'] = [
      '#type' => 'textfield',
      '#title' => $this->t('IP addresses allowed for import:'),
      '#default_value' => $config->get('allowed_ips'),
      '#description' => $this->t('Comma separated list of IP addresses where the importer can be launched from.'),
    ];
    $newsroom_entities = [
      'item',
      'topic',
      'type',
    ];
    foreach ($newsroom_entities as $entity) {
      $form['universe_settings'][$entity . '_import_script'] = [
        '#type' => 'textfield',
        '#title' => $this->t("Import $entity script name:"),
        '#default_value' => $config->get($entity . '_import_script'),
        '#required' => TRUE,
      ];
      $form['universe_settings'][$entity . '_import_segment'] = [
        '#type' => 'textfield',
        '#title' => $this->t("URL chunk for single $entity import:"),
        '#default_value' => $config->get($entity . '_import_segment'),
        '#required' => TRUE,
      ];
    }

    $form['universe_settings']['item_edit_script'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL chunk to edit an item in the Newsroom:'),
      '#default_value' => $config->get('item_edit_script'),
      '#required' => TRUE,
    ];
    $form['universe_settings']['proposal_script'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Newsroom proposal script:'),
      '#default_value' => $config->get('proposal_script'),
    ];
    $form['universe_settings']['docsroom_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Newsroom Docsroom URL:'),
      '#default_value' => $config->get('docsroom_url'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('nexteuropa_newsroom.settings')
      ->set('universe_id', $values['universe_id'])
      ->set('base_url', $values['base_url'])
      ->set('allowed_ips', $values['allowed_ips'])
      ->set('app', $values['app'])
      ->set('app_key', $values['app_key'])
      ->set('subsite', $values['subsite'])
      ->set('item_import_script', $values['item_import_script'])
      ->set('item_import_segment', $values['item_import_segment'])
      ->set('topic_import_script', $values['topic_import_script'])
      ->set('topic_import_segment', $values['topic_import_segment'])
      ->set('type_import_script', $values['type_import_script'])
      ->set('type_import_segment', $values['type_import_segment'])
      ->set('item_edit_segment', $values['item_edit_segment'])
      ->set('proposal_script', $values['proposal_script'])
      ->set('docsroom_url', $values['docsroom_url'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $universe_id = $form_state->getValue('universe_id');
    $base_url = $form_state->getValue('base_url');
    $subsite = $form_state->getValue('subsite');
    if (!$this->validateUniverseId($base_url, $universe_id)) {
      $form_state->setErrorByName('universe_id', $this->t('Wrong newsroom universe ID.'));
    }
    if (!$this->validateSubsite($base_url, $universe_id, $subsite)) {
      $form_state->setErrorByName('subsite', $this->t('Wrong subsite.'));
    }
  }

  private function validateUniverseId($base_url, $universe_id) {
    $result = FALSE;

    try {
      $url = $this->buildUrl($base_url, $universe_id, 'logout.cfm');
      $response = $this->httpClient->get($url);
      $result = $response->getStatusCode() == 200;
    }
    catch (RequestException $exception) {
      watchdog_exception('nexteuropa_newsroom', $exception);
    }

    return $result;
  }

  private function buildUrl($base_part, $universe_id, $paramters) {
    return $base_part . $universe_id . '/' . $paramters;
  }

  private function validateSubsite($base_url, $universe_id, $subsite) {
    $result = TRUE;
    // The subsite is not mandatory.
    if (!empty($subsite)) {
      try {
        $url = $this->buildUrl($base_url, $universe_id, 'validation.cfm?subsite=' . $subsite);
        $response = $this->httpClient->get($url, ['headers' => ['Accept' => 'text/plain']]);
        $body = trim((string) $response->getBody());
        $result = $body == 'True' ? TRUE : FALSE;
      }
      catch (RequestException $exception) {
        watchdog_exception('nexteuropa_newsroom', $exception);
      }
    }

    return $result;
  }
}