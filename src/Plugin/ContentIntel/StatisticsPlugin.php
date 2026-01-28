<?php

declare(strict_types=1);

namespace Drupal\content_intel\Plugin\ContentIntel;

use Drupal\content_intel\Attribute\ContentIntel;
use Drupal\content_intel\ContentIntelPluginBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;
use Drupal\statistics\StatisticsStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides view statistics from the Statistics module.
 */
#[ContentIntel(
  id: 'statistics',
  label: new TranslatableMarkup('View Statistics'),
  description: new TranslatableMarkup('Page view counts from the Statistics module.'),
  entity_types: ['node'],
  weight: 10,
)]
class StatisticsPlugin extends ContentIntelPluginBase {

  /**
   * The statistics storage.
   *
   * @var \Drupal\statistics\StatisticsStorageInterface|null
   */
  protected ?StatisticsStorageInterface $statisticsStorage = NULL;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
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

    if ($container->has('statistics.storage.node')) {
      $instance->statisticsStorage = $container->get('statistics.storage.node');
    }
    $instance->dateFormatter = $container->get('date.formatter');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable(): bool {
    return $this->statisticsStorage !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(ContentEntityInterface $entity): bool {
    return $entity instanceof NodeInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function collect(ContentEntityInterface $entity): array {
    if (!$this->statisticsStorage || !$entity instanceof NodeInterface) {
      return [];
    }

    $nid = (int) $entity->id();
    $result = $this->statisticsStorage->fetchView($nid);

    if (!$result) {
      return [
        'total_views' => 0,
        'day_views' => 0,
        'last_view' => NULL,
      ];
    }

    $timestamp = $result->getTimestamp();

    return [
      'total_views' => $result->getTotalCount(),
      'day_views' => $result->getDayCount(),
      'last_view' => $timestamp ? [
        'timestamp' => $timestamp,
        'iso8601' => date('c', $timestamp),
        'human' => $this->dateFormatter->format($timestamp, 'medium'),
      ] : NULL,
    ];
  }

}
