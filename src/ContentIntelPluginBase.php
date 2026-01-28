<?php

declare(strict_types=1);

namespace Drupal\content_intel;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Content Intel plugins.
 */
abstract class ContentIntelPluginBase extends PluginBase implements ContentIntelPluginInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): static {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function description(): ?string {
    return isset($this->pluginDefinition['description'])
      ? (string) $this->pluginDefinition['description']
      : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(ContentEntityInterface $entity): bool {
    $entity_types = $this->pluginDefinition['entity_types'] ?? [];

    // Empty array means applies to all entity types.
    if (empty($entity_types)) {
      return TRUE;
    }

    return in_array($entity->getEntityTypeId(), $entity_types, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return $this->pluginDefinition['weight'] ?? 0;
  }

}
