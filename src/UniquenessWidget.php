<?php

namespace Drupal\uniqueness;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Link;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Utility\Xss;

/**
 * Displays the Uniqueness widgets and handles searches for it.
 */
class UniquenessWidget {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Whether the last search query has more results.
   *
   * @var bool
   */
  protected $lastSearchHasMore;

  /**
   * Settings constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Get configuration for the Uniqueness module.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The Uniqueness settings config.
   */
  protected function config(): ImmutableConfig {
    return $this->configFactory->get('uniqueness.settings');
  }

  /**
   * Build rendered content for the uniqueness widget.
   *
   * @param mixed[] $values
   *   Values that might contain keys 'tags', 'title', 'entity_id',
   *   'entity_type', and 'bundle'.
   * @param int $count
   *   Reference to the current result count.
   *
   * @return string
   *   Rendered widget.
   */
  public function content(array $values, &$count) {
    $results = [];
    $entity_ids = [];
    $description = Xss::filterAdmin($this->config()->get('uniqueness_default_description'));

    if (!empty($values)) {
      $content = $this->doSearch($values, TRUE);
      foreach ($content as $entity_id => $result_value) {
        // Avoid duplicates.
        if (!in_array($entity_id, array_keys($entity_ids))) {
          $entity_ids[$entity_id] = $entity_id;
          $results[] = $result_value['link'];
        }
      }
    }
    $count = count($entity_ids);

    // Pass the description and any initial results through the theme system.
    $build = [
      '#theme' => 'uniqueness_widget',
      '#description' => $description,
      '#results' => $results,
    ];
    return \Drupal::service('renderer')->render($build);
  }

  /**
   * Perform the search.
   *
   * @param mixed[] $values
   *   Values that might contain keys 'tags', 'title', 'entity_id',
   *   'entity_type', and 'bundle'.
   * @param bool $limit_results
   *   Whether to limit the results output.
   *
   * @return mixed[]
   *   An array of formatted results. Each result contains the keys:
   *     - 'entity': (EntityInterface) The original entity object.
   *     - 'id': The entity ID.
   *     - 'url': (Url) The entity URL.
   *     - 'label': (string/Translatable) The entity label/title.
   *     - 'link': (Link) Link to the entity.
   *     - 'status': (int) Whether the entity is published.
   */
  public function doSearch(array $values = [], bool $limit_results = FALSE) {
    $search_options = [];

    $search_mode = $this->config()->get('uniqueness_search_mode');
    $plugin_manager = \Drupal::service('plugin.manager.uniqueness_search_mode');

    $plugin_instance = NULL;
    try {
      $plugin_instance = $plugin_manager->createInstance($search_mode, []);
    }
    catch (PluginException $e) {
      // @TODO warning in log
      return [];
    }

    $results = [];
    $entity_ids = [];
    $raw_results = $plugin_instance->search($values, $search_options);

    $i = 0;
    $limit = $this->config()->get('uniqueness_results_max');
    $this->lastSearchHasMore = FALSE;
    foreach ($raw_results as $entity_id => $entity) {
      // Avoid duplicates.
      if (!in_array($entity_id, array_keys($entity_ids))) {
        $entity_ids[$entity_id] = $entity_id;
        $url = $entity->toUrl('canonical', [
          'attributes' => [
            'target' => '_blank',
          ],
        ]);
        $results[] = [
          'entity' => $entity,
          'id' => $entity->id(),
          'url' => $url,
          'label' => $entity->label(),
          'link' => Link::fromTextAndUrl($entity->label(), $url),
          'status' => intval($entity->status->getString()),
        ];
      }
      if (!$this->lastSearchHasMore && ++$i > $limit) {
        $this->lastSearchHasMore = TRUE;
      }
    }

    if ($limit_results) {
      return array_slice($results, 0, $limit);
    }
    return $results;
  }

  /**
   * Find out if the last search had more results.
   */
  public function getLastSearchHasMore(): bool {
    return $this->lastSearchHasMore;
  }

}
