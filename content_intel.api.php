<?php

/**
 * @file
 * Hooks provided by the Content Intelligence module.
 */

/**
 * @defgroup content_intel_plugins Content Intelligence Plugins
 * @{
 * Creating plugins for the Content Intelligence module.
 *
 * The Content Intelligence module uses a plugin-based architecture for
 * collecting intelligence data about content entities. Other modules can
 * extend the available intelligence by creating their own plugins.
 *
 * Plugins are discovered using PHP 8 attributes. To create a plugin:
 *
 * 1. Create a class in your module's src/Plugin/ContentIntel/ directory
 * 2. Extend \Drupal\content_intel\ContentIntelPluginBase
 * 3. Add the #[ContentIntel] attribute with required parameters
 * 4. Implement the required methods
 *
 * Basic plugin example:
 * @code
 * namespace Drupal\my_module\Plugin\ContentIntel;
 *
 * use Drupal\content_intel\Attribute\ContentIntel;
 * use Drupal\content_intel\ContentIntelPluginBase;
 * use Drupal\Core\Entity\ContentEntityInterface;
 * use Drupal\Core\StringTranslation\TranslatableMarkup;
 *
 * #[ContentIntel(
 *   id: 'my_custom_intel',
 *   label: new TranslatableMarkup('My Custom Intel'),
 *   description: new TranslatableMarkup('Collects custom intelligence data.'),
 *   entity_types: ['node', 'taxonomy_term'],
 *   weight: 50,
 * )]
 * class MyCustomIntelPlugin extends ContentIntelPluginBase {
 *
 *   public function collect(ContentEntityInterface $entity): array {
 *     return [
 *       'custom_metric' => 123,
 *       'custom_status' => 'active',
 *     ];
 *   }
 *
 * }
 * @endcode
 *
 * Plugin with dependency injection example:
 * @code
 * namespace Drupal\my_module\Plugin\ContentIntel;
 *
 * use Drupal\content_intel\Attribute\ContentIntel;
 * use Drupal\content_intel\ContentIntelPluginBase;
 * use Drupal\Core\Database\Connection;
 * use Drupal\Core\Entity\ContentEntityInterface;
 * use Drupal\Core\StringTranslation\TranslatableMarkup;
 * use Drupal\node\NodeInterface;
 * use Symfony\Component\DependencyInjection\ContainerInterface;
 *
 * #[ContentIntel(
 *   id: 'my_database_intel',
 *   label: new TranslatableMarkup('Database Intel'),
 *   description: new TranslatableMarkup('Collects data from custom database tables.'),
 *   entity_types: ['node'],
 *   weight: 60,
 * )]
 * class MyDatabaseIntelPlugin extends ContentIntelPluginBase {
 *
 *   protected Connection $database;
 *
 *   public static function create(
 *     ContainerInterface $container,
 *     array $configuration,
 *     $plugin_id,
 *     $plugin_definition,
 *   ): static {
 *     $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
 *     $instance->database = $container->get('database');
 *     return $instance;
 *   }
 *
 *   public function isAvailable(): bool {
 *     // Check if required table exists.
 *     return $this->database->schema()->tableExists('my_custom_table');
 *   }
 *
 *   public function applies(ContentEntityInterface $entity): bool {
 *     // Only apply to published nodes.
 *     return $entity instanceof NodeInterface && $entity->isPublished();
 *   }
 *
 *   public function collect(ContentEntityInterface $entity): array {
 *     $result = $this->database->select('my_custom_table', 't')
 *       ->fields('t', ['score', 'category'])
 *       ->condition('entity_id', $entity->id())
 *       ->execute()
 *       ->fetchAssoc();
 *
 *     return $result ?: [];
 *   }
 *
 * }
 * @endcode
 *
 * Available plugin attribute parameters:
 * - id: (required) Unique plugin identifier.
 * - label: (required) Human-readable plugin name (TranslatableMarkup).
 * - description: (optional) Plugin description (TranslatableMarkup).
 * - entity_types: (optional) Array of entity type IDs this plugin applies to.
 *   If empty, the plugin applies to all entity types.
 * - weight: (optional) Integer weight for ordering plugins. Lower weights
 *   execute first. Default is 0.
 * - deriver: (optional) Deriver class for dynamic plugin generation.
 *
 * Plugin methods:
 * - isAvailable(): Returns TRUE if the plugin's dependencies are met.
 * - applies(ContentEntityInterface $entity): Returns TRUE if the plugin
 *   should collect data for the given entity.
 * - collect(ContentEntityInterface $entity): Returns an associative array
 *   of intelligence data for the entity.
 * - label(): Returns the plugin label.
 * - description(): Returns the plugin description.
 * - getWeight(): Returns the plugin weight.
 *
 * @see \Drupal\content_intel\Attribute\ContentIntel
 * @see \Drupal\content_intel\ContentIntelPluginInterface
 * @see \Drupal\content_intel\ContentIntelPluginBase
 * @see \Drupal\content_intel\ContentIntelPluginManager
 * @see content_intel_example
 * @}
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter Content Intelligence plugin definitions.
 *
 * This hook is invoked during plugin discovery to allow modules to modify
 * plugin definitions before they are cached. Use this hook to change plugin
 * properties such as labels, descriptions, entity type restrictions, or
 * weights.
 *
 * @param array $definitions
 *   An array of plugin definitions, keyed by plugin ID. Each definition
 *   contains the following keys:
 *   - id: The plugin ID.
 *   - label: The plugin label (TranslatableMarkup).
 *   - description: The plugin description (TranslatableMarkup or NULL).
 *   - entity_types: Array of entity type IDs the plugin applies to.
 *   - weight: Integer weight for ordering.
 *   - class: The fully qualified plugin class name.
 *   - provider: The module providing the plugin.
 *
 * @see \Drupal\content_intel\ContentIntelPluginManager
 */
