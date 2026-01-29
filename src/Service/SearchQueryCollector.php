<?php

declare(strict_types=1);

namespace Drupal\content_intel\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Collects search query intelligence from available sources.
 *
 * Supports multiple data sources:
 * - content_intel_search_log: Custom logging table (if enabled)
 * - search_api: Search API statistics (if available)
 */
class SearchQueryCollector implements SearchQueryCollectorInterface {

  /**
   * The detected data source.
   *
   * @var string|null
   */
  protected ?string $source = NULL;

  /**
   * Constructs a SearchQueryCollector.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter.
   */
  public function __construct(
    protected Connection $database,
    protected ModuleHandlerInterface $moduleHandler,
    protected DateFormatterInterface $dateFormatter,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function isAvailable(): bool {
    return $this->getSource() !== 'none';
  }

  /**
   * {@inheritdoc}
   */
  public function getSource(): string {
    if ($this->source !== NULL) {
      return $this->source;
    }

    // Check for our custom logging table.
    if ($this->database->schema()->tableExists('content_intel_search_log')) {
      $this->source = 'content_intel';
      return $this->source;
    }

    // Check for Search API Saved Searches or similar.
    if ($this->moduleHandler->moduleExists('search_api')
      && $this->database->schema()->tableExists('search_api_log')) {
      $this->source = 'search_api_log';
      return $this->source;
    }

    $this->source = 'none';
    return $this->source;
  }

  /**
   * {@inheritdoc}
   */
  public function getTopQueries(int $limit = 50): array {
    $source = $this->getSource();

    if ($source === 'content_intel') {
      return $this->getTopQueriesFromContentIntel($limit);
    }

    if ($source === 'search_api_log') {
      return $this->getTopQueriesFromSearchApiLog($limit);
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getContentGaps(int $limit = 50, int $max_results = 0): array {
    $source = $this->getSource();

    if ($source === 'content_intel') {
      return $this->getContentGapsFromContentIntel($limit, $max_results);
    }

    if ($source === 'search_api_log') {
      return $this->getContentGapsFromSearchApiLog($limit, $max_results);
    }

    return [];
  }

  /**
   * Gets top queries from content_intel_search_log table.
   *
   * @param int $limit
   *   Maximum queries to return.
   *
   * @return array
   *   Query data.
   */
  protected function getTopQueriesFromContentIntel(int $limit): array {
    $query = $this->database->select('content_intel_search_log', 'l')
      ->fields('l', ['keywords'])
      ->groupBy('l.keywords');

    $query->addExpression('COUNT(*)', 'count');
    $query->addExpression('AVG(l.results_count)', 'avg_results');
    $query->addExpression('MAX(l.timestamp)', 'last_searched');

    $query->orderBy('count', 'DESC')
      ->range(0, $limit);

    $results = $query->execute()->fetchAll();

    return array_map(function ($row) {
      return [
        'query' => $row->keywords,
        'count' => (int) $row->count,
        'results_count' => $row->avg_results !== NULL ? (int) round($row->avg_results) : NULL,
        'last_searched' => $row->last_searched ? [
          'timestamp' => (int) $row->last_searched,
          'iso8601' => date('c', (int) $row->last_searched),
          'human' => $this->dateFormatter->format((int) $row->last_searched, 'medium'),
        ] : NULL,
      ];
    }, $results);
  }

  /**
   * Gets content gaps from content_intel_search_log table.
   *
   * @param int $limit
   *   Maximum queries to return.
   * @param int $max_results
   *   Maximum result count threshold.
   *
   * @return array
   *   Query data for low/no result searches.
   */
  protected function getContentGapsFromContentIntel(int $limit, int $max_results): array {
    $query = $this->database->select('content_intel_search_log', 'l')
      ->fields('l', ['keywords'])
      ->groupBy('l.keywords');

    $query->addExpression('COUNT(*)', 'count');
    $query->addExpression('AVG(l.results_count)', 'avg_results');
    $query->addExpression('MAX(l.timestamp)', 'last_searched');

    $query->having('AVG(l.results_count) <= :max', [':max' => $max_results]);
    $query->orderBy('count', 'DESC')
      ->range(0, $limit);

    $results = $query->execute()->fetchAll();

    return array_map(function ($row) {
      return [
        'query' => $row->keywords,
        'count' => (int) $row->count,
        'results_count' => (int) round($row->avg_results),
        'last_searched' => $row->last_searched ? [
          'timestamp' => (int) $row->last_searched,
          'iso8601' => date('c', (int) $row->last_searched),
          'human' => $this->dateFormatter->format((int) $row->last_searched, 'medium'),
        ] : NULL,
        'is_content_gap' => TRUE,
      ];
    }, $results);
  }

  /**
   * Gets top queries from search_api_log table.
   *
   * @param int $limit
   *   Maximum queries to return.
   *
   * @return array
   *   Query data.
   */
  protected function getTopQueriesFromSearchApiLog(int $limit): array {
    // Search API Log module schema may vary.
    // This is a common implementation pattern.
    if (!$this->database->schema()->fieldExists('search_api_log', 'keywords')) {
      return [];
    }

    $query = $this->database->select('search_api_log', 'l')
      ->fields('l', ['keywords'])
      ->groupBy('l.keywords');

    $query->addExpression('COUNT(*)', 'count');
    $query->addExpression('MAX(l.timestamp)', 'last_searched');

    $query->orderBy('count', 'DESC')
      ->range(0, $limit);

    $results = $query->execute()->fetchAll();

    return array_map(function ($row) {
      return [
        'query' => $row->keywords,
        'count' => (int) $row->count,
        'results_count' => NULL,
        'last_searched' => $row->last_searched ? [
          'timestamp' => (int) $row->last_searched,
          'iso8601' => date('c', (int) $row->last_searched),
          'human' => $this->dateFormatter->format((int) $row->last_searched, 'medium'),
        ] : NULL,
      ];
    }, $results);
  }

  /**
   * Gets content gaps from search_api_log table.
   *
   * @param int $limit
   *   Maximum queries to return.
   * @param int $max_results
   *   Maximum result count threshold.
   *
   * @return array
   *   Query data.
   */
  protected function getContentGapsFromSearchApiLog(int $limit, int $max_results): array {
    // Search API Log may not track result counts.
    // Return empty if the field doesn't exist.
    if (!$this->database->schema()->fieldExists('search_api_log', 'num_results')) {
      return [];
    }

    $query = $this->database->select('search_api_log', 'l')
      ->fields('l', ['keywords'])
      ->groupBy('l.keywords');

    $query->addExpression('COUNT(*)', 'count');
    $query->addExpression('AVG(l.num_results)', 'avg_results');
    $query->addExpression('MAX(l.timestamp)', 'last_searched');

    $query->having('AVG(l.num_results) <= :max', [':max' => $max_results]);
    $query->orderBy('count', 'DESC')
      ->range(0, $limit);

    $results = $query->execute()->fetchAll();

    return array_map(function ($row) {
      return [
        'query' => $row->keywords,
        'count' => (int) $row->count,
        'results_count' => (int) round($row->avg_results),
        'last_searched' => $row->last_searched ? [
          'timestamp' => (int) $row->last_searched,
          'iso8601' => date('c', (int) $row->last_searched),
          'human' => $this->dateFormatter->format((int) $row->last_searched, 'medium'),
        ] : NULL,
        'is_content_gap' => TRUE,
      ];
    }, $results);
  }

  /**
   * Logs a search query (for sites using content_intel logging).
   *
   * @param string $keywords
   *   The search keywords.
   * @param int $results_count
   *   The number of results returned.
   * @param string|null $index_id
   *   Optional search index identifier.
   */
  public function logQuery(string $keywords, int $results_count, ?string $index_id = NULL): void {
    if (!$this->database->schema()->tableExists('content_intel_search_log')) {
      return;
    }

    $keywords = trim($keywords);
    if (empty($keywords)) {
      return;
    }

    $this->database->insert('content_intel_search_log')
      ->fields([
        'keywords' => mb_substr($keywords, 0, 255),
        'results_count' => $results_count,
        'index_id' => $index_id,
        'timestamp' => \Drupal::time()->getRequestTime(),
      ])
      ->execute();
  }

}
