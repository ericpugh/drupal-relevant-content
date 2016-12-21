<?php

namespace Drupal\relevant_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Extension\ModuleHandler;

/**
 * Provides a 'RelevantContentBlock' block.
 *
 * @Block(
 *  id = "relevant_content_block",
 *  admin_label = @Translation("Relevant content block"),
 * )
 */
class RelevantContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;
  /**
   * Drupal\Core\Logger\LoggerChannelFactory definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;
  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;
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
        QueryFactory $entity_query, 
	LoggerChannelFactory $logger_factory, 
	ModuleHandler $module_handler
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityQuery = $entity_query;
    $this->loggerFactory = $logger_factory;
    $this->moduleHandler = $module_handler;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.query'),
      $container->get('logger.factory'),
      $container->get('module_handler')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['relevant_content_block']['#markup'] = 'Implement RelevantContentBlock.';

    return $build;
  }

}
