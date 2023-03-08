<?php

namespace Drupal\uniqueness\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Sync normalizer decorator item annotation object.
 *
 * @see \Drupal\uniqueness\Plugin\UniquenessSearchModeManager
 * @see plugin_api
 *
 * @Annotation
 */
class UniquenessSearchMode extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
