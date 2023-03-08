<?php

namespace Drupal\uniqueness\Plugin\UniquenessSearchMode;

use Drupal\uniqueness\Plugin\UniquenessSearchModeBase;

/**
 * Provides a 'Node Title' search mode.
 *
 * @UniquenessSearchMode(
 *   id = "node_title",
 *   label = @Translation("Node Title")
 * )
 */
class NodeTitle extends UniquenessSearchModeBase {

  /**
   * {@inheritdoc}
   */
  public static function available(): bool {
    $module_handler = \Drupal::service('module_handler');
    return $module_handler->moduleExists('node');
  }

  /**
   * {@inheritdoc}
   */
  public function search(array $values = [], array $search_options = []): array {
    if (empty($values['title'])) {
      return [];
    }

    $config = \Drupal::config('uniqueness.settings');

    // @todo obtain via service.
    $current_user = \Drupal::currentUser();

    $entity_storage = \Drupal::entityTypeManager()->getStorage('node');
    $query = $entity_storage->getQuery();
    $query->condition('title', $values['title'], 'CONTAINS');
    if (!empty($values['bundle'])) {
      $query->condition('bundle', $values['bundle'], 'IN');
    }
    if (!empty($values['entity_id'])) {
      $query->condition('nid', $values['entity_id'], '<>');
    }
    $query->accessCheck($current_user->hasPermission('bypass node access'));
    $query->range(0, $config->get('uniqueness_results_max') + 2);
    $results = $query->execute();
    return $entity_storage->loadMultiple($results);
  }

}
