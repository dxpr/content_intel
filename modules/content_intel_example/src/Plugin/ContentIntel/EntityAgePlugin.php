<?php

declare(strict_types=1);

namespace Drupal\content_intel_example\Plugin\ContentIntel;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\content_intel\Attribute\ContentIntel;
use Drupal\content_intel\ContentIntelPluginBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides entity age and freshness metrics.
 *
 * This is an advanced example plugin demonstrating:
 * - Dependency injection via create() method.
 * - Using Drupal services (datetime.time, date.formatter).
 * - Conditional logic based on entity interfaces.
 * - Calculated metrics with multiple data points.
 */
#[ContentIntel(
  id: 'entity_age',
  label: new TranslatableMarkup('Entity Age'),
  description: new TranslatableMarkup('Calculates entity age and freshness metrics.'),
  entity_types: [],
  weight: 110,
)]
final class EntityAgePlugin extends ContentIntelPluginBase {

  /**
   * The time service.
   */
  protected TimeInterface $time;

  /**
   * The date formatter service.
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->time = $container->get('datetime.time');
    $instance->dateFormatter = $container->get('date.formatter');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(ContentEntityInterface $entity): bool {
    // Only apply to entities that track creation time.
    return method_exists($entity, 'getCreatedTime');
  }

  /**
   * {@inheritdoc}
   */
  public function collect(ContentEntityInterface $entity): array {
    $now = $this->time->getRequestTime();
    $data = [];

    // Get creation time if available.
    if (method_exists($entity, 'getCreatedTime')) {
      $created = $entity->getCreatedTime();
      $age_seconds = $now - $created;

      $data['created'] = [
        'timestamp' => $created,
        'iso8601' => date('c', $created),
        'human' => $this->dateFormatter->format($created, 'medium'),
      ];

      $data['age'] = [
        'seconds' => $age_seconds,
        'days' => (int) floor($age_seconds / 86400),
        'human' => $this->dateFormatter->formatInterval($age_seconds, 2),
      ];

      // Calculate freshness category.
      $days_old = $data['age']['days'];
      $data['freshness'] = match (TRUE) {
        $days_old <= 1 => 'new',
        $days_old <= 7 => 'recent',
        $days_old <= 30 => 'current',
        $days_old <= 90 => 'aging',
        $days_old <= 365 => 'old',
        default => 'archival',
      };
    }

    // Get last modified time if entity supports it.
    if ($entity instanceof EntityChangedInterface) {
      $changed = $entity->getChangedTime();
      $since_changed = $now - $changed;

      $data['last_modified'] = [
        'timestamp' => $changed,
        'iso8601' => date('c', $changed),
        'human' => $this->dateFormatter->format($changed, 'medium'),
      ];

      $data['time_since_update'] = [
        'seconds' => $since_changed,
        'days' => (int) floor($since_changed / 86400),
        'human' => $this->dateFormatter->formatInterval($since_changed, 2),
      ];

      // Check if entity was modified after creation.
      if (isset($data['created']['timestamp'])) {
        $data['was_edited'] = $changed > $data['created']['timestamp'];
      }
    }

    return $data;
  }

}
