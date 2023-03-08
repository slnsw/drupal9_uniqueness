<?php

namespace Drupal\uniqueness;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Url;
use Drupal\Component\Utility\Xss;

/**
 * Service to show frontend settings for Uniqueness.
 */
class FrontendSettings {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
   * Get read-only settings object.
   *
   * @param string $config_id
   *   ID of the config object.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   Read-only settings config.
   */
  protected function settings(string $config_id): ImmutableConfig {
    return \Drupal::configFactory()->get($config_id);
  }

  /**
   * Determine the minimum word size allowed in a query.
   *
   * @return int
   *   The minimum number of characters required per word.
   */
  protected function minimumWordSize(): int {
    $module_handler = \Drupal::service('module_handler');
    if ($module_handler->moduleExists('search')) {
      $search_config = $this->settings('search.settings');
      return $search_config->get('index.minimum_word_size') ?? 3;
    }
    return 3;
  }

  /**
   * Get the drupalSettings object for Uniqueness in the current context.
   *
   * @param string $entity_type
   *   The current entity type.
   * @param string $bundle
   *   The current bundle.
   * @param mixed|null $entity_id
   *   The current entity ID, if there is one.
   *
   * @return mixed[]
   *   The drupalSettings.uniqueness object.
   */
  public function getSettings(string $entity_type, string $bundle, $entity_id = NULL): array {
    $config = $this->settings('uniqueness.settings');

    $min_characters = $config->get('uniqueness_query_min');
    if (
      $config->get('uniqueness_search_mode') === 'drupal_search'
      && $min_characters < $this->minimumWordSize()
    ) {
      $min_characters = $this->minimumWordSize();
    }

    $settings = [
      'URL' => Url::fromRoute('uniqueness.search', [
        'entity_type' => $entity_type,
        'bundle' => $bundle,
      ])->toString(),
      'prependResults' => $config->get('uniqueness_results_prepend'),
      'minCharacters' => $min_characters,
      'searchingString' => Xss::filterAdmin($config->get('uniqueness_searching_string')),
      'noResultsString' => Xss::filterAdmin($config->get('uniqueness_no_result_string')),
    ];
    if ($config->get('uniqueness_scope') == 'content_type') {
      $settings['entityType'] = $entity_type;
      $settings['bundle'] = $bundle;
    }
    if (!is_null($entity_id)) {
      $settings['entityId'] = $entity_id;
    }
    return $settings;
  }

}
