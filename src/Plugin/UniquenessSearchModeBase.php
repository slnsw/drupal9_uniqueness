<?php

namespace Drupal\uniqueness\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Base for Uniqueness Search Mode plugins.
 */
abstract class UniquenessSearchModeBase implements UniquenessSearchModeInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function available(): bool {
    return FALSE;
  }

  /**
   * Get read-only settings object.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   Read-only Uniqueness settings config.
   */
  protected function settings(): ImmutableConfig {
    return \Drupal::configFactory()->get('uniqueness.settings');
  }

  /**
   * Get editable settings object.
   *
   * @return \Drupal\Core\Config\Config
   *   Editable Uniqueness settings config.
   */
  protected function editableSettings(): Config {
    return \Drupal::configFactory()->getEditable('uniqueness.settings');
  }

  /**
   * Build settings form for this plugin.
   *
   * Displays beneath the "Search mode" dropdown on the config page.
   *
   * @param mixed[] $element
   *   The subform element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param mixed[] $original_form
   *   The original settings form.
   *
   * @return mixed[]
   *   The modified subform elemnt.
   */
  public function settingsForm(array $element, FormStateInterface $form_state, array $original_form): array {
    return $element;
  }

  /**
   * Handles the Uniqueness config form submit for this plugin.
   *
   * @param mixed[] $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function submitSettingsForm(array $form, FormStateInterface $form_state): void {
    $settings = $this->editableSettings();
    $settings->set(
      'uniqueness_search_mode_config.search_page',
      NULL
    );
    $settings->set(
      'uniqueness_search_mode_config.search_api_index',
      NULL
    );
    $settings->save();
  }

  /**
   * {@inheritdoc}
   */
  public function search(array $values = [], array $search_options = []): array {
    return [];
  }

}
