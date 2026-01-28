<?php

declare(strict_types=1);

namespace Drupal\content_intel\Drush\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\content_intel\Service\ContentIntelCollector;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drush commands for Content Intelligence.
 *
 * Provides CLI access to entity data and intelligence from multiple sources.
 */
final class ContentIntelCommands extends DrushCommands {

  /**
   * Constructs ContentIntelCommands.
   *
   * @param \Drupal\content_intel\Service\ContentIntelCollector $collector
   *   The content intel collector service.
   */
  public function __construct(
    protected ContentIntelCollector $collector,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('content_intel.collector')
    );
  }

  /**
   * List available entity types.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   Entity types data.
   */
  #[CLI\Command(name: 'ci:types', aliases: ['cit'])]
  #[CLI\FieldLabels(labels: [
    'id' => 'ID',
    'label' => 'Label',
    'bundle_entity_type' => 'Bundle Type',
  ])]
  #[CLI\DefaultFields(fields: ['id', 'label'])]
  #[CLI\Usage(name: 'drush ci:types', description: 'List all content entity types')]
  #[CLI\Usage(name: 'drush ci:types --format=json', description: 'Get entity types as JSON')]
  public function types(): RowsOfFields {
    $types = $this->collector->getEntityTypes();
    return new RowsOfFields($types);
  }

  /**
   * List bundles for an entity type.
   *
   * @param string $entity_type
   *   The entity type ID.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   Bundles data.
   */
  #[CLI\Command(name: 'ci:bundles', aliases: ['cib'])]
  #[CLI\Argument(name: 'entity_type', description: 'The entity type ID')]
  #[CLI\FieldLabels(labels: [
    'id' => 'Bundle ID',
    'label' => 'Label',
  ])]
  #[CLI\Usage(name: 'drush ci:bundles node', description: 'List all node bundles')]
  #[CLI\Usage(name: 'drush ci:bundles taxonomy_term --format=json', description: 'Get taxonomy vocabularies as JSON')]
  public function bundles(string $entity_type): RowsOfFields {
    $bundles = $this->collector->getBundles($entity_type);
    return new RowsOfFields($bundles);
  }

  /**
   * List fields for an entity type and bundle.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string|null $bundle
   *   Optional bundle ID.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   Fields data.
   */
  #[CLI\Command(name: 'ci:fields', aliases: ['cif'])]
  #[CLI\Argument(name: 'entity_type', description: 'The entity type ID')]
  #[CLI\Argument(name: 'bundle', description: 'Optional bundle ID')]
  #[CLI\FieldLabels(labels: [
    'name' => 'Field Name',
    'label' => 'Label',
    'type' => 'Type',
    'required' => 'Required',
    'cardinality' => 'Cardinality',
  ])]
  #[CLI\DefaultFields(fields: ['name', 'label', 'type'])]
  #[CLI\Usage(name: 'drush ci:fields node article', description: 'List fields for article nodes')]
  #[CLI\Usage(name: 'drush ci:fields user', description: 'List base user fields')]
  public function fields(string $entity_type, ?string $bundle = NULL): RowsOfFields {
    $fields = $this->collector->getFields($entity_type, $bundle);
    return new RowsOfFields($fields);
  }

  /**
   * List available Content Intel plugins.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   Plugins data.
   */
  #[CLI\Command(name: 'ci:plugins', aliases: ['cip'])]
  #[CLI\FieldLabels(labels: [
    'id' => 'Plugin ID',
    'label' => 'Label',
    'description' => 'Description',
    'provider' => 'Provider',
    'available' => 'Available',
    'weight' => 'Weight',
  ])]
  #[CLI\DefaultFields(fields: ['id', 'label', 'provider', 'available'])]
  #[CLI\Usage(name: 'drush ci:plugins', description: 'List all intel plugins')]
  #[CLI\Usage(name: 'drush ci:plugins --format=json', description: 'Get plugins as JSON')]
  public function plugins(): RowsOfFields {
    $plugins = $this->collector->getPlugins();

    // Convert boolean to string for table display.
    foreach ($plugins as &$plugin) {
      $plugin['available'] = $plugin['available'] ? 'Yes' : 'No';
    }

    return new RowsOfFields($plugins);
  }

  /**
   * List entities of a given type.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string|null $bundle
   *   Optional bundle to filter by.
   * @param array $options
   *   Command options.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   Entity list data.
   */
  #[CLI\Command(name: 'ci:list', aliases: ['cil'])]
  #[CLI\Argument(name: 'entity_type', description: 'The entity type ID')]
  #[CLI\Argument(name: 'bundle', description: 'Optional bundle to filter by')]
  #[CLI\Option(name: 'limit', description: 'Maximum entities to return (default: 50)')]
  #[CLI\Option(name: 'offset', description: 'Offset for pagination (default: 0)')]
  #[CLI\FieldLabels(labels: [
    'id' => 'ID',
    'uuid' => 'UUID',
    'label' => 'Label',
    'bundle' => 'Bundle',
    'langcode' => 'Language',
  ])]
  #[CLI\DefaultFields(fields: ['id', 'label', 'bundle'])]
  #[CLI\Usage(name: 'drush ci:list node', description: 'List all nodes')]
  #[CLI\Usage(name: 'drush ci:list node article --limit=10', description: 'List 10 articles')]
  #[CLI\Usage(name: 'drush ci:list taxonomy_term tags --format=json', description: 'List tags as JSON')]
  public function listEntities(
    string $entity_type,
    ?string $bundle = NULL,
    array $options = [
      'limit' => 50,
      'offset' => 0,
    ],
  ): RowsOfFields {
    $entities = $this->collector->listEntities(
      $entity_type,
      $bundle,
      (int) $options['limit'],
      (int) $options['offset']
    );
    return new RowsOfFields($entities);
  }

  /**
   * Get full intelligence data for an entity.
   *
   * Returns entity fields and data from all applicable intel plugins.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $entity_id
   *   The entity ID.
   * @param array $options
   *   Command options.
   *
   * @return array
   *   Complete intel data.
   */
  #[CLI\Command(name: 'ci:entity', aliases: ['cie'])]
  #[CLI\Argument(name: 'entity_type', description: 'The entity type ID')]
  #[CLI\Argument(name: 'entity_id', description: 'The entity ID')]
  #[CLI\Option(name: 'fields', description: 'Comma-separated list of fields to include')]
  #[CLI\Option(name: 'plugins', description: 'Comma-separated list of plugins to use')]
  #[CLI\Option(name: 'format', description: 'Output format: json, yaml (default: yaml)')]
  #[CLI\Usage(name: 'drush ci:entity node 1', description: 'Get intel for node 1')]
  #[CLI\Usage(
    name: 'drush ci:entity node 1 --fields=title,body --format=json',
    description: 'Get specific fields as JSON',
  )]
  #[CLI\Usage(
    name: 'drush ci:entity node 1 --plugins=statistics,content_translation',
    description: 'Use only specific plugins',
  )]
  public function entity(
    string $entity_type,
    string $entity_id,
    array $options = [
      'fields' => '',
      'plugins' => '',
      'format' => 'yaml',
    ],
  ): array {
    $entity = $this->collector->loadEntity($entity_type, $entity_id);

    if (!$entity) {
      throw new \InvalidArgumentException("Entity {$entity_type}/{$entity_id} not found.");
    }

    $fields = $options['fields'] ? array_map('trim', explode(',', $options['fields'])) : [];
    $plugins = $options['plugins'] ? array_map('trim', explode(',', $options['plugins'])) : [];

    return $this->collector->collectIntel($entity, $fields, $plugins);
  }

  /**
   * Get a summary view of an entity (basic info only, no intel).
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $entity_id
   *   The entity ID.
   * @param array $options
   *   Command options.
   *
   * @return array
   *   Entity summary data.
   */
  #[CLI\Command(name: 'ci:summary', aliases: ['cis'])]
  #[CLI\Argument(name: 'entity_type', description: 'The entity type ID')]
  #[CLI\Argument(name: 'entity_id', description: 'The entity ID')]
  #[CLI\Option(name: 'format', description: 'Output format: json, yaml (default: yaml)')]
  #[CLI\Usage(name: 'drush ci:summary node 1', description: 'Get summary for node 1')]
  public function summary(
    string $entity_type,
    string $entity_id,
    array $options = ['format' => 'yaml'],
  ): array {
    $entity = $this->collector->loadEntity($entity_type, $entity_id);

    if (!$entity) {
      throw new \InvalidArgumentException("Entity {$entity_type}/{$entity_id} not found.");
    }

    return $this->collector->getEntitySummary($entity);
  }

  /**
   * Batch collect intel for multiple entities.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param array $options
   *   Command options.
   *
   * @return array
   *   Array of intel data for each entity.
   */
  #[CLI\Command(name: 'ci:batch', aliases: ['cibt'])]
  #[CLI\Argument(name: 'entity_type', description: 'The entity type ID')]
  #[CLI\Option(name: 'bundle', description: 'Filter by bundle')]
  #[CLI\Option(name: 'ids', description: 'Comma-separated list of entity IDs')]
  #[CLI\Option(name: 'limit', description: 'Maximum entities (default: 10)')]
  #[CLI\Option(name: 'plugins', description: 'Comma-separated list of plugins')]
  #[CLI\Option(name: 'format', description: 'Output format: json, yaml (default: json)')]
  #[CLI\Usage(
    name: 'drush ci:batch node --bundle=article --limit=5',
    description: 'Get intel for 5 articles',
  )]
  #[CLI\Usage(name: 'drush ci:batch node --ids=1,2,3', description: 'Get intel for specific nodes')]
  public function batch(
    string $entity_type,
    array $options = [
      'bundle' => '',
      'ids' => '',
      'limit' => 10,
      'plugins' => '',
      'format' => 'json',
    ],
  ): array {
    $plugins = $options['plugins'] ? array_map('trim', explode(',', $options['plugins'])) : [];
    $results = [];

    if ($options['ids']) {
      // Use bulk loading for better performance.
      $ids = array_map('trim', explode(',', $options['ids']));
      $entities = $this->collector->loadEntities($entity_type, $ids);
      foreach ($entities as $entity) {
        $results[] = $this->collector->collectIntel($entity, [], $plugins);
      }
    }
    else {
      $bundle = $options['bundle'] ?: NULL;
      $entity_summaries = $this->collector->listEntities(
        $entity_type,
        $bundle,
        (int) $options['limit']
      );

      // Extract IDs and use bulk loading for better performance.
      $ids = array_column($entity_summaries, 'id');
      $entities = $this->collector->loadEntities($entity_type, $ids);
      foreach ($entities as $entity) {
        $results[] = $this->collector->collectIntel($entity, [], $plugins);
      }
    }

    return $results;
  }

}
