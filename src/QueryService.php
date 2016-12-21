<?php

namespace Drupal\relevant_content;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class QueryService.
 *
 * @package Drupal\relevant_content
 */
class QueryService implements QueryServiceInterface {

  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;
  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;
  /**
   * Drupal\Core\Logger\LoggerChannelFactory definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;
  /**
   * Constructor.
   */
  public function __construct(QueryFactory $entity_query, ModuleHandler $module_handler, LoggerChannelFactory $logger_factory) {
    $this->entityQuery = $entity_query;
    $this->moduleHandler = $module_handler;
    $this->loggerFactory = $logger_factory;
  }

}
