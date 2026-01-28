<?php

declare(strict_types=1);

namespace Drupal\content_intel\Service;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for content intelligence collector service.
 *
 * This service provides methods to analyze content entities,
 * retrieve performance data, and generate insights.
 */
interface ContentIntelCollectorInterface {

  /**
   * Gets all available entity types that can be analyzed.
   *
   * @return array
   *   Array of entity type info with id, label, and bundles.
   */
  public function getEntityTypes(): array;

  /**
   * Gets bundles for an entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   Array of bundle info with id and label.
   */
  public function getBundles(string $entity_type_id): array;

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
  public function getFields(string $entity_type_id, ?string $bundle = NULL): array;

  /**
   * Loads a single entity.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param int|string $entity_id
   *   The entity ID.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The loaded entity or NULL if not found.
   */
  public function loadEntity(string $entity_type_id, int|string $entity_id): ?ContentEntityInterface;

  /**
   * Loads multiple entities at once.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param array $entity_ids
   *   Array of entity IDs to load.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Array of loaded entities, keyed by entity ID.
   */
  public function loadEntities(string $entity_type_id, array $entity_ids): array;

  /**
   * Lists entities with optional filtering.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string|null $bundle
   *   The bundle to filter by, or NULL for all.
   * @param int $limit
   *   Maximum number of entities to return.
   * @param int $offset
   *   Number of entities to skip.
   * @param array $conditions
   *   Additional query conditions.
   *
   * @return array
   *   Array of entity summaries.
   */
  public function listEntities(string $entity_type_id, ?string $bundle = NULL, int $limit = 50, int $offset = 0, array $conditions = []): array;

  /**
   * Gets a summary of an entity (basic info only).
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to summarize.
   *
   * @return array
   *   Entity summary data.
   */
  public function getEntitySummary(ContentEntityInterface $entity): array;

  /**
   * Collects full intelligence for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to analyze.
   * @param array $fields
   *   Specific fields to include, or empty for all.
   * @param array $plugins
   *   Specific plugins to use, or empty for all.
   *
   * @return array
   *   Complete entity intelligence data.
   */
  public function collectIntel(ContentEntityInterface $entity, array $fields = [], array $plugins = []): array;

  /**
   * Gets available plugins.
   *
   * @return array
   *   Array of plugin info keyed by plugin ID. Each entry contains:
   *   - id: (string) Plugin ID.
   *   - label: (string) Human-readable label.
   *   - description: (string) Plugin description.
   *   - provider: (string) Module providing the plugin.
   *   - available: (bool) Whether the plugin's dependencies are met.
   *   - entity_types: (array) Entity types this plugin supports.
   *   - weight: (int) Plugin weight for ordering.
   */
  public function getPlugins(): array;

}
