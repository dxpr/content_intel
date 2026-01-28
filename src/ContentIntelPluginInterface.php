<?php

declare(strict_types=1);

namespace Drupal\content_intel;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for Content Intel plugins.
 *
 * Plugins gather intelligence data from various sources about content entities.
 */
interface ContentIntelPluginInterface extends PluginInspectionInterface {

  /**
   * Gets the plugin label.
   *
   * @return string
   *   The plugin label.
   */
  public function label(): string;

  /**
   * Gets the plugin description.
   *
   * @return string|null
   *   The plugin description, or NULL if not set.
   */
  public function description(): ?string;

  /**
   * Checks if the plugin is available (dependencies met).
   *
   * @return bool
   *   TRUE if the plugin can be used, FALSE otherwise.
   */
  public function isAvailable(): bool;

  /**
   * Checks if the plugin applies to the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if the plugin applies to this entity, FALSE otherwise.
   */
  public function applies(ContentEntityInterface $entity): bool;

  /**
   * Collects intelligence data for the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to analyze.
   *
   * @return array
   *   An associative array of intelligence data. Keys should be machine names,
   *   values can be scalars or arrays with 'value' and 'label' keys.
   */
  public function collect(ContentEntityInterface $entity): array;

  /**
   * Gets the weight of this plugin for ordering.
   *
   * @return int
   *   The plugin weight.
   */
  public function getWeight(): int;

}
