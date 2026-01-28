<?php

declare(strict_types=1);

namespace Drupal\content_intel\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a Content Intel plugin attribute.
 *
 * Plugin Namespace: Plugin\ContentIntel.
 *
 * @see \Drupal\content_intel\ContentIntelPluginInterface
 * @see \Drupal\content_intel\ContentIntelPluginBase
 * @see \Drupal\content_intel\ContentIntelPluginManager
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ContentIntel extends Plugin {

  /**
   * The plugin label.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public readonly TranslatableMarkup $label;

  /**
   * The plugin description.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup|null
   */
  public readonly ?TranslatableMarkup $description;

  /**
   * Entity types this plugin applies to.
   *
   * @var array
   */
  public readonly array $entity_types;

  /**
   * Plugin weight for ordering.
   *
   * @var int
   */
  public readonly int $weight;

  /**
   * Constructs a ContentIntel attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The human-readable name of the plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   A brief description of the plugin.
   * @param array $entity_types
   *   Entity types this plugin applies to. Empty array means all types.
   * @param int $weight
   *   Plugin weight for ordering. Lower weights appear first.
   * @param class-string|null $deriver
   *   The deriver class for this plugin.
   */
  public function __construct(
    string $id,
    TranslatableMarkup $label,
    ?TranslatableMarkup $description = NULL,
    array $entity_types = [],
    int $weight = 0,
    ?string $deriver = NULL,
  ) {
    parent::__construct($id, $deriver);
    $this->label = $label;
    $this->description = $description;
    $this->entity_types = $entity_types;
    $this->weight = $weight;
  }

}
