<?php

namespace Drupal\uniqueness\Plugin\UniquenessSearchMode;

use Drupal\uniqueness\Plugin\UniquenessSearchModeBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Drupal Search API' search mode.
 *
 * @UniquenessSearchMode(
 *   id = "drupal_search_api",
 *   label = @Translation("Drupal Search API")
 * )
 */
class DrupalSearchApi extends UniquenessSearchModeBase {

  /**
   * {@inheritdoc}
   */
  public static function available(): bool {
    $module_handler = \Drupal::service('module_handler');
    return $module_handler->moduleExists('search_api');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $element, FormStateInterface $form_state, array $original_form): array {
    $options = [];

    $search_index_storage = \Drupal::service('entity_type.manager')
      ->getStorage('search_api_index');

    foreach ($search_index_storage->getQuery()->execute() as $index_id) {
      $search_index = $search_index_storage->load($index_id);
      $options[$index_id] = $search_index->label();
    }

    $plugin_id = $this->settings()->get('uniqueness_search_mode_config.search_api_index');
    $element['search_api_index'] = [
      '#type' => 'select',
      '#title' => $this->t('Search API index'),
      '#options' => $options,
      '#default_value' => $plugin_id,
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitSettingsForm(array $form, FormStateInterface $form_state): void {
    $settings = $this->editableSettings();
    $settings->set(
      'uniqueness_search_mode_config.search_page',
      NULL
    );
    $settings->set(
      'uniqueness_search_mode_config.search_api_index',
      $form_state->getValue([
        'uniqueness_search_mode_config',
        'search_api_index',
      ])
    );
    $settings->save();
  }

  /**
   * {@inheritdoc}
   */
  public function search(array $values = [], array $search_options = []): array {
    if (empty($values['title'])) {
      return [];
    }

    $search_index_id = $this->settings()->get('uniqueness_search_mode_config.search_api_index');
    if (empty($search_index_id)) {
      return [];
    }

    $search_index_storage = \Drupal::service('entity_type.manager')
      ->getStorage('search_api_index');
    $search_index = $search_index_storage->load($search_index_id);
    if (empty($search_index)) {
      // @todo warning in logs.
      return [];
    }

    $query = $search_index->query();
    $query->keys($values['title']);
    $query->range(0, $this->settings()->get('uniqueness_results_max') + 2);
    $results = $query->execute();

    $output = [];
    $entity_storages = [];
    foreach ($results as $row) {
      $entity = $row->getOriginalObject()->getValue();
      if (!empty($values['entity_id']) && $values['entity_id'] == $entity->id()) {
        continue;
      }
      $output[$entity->id()] = $entity;
    }

    return $output;
  }

}
