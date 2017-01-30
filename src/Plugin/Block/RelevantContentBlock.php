<?php

namespace Drupal\relevant_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\relevant_content\QueryService;
use Drupal\Core\Entity\EntityDisplayRepository;

/**
 * Provides a 'RelevantContentBlock' block.
 *
 * @Block(
 *  id = "relevant_content_block",
 *  admin_label = @Translation("Relevant Content"),
 *  context = {
 *     "node" = @ContextDefinition("entity:node", required = TRUE, label = @Translation("Current Node"))
 *  }
 * )
 */
class RelevantContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;
  /**
   * Drupal\Core\Logger\LoggerChannelFactory definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;
  /**
   * Drupal\relevant_content\QueryService definition.
   *
   * @var \Drupal\relevant_content\QueryService
   */
  protected $relevantQuery;
  /**
   * The Entity Display Repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepository
   */
  protected $entityDisplayRepository;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        EntityTypeManager $entity_type_manager,
        LoggerChannelFactory $logger_factory,
        QueryService $query_service,
        EntityDisplayRepository $entity_display_repository
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
    $this->relevantQuery = $query_service;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
        $container->get('entity_type.manager'),
      $container->get('logger.factory'),
        $container->get('relevant_content.query'),
        $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
    $contentTypes = $this->getContentTypeOptions();
    $vocabularies = $this->getVocabulariesOptions();

    // Wrapping form elements using prefix/suffix for display after
    // having problems setting config values nested in a fieldset.
    $form['vocabularies'] = [
      '#prefix' => sprintf('<details class="form-wrapper" open="open"><summary>%s</summary><div class="details-wrapper">', $this->t('Relevant Content Search Criteria')),
      '#type' => 'checkboxes',
      '#title' => $this->t('Vocabularies'),
      '#description' => $this->t('The referenced vocabularies used to find relevant content by term.'),
      '#options' => $vocabularies,
      '#default_value' => isset($config['vocabularies']) ? $config['vocabularies'] : [],
      '#suffix' => '</div></details>',
    ];

    $form['number_relevant_content'] = [
      '#prefix' => sprintf('<details class="form-wrapper" open="open"><summary>%s</summary><div class="details-wrapper">', $this->t('Relevant Content Results Settings')),
      '#type' => 'number',
      '#title' => $this->t('Number of items'),
      '#size' => 5,
      '#required' => TRUE,
      '#description' => $this->t('The maximum number of items to display.'),
      '#default_value' => isset($config['number_relevant_content']) ? $config['number_relevant_content'] : '',
    ];
    $form['allowed_content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed Results Types'),
      '#description' => $this->t('The relevant content types to include in results.'),
      '#checked' => 'checked',
      '#options' => $contentTypes,
      '#default_value' => isset($config['allowed_content_types']) ? $config['allowed_content_types'] : [],
    ];
    $form['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View Mode'),
      '#description' => $this->t('Select the view mode to use for output of relevant content.'),
      '#options' => $this->getViewModeOptions(),
      '#default_value' => isset($config['view_mode']) ? $config['view_mode'] : [],
      '#suffix' => '</div></details>',
    ];

    return $form;
  }

  /**
   * Get a list of content types as form options.
   *
   * @return array
   *   Array of content type labels keyed by id.
   */
  protected function getContentTypeOptions() {
    $contentTypes = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $options = [];
    foreach ($contentTypes as $contentType) {
      $options[$contentType->id()] = $contentType->label();
    }
    return $options;
  }

  /**
   * Get a list of vocabularies as form options.
   *
   * @return array
   *   Array of vocabulary labels keyed by id.
   */
  protected function getVocabulariesOptions() {
    $vocabularies = Vocabulary::loadMultiple();
    $options = [];
    foreach ($vocabularies as $vocabulary) {
      $options[$vocabulary->get('vid')] = $vocabulary->label();
    }
    return $options;
  }

  /**
   * Get a list of view modes as form options.
   *
   * @return array
   *   Array of view modes as options.
   */
  protected function getViewModeOptions() {
    return $this->entityDisplayRepository->getViewModeOptions('node');
  }

  /**
   * Get checked option values from a list of options.
   *
   * @return array
   *   Array of option values.
   */
  protected function getCheckedOptionValues($options) {
    $checked = [];
    // Get the checked options.
    // Checked values equal their key, while unchecked equal zero.
    foreach ($options as $key => $value) {
      if ($key === $value) {
        $checked[] = $value;
      }
    }
    return $checked;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('vocabularies', $form_state->getValue('vocabularies'));
    $this->setConfigurationValue('number_relevant_content', $form_state->getValue('number_relevant_content'));
    $this->setConfigurationValue('allowed_content_types', $form_state->getValue('allowed_content_types'));
    $this->setConfigurationValue('view_mode', $form_state->getValue('view_mode'));
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $config = $this->getConfiguration();
    // Get the current node the block is displayed on.
    $node = $this->getContextValue('node');
    // Get relevant content with the QueryService.
    $vids = $this->getCheckedOptionValues($config['vocabularies']);
    $allowed = $this->getCheckedOptionValues($config['allowed_content_types']);
    $limit = $config['number_relevant_content'];
    $relevant_results = $this->relevantQuery
      ->addNode($node)
      ->filterByVocabularies($vids)
      ->setAllowedContentTypes($allowed)
      ->setMaxResults($limit)
      ->execute();

    $nodes = $this->relevantQuery->loadNodes($relevant_results, $config['view_mode']);
    if (!empty($nodes)) {
      $build['#theme'] = ['item_list__relevant_content'];
      $build['#context'] = ['list_style' => 'relevant_content'];
      $build['#items'] = $nodes;
      $build['#cache']['max-age'] = 3600;
    }
    return $build;
  }

}
