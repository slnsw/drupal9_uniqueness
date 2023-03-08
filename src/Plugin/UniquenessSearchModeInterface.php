<?php

namespace Drupal\uniqueness\Plugin;

/**
 * Interface for Uniqueness Search Mode plugins.
 */
interface UniquenessSearchModeInterface {

  /**
   * Whether this plugin type is available for use.
   *
   * @return bool
   *   TRUE if this plugin type is available.
   *   FALSE if some requirement for it is not met.
   */
  public static function available(): bool;

  /**
   * Do a title search using this plugin.
   *
   * @param mixed[] $values
   *   Values to search on.
   * @param mixed[] $search_options
   *   Options for the current search.
   *
   * @option nid
   *   The current node ID.
   *
   * @return mixed[]
   *   Return a list of candidate content entities.
   *   Keys are IDs, values are content entities.
   */
  public function search(array $values = [], array $search_options = []): array;

}