function hook_content_intel_info_alter(array &$definitions) {
  // Change the label of an existing plugin.
  if (isset($definitions['statistics'])) {
    $definitions['statistics']['label'] = t('Page View Statistics');
  }

  // Restrict a plugin to specific entity types.
  if (isset($definitions['my_plugin'])) {
    $definitions['my_plugin']['entity_types'] = ['node', 'media'];
  }

  // Adjust plugin weight to change execution order.
  if (isset($definitions['content_translation'])) {
    $definitions['content_translation']['weight'] = 100;
  }

  // Remove a plugin entirely.
  unset($definitions['unwanted_plugin']);
}

/**
 * Alter collected intelligence data for an entity.
 *
 * This hook is invoked after all plugins have collected their data, allowing
 * modules to modify or extend the final intelligence result. Use this hook to:
 * - Add computed or derived metrics based on combined plugin data
 * - Modify or remove data from specific plugins
 * - Add custom data that doesn't warrant a full plugin.
 *
 * @param array $data
 *   The collected intelligence data array with the following structure:
 *   - entity: Entity summary (entity_type, id, uuid, label, bundle, langcode).
 *   - fields: Extracted field data keyed by field name.
 *   - intel: Plugin data keyed by plugin ID, each containing:
 *     - plugin: The plugin label.
 *     - data: The collected data array.
 *     - error: (optional) Error message if collection failed.
 * @param \Drupal\Core\Entity\ContentEntityInterface $entity
 *   The entity that was analyzed.
 *
 * @see \Drupal\content_intel\Service\ContentIntelCollector::collectIntel()
 */
function hook_content_intel_collect_alter(array &$data, \Drupal\Core\Entity\ContentEntityInterface $entity) {
  // Add a computed score based on statistics data.
  if (isset($data['intel']['statistics']['data']['total_views'])) {
    $views = $data['intel']['statistics']['data']['total_views'];
    $data['intel']['computed']['data']['popularity'] = match (TRUE) {
      $views > 1000 => 'viral',
      $views > 100 => 'popular',
      $views > 10 => 'moderate',
      default => 'low',
    };
  }

  // Add custom metadata.
  $data['intel']['custom']['data']['analyzed_at'] = date('c');

  // Remove sensitive data from a specific plugin.
  if (isset($data['intel']['my_plugin']['data']['internal_id'])) {
    unset($data['intel']['my_plugin']['data']['internal_id']);
  }
}

/**
 * @} End of "addtogroup hooks".
 */
