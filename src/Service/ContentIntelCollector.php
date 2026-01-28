<?php

declare(strict_types=1);

namespace Drupal\content_intel\Service;

use Drupal\content_intel\ContentIntelPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Service for collecting content intelligence from various sources.
 */
class ContentIntelCollector {

  /**
   * Constructs a ContentIntelCollector.
   *
   * @param \Drupal\content_intel\ContentIntelPluginManager $pluginManager
   *   The content intel plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(
    protected ContentIntelPluginManager $pluginManager,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected ConfigFactoryInterface $configFactory,
    protected RendererInterface $renderer,
  ) {}

  /**
   * Gets all available entity types that can be analyzed.
   *
   * @return array
   *   Array of entity type info with id, label, and bundles.
   */
  public function getEntityTypes(): array {
    $types = [];
    foreach ($this->entityTypeManager->getDefinitions() as $id => $definition) {
      if ($definition->entityClassImplements(ContentEntityInterface::class)) {
        $types[$id] = [
          'id' => $id,
          'label' => (string) $definition->getLabel(),
          'bundle_entity_type' => $definition->getBundleEntityType(),
        ];
      }
    }
    ksort($types);
    return $types;
  }

  /**
   * Gets bundles for an entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   Array of bundle info with id and label.
   */
  public function getBundles(string $entity_type_id): array {
    $bundles = [];
    $bundle_info = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type_id);

