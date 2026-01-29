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
 * Example plugin implementation:
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
 *   public function isAvailable(): bool {
 *     // Check if required dependencies are available.
 *     return \Drupal::moduleHandler()->moduleExists('my_dependency');
 *   }
 *
 *   public function applies(ContentEntityInterface $entity): bool {
 *     // Only apply to published nodes.
 *     if ($entity->getEntityTypeId() === 'node') {
 *       return $entity->isPublished();
 *     }
 *     return TRUE;
 *   }
 *
 *   public function collect(ContentEntityInterface $entity): array {
 *     // Return intelligence data as an associative array.
 *     return [
 *       'custom_metric' => $this->calculateMetric($entity),
 *       'custom_status' => 'active',
 *     ];
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
 * @} End of "addtogroup hooks".
 */
