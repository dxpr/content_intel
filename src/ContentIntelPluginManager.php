<?php

declare(strict_types=1);

namespace Drupal\content_intel;

use Drupal\content_intel\Attribute\ContentIntel;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager for Content Intel plugins.
 *
 * @see \Drupal\content_intel\Attribute\ContentIntel
 * @see \Drupal\content_intel\ContentIntelPluginInterface
 * @see \Drupal\content_intel\ContentIntelPluginBase
 * @see plugin_api
 */
class ContentIntelPluginManager extends DefaultPluginManager {

  /**
   * Constructs a ContentIntelPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
  ) {
    parent::__construct(
      'Plugin/ContentIntel',
      $namespaces,
      $module_handler,
      ContentIntelPluginInterface::class,
      ContentIntel::class,
    );

    $this->alterInfo('content_intel_info');
    $this->setCacheBackend($cache_backend, 'content_intel_plugins');
  }

  /**
   * Gets all available plugin instances.
   *
   * @return \Drupal\content_intel\ContentIntelPluginInterface[]
   *   Array of plugin instances keyed by plugin ID.
   */
  public function getAvailablePlugins(): array {
    $plugins = [];
    foreach ($this->getDefinitions() as $id => $definition) {
      $plugin = $this->createInstance($id);
      if ($plugin->isAvailable()) {
        $plugins[$id] = $plugin;
      }
    }

    // Sort by weight.
    uasort($plugins, fn($a, $b) => $a->getWeight() <=> $b->getWeight());

    return $plugins;
  }

  /**
   * Gets plugins applicable to an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   *
   * @return \Drupal\content_intel\ContentIntelPluginInterface[]
   *   Array of applicable plugin instances keyed by plugin ID.
   */
  public function getApplicablePlugins(ContentEntityInterface $entity): array {
    $plugins = [];
    foreach ($this->getAvailablePlugins() as $id => $plugin) {
      if ($plugin->applies($entity)) {
        $plugins[$id] = $plugin;
      }
    }
    return $plugins;
  }

}
