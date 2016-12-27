<?php

namespace Drupal\relevant_content;

use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\node\NodeInterface;
use Drupal\core\Database\Connection;
use Drupal\node\Entity\Node;

/**
 * Class QueryService.
 *
 * @package Drupal\relevant_content
 */
class QueryService implements QueryServiceInterface {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;
  /**
   * Drupal\Core\Logger\LoggerChannelFactory definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Current Node.
   *
   * @var \Drupal\node\Entity\Node
   */
  public $node;

  public $vocabularies;
  public $maxResults = 5;
  public $allowed;
  public $excluded;
  public $tids;

  /**
   * Constructor.
   */
  public function __construct(Connection $connection, LoggerChannelFactory $logger_factory) {
    $this->connection = $connection;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Set the target node to find relevant content.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Node.
   *
   * @return object
   *   The current instance of QueryService.
   */
  public function addNode(NodeInterface $node) {
    $this->node = $node;
    return $this;
  }

  /**
   * Set a specific node id to exclude from results.
   *
   * @param int $id
   *   The Node ID.
   *
   * @return object
   *   The current instance of QueryService
   */
  public function addExcluded($id) {
    if (is_int($id)) {
      if (is_array($this->excluded)) {
        $this->excluded[] = $id;
      }
      else {
        $this->excluded = [$id];
      }
    }
    return $this;
  }

  /**
   * Set the vocabularies vids used to filter content.
   *
   * @param array $vids
   *   The Vocabularies vids.
   *
   * @return object
   *    The current instance of QueryService
   */
  public function filterByVocabularies(array $vids) {
    if (!$this->node) {
      throw new \Exception('Missing properties to find relevant content. See documentation for service usage.');
    }
    // Set the Vocabularies used to filter by.
    if (is_array($vids)) {
      $this->vocabularies = $vids;
    }
    else {
      $this->vocabularies = [$vids];
    }
    // Retrieve info about the terms referenced in the Node.
    $terms = $this->getReferencedTermIds($this->node);
    // Set Term ids on object property.
    $this->tids = array_keys($terms);

    return $this;
  }

  /**
   * Set the maximum number of results to retrieve.
   *
   * @param int $max
   *   The Vocabularies vids.
   *
   * @return object
   *    The current instance of QueryService
   */
  public function setMaxResults($max) {
    if (is_int($max)) {
      $this->maxResults = $max;
    }
    return $this;
  }

  /**
   * Set the allowed node types to retrieve.
   *
   * @param array $types
   *   Array of node types.
   *
   * @return object
   *    The current instance of QueryService
   */
  public function setAllowedContentTypes(array $types) {
    $this->allowed = $types;
    return $this;
  }

  /**
   * Add content type to the list of allowed types.
   *
   * @param string $type
   *   The Node type. (ie "article")
   *
   * @return object
   *    The current instance of QueryService
   */
  public function addAllowedContentType($type) {
    if (is_array($this->allowed)) {
      $this->allowed[] = $type;
    }
    else {
      $this->allowed = [$type];
    }
    return $this;
  }

  /**
   * Get taxonomy terms referenced in a Node's fields.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Node.
   *
   * @return array
   *   Array of taxonomy term ids and associated vid, keyed by term id.
   */
  public function getReferencedTermIds(NodeInterface $node = NULL) {
    if (is_null($node) && !empty($this->node)) {
      $node = $this->node;
    }
    $terms = [];
    if ($node) {
      $query = $this->connection->select('taxonomy_index', 't');
      $query->join('taxonomy_term_data', 'td', 'td.tid = t.tid');
      // We need the Term ID, Name and Vocabulary ID.
      $query->addField('t', 'tid');
      $query->fields('td', ['vid']);
      // And we need to filter for the node in question.
      $query->condition('t.nid', $node->id());
      // Get the terms as a tid-indexed array of objects.
      $terms = $query->execute()->fetchAllAssoc('tid');
    }
    // Remove terms not in the vocabularies filters.
    if ($this->vocabularies) {
      foreach ($terms as $tid => $term) {
        if (property_exists($term, 'vid')) {
          if (!in_array($term->vid, $this->vocabularies)) {
            unset($terms[$tid]);
          }
        }
      }
    }

    return $terms;
  }

  /**
   * Get a list of a Node's entity reference (taxonomy_term) fields.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Node.
   *
   * @return array
   *   Array of reference fields with id, target_type,
   *   and target_bundles keyed by field name.
   */
  public function getTermReferenceFieldsInfo(NodeInterface $node = NULL) {
    if (is_null($node) && !empty($this->node)) {
      $node = $this->node;
    }
    $info = [];
    if ($node) {
      $field_definitions = $node->getFieldDefinitions();
      foreach ($field_definitions as $definition) {
        $field_storage_settings = $definition->getFieldStorageDefinition()->getSettings();
        $field = $definition->toArray();
        if (isset($field['field_type']) && $field['field_type'] == "entity_reference") {
          // Set the array key to field name.
          $key = $field['field_name'];
          if (isset($field['settings']) && isset($field['settings']['handler'])) {
            if (isset($field_storage_settings['target_type']) && $field_storage_settings['target_type'] == 'taxonomy_term') {
              $info[$key]['target_type'] = $field_storage_settings['target_type'];
              // Set the node value.
              $info[$key]['value'] = $node->get($key)->getValue();
              // Set target bundle and other information about the current field.
              $info[$key]['id'] = $field['id'];
              if (isset($field['settings']['handler_settings']) && isset($field['settings']['handler_settings']['target_bundles'])) {
                $info[$key]['target_bundles'] = $field['settings']['handler_settings']['target_bundles'];
              }
            }
          }
        }
      }
    }
    return $info;
  }

  /**
   * Get relevant content.
   *
   * @return array
   *   Information array of relevant nodes with nid, type,
   *   and relevance count, keyed by Node id.
   */
  public function execute() {
    // @TODO: try /catch
    try {
      if (!$this->node || !$this->vocabularies) {
        // There's nothing to find relevant content for.
        throw new \Exception('Missing properties to find relevant content. See documentation for service usage.');
      }
      // Define the query.
      $query = $this->connection->select('node_field_data', 'n');
      $query->leftJoin('taxonomy_index', 't', 'n.nid = t.nid');
      $query->fields('n', ['nid', 'type']);
      $query->addExpression('COUNT(*)', 'cnt');
      // Filter for published nodes.
      $query->condition('n.status', 1);
      // Exclude specific Node ids from the results.
      // Always exclude the current node id.
      $this->excluded[] = $this->node->id();
      $query->condition('n.nid', $this->excluded, '<>');
      // Filter by allowed content types.
      if (!empty($this->allowed)) {
        $query->condition('n.type', $this->allowed, 'IN');
      }
      // Require item to have at least one of the terms.
      $query->condition('t.tid', $this->tids, 'IN');
      // Group, order and limit the query.
      $query->groupBy('n.nid')
        ->groupBy('n.type')
        ->orderBy('cnt', 'DESC')
        ->orderBy('n.created', 'DESC')
        ->orderBy('n.nid', 'DESC')
        ->range(0, $this->maxResults);

      // Execute and loop to store the results against the node ID key.
      $results = $query->execute()->fetchAllAssoc('nid', \PDO::FETCH_ASSOC);

      return empty($results) ? FALSE : $results;
    }
    catch (\Exception $exception) {
      $this->loggerFactory->get('relevant_content')->error($exception->getMessage());
    }
  }

  /**
   * Load Nodes from the results of a QueryService query.
   *
   * @return array
   *   An array of node objects.
   */
  public function loadNodes($query_results, $view_mode = 'teaser') {
    $items = [];
    if (is_array($query_results)) {
      $nids = array_keys($query_results);
      $nodes = Node::loadMultiple($nids);
      foreach ($nodes as $node) {
        if (isset($query_results[$node->id()])) {
          // Append the count to node for theming.
          $node->relevant_content_count = $query_results[$node->id()]['cnt'];
        }
        $items[] = node_view($node, $view_mode);
      }
    }
    return $items;
  }

}
