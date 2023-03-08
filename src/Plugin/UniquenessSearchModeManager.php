<?php

namespace Drupal\uniqueness\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Uniqueness Search Mode plugin manager.
 */
class UniquenessSearchModeManager extends DefaultPluginManager {

  /**
   * Constructor for UniquenessSearchModeManager objects.
   *
   * @codingStandardsIgnoreStart
   *
   * @param \Traversable<mixed> $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   *
   * @codingStandardsIgnoreEnd
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct(
      'Plugin/UniquenessSearchMode',
      $namespaces,
      $module_handler,
      'Drupal\uniqueness\Plugin\UniquenessSearchModeInterface',
      'Drupal\uniqueness\Annotation\UniquenessSearchMode'
    );

    $this->alterInfo('uniqueness_search_mode_info');
    $this->setCacheBackend($cache_backend, 'uniqueness_search_mode_plugins');
  }

}
