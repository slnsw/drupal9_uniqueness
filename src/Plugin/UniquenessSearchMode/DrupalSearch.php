<?php

namespace Drupal\uniqueness\Plugin\UniquenessSearchMode;

use Drupal\uniqueness\Plugin\UniquenessSearchModeBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Plugin\Search\NodeSearch;

/**
 * Provides a 'Drupal Search' search mode.
 *
 * @UniquenessSearchMode(
 *   id = "drupal_search",
 *   label = @Translation("Drupal Search")
 * )
 */
class DrupalSearch extends UniquenessSearchModeBase {

  /**
   * {@inheritdoc}
   */
  public static function available(): bool {
    $module_handler = \Drupal::service('module_handler');
    return $module_handler->moduleExists('search');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $element, FormStateInterface $form_state, array $original_form): array {
    $options = [];

    foreach (\Drupal::service('plugin.manager.search')->getDefinitions() as $page_id => $page_def) {
      $options[$page_id] = $page_def['title'];
    }

    $plugin_id = $this->settings()->get('uniqueness_search_mode_config.search_page');
    $element['search_page'] = [
      '#type' => 'select',
      '#title' => $this->t('Search page'),
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
      $form_state->getValue(['uniqueness_search_mode_config', 'search_page'])
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
    if (empty($values['title'])) {
      return [];
    }

    $plugin_id = $this->settings()->get('uniqueness_search_mode_config.search_page');
    if (empty($plugin_id)) {
      return [];
    }

    $instance = \Drupal::service('plugin.manager.search')->createInstance($plugin_id, []);
    $instance->setSearch($values['title'], [], []);
    $results = $instance->execute();

    $output = [];
    foreach ($results as $row) {
      if ($instance instanceof NodeSearch) {
        $entity = $row['node'];
        if (!empty($values['entity_id']) && $values['entity_id'] == $entity->id()) {
          continue;
        }
        $output[$entity->id()] = $entity;
      }
      else {
        \Drupal::logger('uniqueness')->warning($this->t('Drupal Search could not obtain results; cannot obtain entity results from search page ID @plugin_id.', [
          '@plugin_id' => $plugin_id,
        ]));
      }
    }

    return array_slice($output, 0, $this->settings()->get('uniqueness_results_max'));
  }

}
