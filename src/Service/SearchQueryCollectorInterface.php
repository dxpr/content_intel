<?php

declare(strict_types=1);

namespace Drupal\content_intel\Service;

/**
 * Interface for collecting search query intelligence.
 */
interface SearchQueryCollectorInterface {

  /**
   * Gets top search queries.
   *
   * @param int $limit
   *   Maximum number of queries to return.
   *
   * @return array
   *   Array of search query data, each containing:
   *   - query: The search query string
   *   - count: Number of times searched
   *   - results_count: Average/last results count (if available)
   *   - last_searched: Timestamp of last search (if available)
   */
  public function getTopQueries(int $limit = 50): array;

  /**
   * Gets queries with zero or low results (content gaps).
   *
   * @param int $limit
   *   Maximum number of queries to return.
   * @param int $max_results
   *   Maximum result count to consider as "low results".
   *
   * @return array
   *   Array of search query data for queries with few/no results.
   */
  public function getContentGaps(int $limit = 50, int $max_results = 0): array;

  /**
   * Checks if search query logging is available.
   *
   * @return bool
   *   TRUE if a search query data source is available.
   */
  public function isAvailable(): bool;

  /**
   * Gets the source of search query data.
   *
   * @return string
   *   The data source identifier (e.g., 'search_api', 'core_search', 'custom').
   */
  public function getSource(): string;

}
