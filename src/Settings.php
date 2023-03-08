<?php

namespace Drupal\uniqueness;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * The uniqueness.settings service.
 */
class Settings {

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
   * Returns a specific setting or a default value if setting does not exist.
   *
   * @param string $name
   *   Name of the setting, like 'max_links'.
   * @param mixed $default
   *   Value to be returned if the setting does not exist in the configuration.
   *
   * @return mixed
   *   The current setting from configuration or a default value.
   */
  public function get(string $name, $default = NULL) {
    $setting = $this->configFactory
      ->get('uniqueness.settings')
      ->get($name);

    return $setting ?? $default;
  }

  /**
   * Returns all settings.
   *
   * @return mixed
   *   Uniqueness settings.
   */
  public function getAll() {
    return $this->configFactory
      ->get('uniqueness.settings')
      ->get();
  }

  /**
   * Stores a specific uniqueness setting in configuration.
   *
   * @param string $name
   *   Setting name, like 'max_links'.
   * @param mixed $setting
   *   The setting to be saved.
   *
   * @return $this
   */
  public function save(string $name, $setting): Settings {
    $this->configFactory
      ->getEditable('uniqueness.settings')
      ->set($name, $setting)
      ->save();

    return $this;
  }

}
