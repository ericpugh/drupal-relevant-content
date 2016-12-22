<?php

namespace Drupal\relevant_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Config\ConfigFactory;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\node\Entity\Node;

/**
 * Class RelevantContentBlockForm.
 *
 * @package Drupal\relevant_content\Form
 */
class RelevantContentBlockForm extends FormBase {

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
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;
  public function __construct(
    EntityTypeManager $entity_type_manager,
    LoggerChannelFactory $logger_factory,
    ConfigFactory $config_factory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
    $this->configFactory = $config_factory;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('config.factory')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'relevant_content_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

      $vids = ['mediums','tags'];
      $allowed = ['artwork'];
      $max = 10;
      $node = Node::load(660); // Joan Mitchell sunflowers
      $relevantQuery = \Drupal::service('relevant_content.query');
      $relevantQuery
          ->addNode($node)
          ->filterByVocabularies($vids)
          ->setAllowedContentTypes($allowed)
          ->addAllowedContentType('article')
          //->addExcluded(660)
          ->setMaxResults($max);

//      kint($relevantQuery->getTermReferenceFieldsInfo($node));
//      kint($relevantQuery->tids);

      $result = $relevantQuery->execute();
      kint($result);


    return $form;
  }

    /**
     * Get a list of content types as form options.
     *
     * @return  array $list
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
     * @return  array $list
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
    * {@inheritdoc}
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
        drupal_set_message($key . ': ' . print_r($value, TRUE));

    }

  }

}