    foreach ($bundle_info as $bundle_id => $info) {
      $bundles[$bundle_id] = [
        'id' => $bundle_id,
        'label' => $info['label'],
      ];
    }
    ksort($bundles);
    return $bundles;
  }

  /**
   * Gets field definitions for an entity type/bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string|null $bundle
   *   The bundle, or NULL for base fields only.
   *
   * @return array
   *   Array of field info.
   */
  public function getFields(string $entity_type_id, ?string $bundle = NULL): array {
    $fields = [];

    if ($bundle) {
      $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    }
    else {
      $field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
    }

    foreach ($field_definitions as $field_name => $definition) {
      $fields[$field_name] = [
        'name' => $field_name,
        'label' => (string) $definition->getLabel(),
        'type' => $definition->getType(),
        'required' => $definition->isRequired(),
        'cardinality' => $definition->getFieldStorageDefinition()->getCardinality(),
      ];
    }
    ksort($fields);
    return $fields;
  }

  /**
   * Loads an entity by type and ID.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param int|string $entity_id
   *   The entity ID.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The entity, or NULL if not found.
   */
  public function loadEntity(string $entity_type_id, int|string $entity_id): ?ContentEntityInterface {
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
    return $entity instanceof ContentEntityInterface ? $entity : NULL;
  }

  /**
   * Lists entities matching criteria.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string|null $bundle
   *   Optional bundle to filter by.
   * @param int $limit
   *   Maximum number of entities to return.
   * @param int $offset
   *   Offset for pagination.
   * @param array $conditions
   *   Additional query conditions.
   *
   * @return array
   *   Array of entity summary data.
   */
  public function listEntities(
    string $entity_type_id,
    ?string $bundle = NULL,
    int $limit = 50,
    int $offset = 0,
    array $conditions = [],
  ): array {
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->range($offset, $limit);

    if ($bundle && $bundle_key = $entity_type->getKey('bundle')) {
      $query->condition($bundle_key, $bundle);
    }

    foreach ($conditions as $field => $value) {
      $query->condition($field, $value);
    }

    // Sort by ID descending (newest first).
    if ($id_key = $entity_type->getKey('id')) {
      $query->sort($id_key, 'DESC');
    }

    $ids = $query->execute();
    $entities = $storage->loadMultiple($ids);

    $results = [];
    foreach ($entities as $entity) {
      $results[] = $this->getEntitySummary($entity);
    }

    return $results;
  }

  /**
   * Gets a summary of an entity (basic info without full intel).
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return array
   *   Summary data.
   */
  public function getEntitySummary(ContentEntityInterface $entity): array {
    $entity_type = $entity->getEntityType();

    $summary = [
      'entity_type' => $entity->getEntityTypeId(),
      'id' => $entity->id(),
      'uuid' => $entity->uuid(),
      'label' => $entity->label(),
    ];

    if ($entity_type->hasKey('bundle')) {
      $summary['bundle'] = $entity->bundle();
    }

    if ($entity_type->hasKey('langcode') && method_exists($entity, 'language')) {
      $summary['langcode'] = $entity->language()->getId();
    }

    return $summary;
  }

  /**
   * Collects all intelligence data for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to analyze.
   * @param array $fields
   *   Specific fields to include, or empty for all.
   * @param array $plugins
   *   Specific plugins to use, or empty for all enabled.
   *
   * @return array
   *   Complete intelligence data.
   */
  public function collectIntel(
    ContentEntityInterface $entity,
    array $fields = [],
    array $plugins = [],
  ): array {
    $config = $this->configFactory->get('content_intel.settings');
    $enabled_plugins = $config->get('enabled_plugins') ?? [];

    $data = [
      'entity' => $this->getEntitySummary($entity),
      'fields' => $this->extractFieldData($entity, $fields),
      'intel' => [],
    ];

    // Collect data from all applicable plugins.
    $applicable_plugins = $this->pluginManager->getApplicablePlugins($entity);

    foreach ($applicable_plugins as $plugin_id => $plugin) {
      // Skip if specific plugins requested and this isn't one of them.
      if (!empty($plugins) && !in_array($plugin_id, $plugins, TRUE)) {
        continue;
      }

      // Skip if plugin is disabled in config (unless specific plugins requested).
      if (empty($plugins) && !empty($enabled_plugins) && !in_array($plugin_id, $enabled_plugins, TRUE)) {
        continue;
      }

      try {
        $plugin_data = $plugin->collect($entity);
        if (!empty($plugin_data)) {
          $data['intel'][$plugin_id] = [
            'plugin' => $plugin->label(),
            'data' => $plugin_data,
          ];
        }
      }
      catch (\Exception $e) {
        $data['intel'][$plugin_id] = [
          'plugin' => $plugin->label(),
          'error' => $e->getMessage(),
        ];
      }
    }

    return $data;
  }

  /**
   * Extracts field data from an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param array $field_names
   *   Specific field names to include, or empty for all.
   *
   * @return array
   *   Field data.
   */
  protected function extractFieldData(ContentEntityInterface $entity, array $field_names = []): array {
    $data = [];

    foreach ($entity->getFields() as $field_name => $field) {
      // Skip if specific fields requested and this isn't one of them.
      if (!empty($field_names) && !in_array($field_name, $field_names, TRUE)) {
        continue;
      }

      // Skip computed fields and internal fields.
      if ($field->getFieldDefinition()->isComputed()) {
        continue;
      }

      // Skip empty fields.
      if ($field->isEmpty()) {
        continue;
      }

      $data[$field_name] = $this->extractFieldValue($field);
    }

    return $data;
  }

  /**
   * Extracts value from a field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field.
   *
   * @return mixed
   *   The extracted value.
   */
  protected function extractFieldValue(FieldItemListInterface $field): mixed {
    $definition = $field->getFieldDefinition();
    $type = $definition->getType();
    $cardinality = $definition->getFieldStorageDefinition()->getCardinality();

    $values = [];
    foreach ($field as $item) {
      $value = $this->extractItemValue($item, $type);
      if ($value !== NULL) {
        $values[] = $value;
      }
    }

    // Return single value for cardinality 1 fields.
    if ($cardinality === 1 && count($values) === 1) {
      return $values[0];
    }

    return $values;
  }

  /**
   * Extracts value from a field item.
   *
   * @param mixed $item
   *   The field item.
   * @param string $type
   *   The field type.
   *
   * @return mixed
   *   The extracted value.
   */
  protected function extractItemValue(mixed $item, string $type): mixed {
    // Handle entity references specially.
    if (in_array($type, ['entity_reference', 'entity_reference_revisions'])) {
      $entity = $item->entity;
      if ($entity) {
        return [
          'target_id' => $item->target_id,
          'target_type' => $entity->getEntityTypeId(),
          'label' => $entity->label(),
        ];
      }
      return ['target_id' => $item->target_id];
    }

    // Handle file/image references.
    if (in_array($type, ['file', 'image'])) {
      $file = $item->entity;
      $value = [
        'target_id' => $item->target_id,
      ];
      if ($file) {
        $value['filename'] = $file->getFilename();
        $value['uri'] = $file->getFileUri();
        $value['url'] = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
        $value['mime'] = $file->getMimeType();
        $value['size'] = $file->getSize();
      }
      if ($type === 'image') {
        $value['alt'] = $item->alt ?? '';
        $value['title'] = $item->title ?? '';
        $value['width'] = $item->width ?? NULL;
        $value['height'] = $item->height ?? NULL;
      }
      return $value;
    }

    // Handle text fields (return both raw and processed).
    if (in_array($type, ['text', 'text_long', 'text_with_summary'])) {
      $value = [
        'value' => $item->value,
        'format' => $item->format ?? NULL,
      ];
      if (isset($item->processed)) {
        $value['processed'] = $item->processed;
      }
      if ($type === 'text_with_summary' && !empty($item->summary)) {
        $value['summary'] = $item->summary;
      }
      return $value;
    }

    // Handle link fields.
    if ($type === 'link') {
      return [
        'uri' => $item->uri,
        'title' => $item->title ?? '',
        'options' => $item->options ?? [],
      ];
    }

    // Handle datetime fields.
    if (in_array($type, ['datetime', 'timestamp', 'created', 'changed'])) {
      $value = $item->value;
      if (is_numeric($value)) {
        return [
          'timestamp' => (int) $value,
          'iso8601' => date('c', (int) $value),
        ];
      }
      return $value;
    }

    // Handle boolean.
    if ($type === 'boolean') {
      return (bool) $item->value;
    }

    // Default: return the main value property.
    $main_property = $item->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getMainPropertyName();

    if ($main_property && isset($item->{$main_property})) {
      return $item->{$main_property};
    }

    // Fallback to getValue().
    return $item->getValue();
  }

  /**
   * Gets available plugins info.
   *
   * @return array
   *   Array of plugin info.
   */
  public function getPlugins(): array {
    $plugins = [];
    foreach ($this->pluginManager->getDefinitions() as $id => $definition) {
      $plugin = $this->pluginManager->createInstance($id);
      $plugins[$id] = [
        'id' => $id,
        'label' => $plugin->label(),
        'description' => $plugin->description(),
        'provider' => $definition['provider'] ?? 'unknown',
        'available' => $plugin->isAvailable(),
        'entity_types' => $definition['entity_types'] ?? [],
        'weight' => $plugin->getWeight(),
      ];
    }
    return $plugins;
  }

}
