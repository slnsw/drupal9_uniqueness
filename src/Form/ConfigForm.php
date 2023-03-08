<?php

namespace Drupal\uniqueness\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Configuration form for Uniqueness module.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'uniqueness_settings';
  }

  /**
   * {@inheritdoc}
   *
   * @return string[]
   *   List of editable configurations.
   */
  protected function getEditableConfigNames(): array {
    return ['uniqueness.settings'];
  }

  /**
   * Get a list of available search mode options.
   *
   * @return mixed[]
   *   All currently available search mode options.
   */
  protected function searchModeOptions(): array {
    $options = [];
    $defintions = \Drupal::service('plugin.manager.uniqueness_search_mode')->getDefinitions();
    foreach ($defintions as $definition_id => $defintion) {
      if ($defintion['class']::available()) {
        $options[$definition_id] = $defintion['label'];
      }
    }
    return $options;
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
      $search_config = $this->config('search.settings');
      return $search_config->get('index.minimum_word_size') ?? 3;
    }
    return 3;
  }

  /**
   * Display alert if no content types have been enabled for uniqueness checks.
   */
  protected function statusCheck(): void {
    $config_factory = \Drupal::service('config.factory');
    foreach ($config_factory->listAll('uniqueness.bundle_settings.') as $config_name) {
      [, , $entity_type, $bundle] = explode('.', $config_name);
      $config = $config_factory->get($config_name);
      if (
        !empty($config->get('uniqueness_type.add'))
        || !empty($config->get('uniqueness_type.edit'))
      ) {
        return;
      }
    }
    \Drupal::service('messenger')->addWarning($this->t(
          'Uniqueness search has not been enabled for any content types. To enable it, visit the configuration page for each desired <a href="@content-types-page">content type</a>.',
          ['@content-types-page' => URL::fromRoute('entity.node_type.collection')->toString()]
      ));
  }

  /**
   * Get the currently selected search mode.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return string
   *   The currently selected search mode.
   */
  protected function getCurrentSearchMode(FormStateInterface $form_state) {
    $config = $this->config('uniqueness.settings');
    $search_mode = $config->get('uniqueness_search_mode');
    if (!empty($form_state->getValue('uniqueness_search_mode'))) {
      $search_mode = $form_state->getValue('uniqueness_search_mode');
    }
    return $search_mode;
  }

  /**
   * {@inheritdoc}
   *
   * @param mixed[] $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return mixed[]
   *   Render array for the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('uniqueness.settings');
    $this->statusCheck();

    $form['search'] = [
      '#type' => 'details',
      '#title' => $this->t('Search'),
      '#open' => TRUE,
    ];

    $form['search']['uniqueness_search_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Search mode'),
      '#description' => $this->t('Select the mode which should be used for generating the list of related nodes.'),
      '#options' => $this->searchModeOptions(),
      '#default_value' => $this->getCurrentSearchMode($form_state),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::searchModeAjaxCallback',
        'disable-refocus' => FALSE,
        'event' => 'change',
        'wrapper' => 'uniqueness-search-mode-config',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Fetching search mode configuration form...'),
        ],
      ],
    ];

    $this->addPluginForm($form, $form_state);

    $form['search']['uniqueness_scope'] = [
      '#type' => 'radios',
      '#title' => $this->t('Search scope'),
      '#options' => [
        'all' => $this->t('Search in all nodes'),
        'content_type' => $this->t('Search only within the content type of the node being added or edited.'),
      ],
      '#default_value' => $config->get('uniqueness_scope'),
      '#description' => $this->t('Search all nodes or just nodes of the same content type.'),
      '#required' => TRUE,
    ];

    $form['search']['uniqueness_results_max'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum number of results'),
      '#default_value' => $config->get('uniqueness_results_max'),
      '#size' => 5,
      '#maxlength' => 4,
      '#element_validate' => [[$this, 'validateResultsMax']],
      '#description' => $this->t('Limit the number of search results. (For "Drupal search", must be 10 or fewer.)'),
      '#required' => TRUE,
    ];
    $form['search']['uniqueness_query_min'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum length of search string'),
      '#default_value' => $config->get('uniqueness_query_min'),
      '#size' => 5,
      '#maxlength' => 4,
      '#element_validate' => [[$this, 'validateQueryMin']],
      '#description' => $this->t('Enter the minimum number of characters required in the node title for triggering a search. (For "Drupal search", must be @min or more.)', ['@min' => $this->minimumWordSize()]),
      '#required' => TRUE,
    ];

    // Appearance.
    $form['appearance'] = [
      '#type' => 'details',
      '#title' => $this->t('Appearance'),
      '#open' => TRUE,
    ];
    $form['appearance']['uniqueness_widgets'] = [
      '#type' => 'checkboxes',
      '#options' => [
        'inline' => t('Display related content embedded on the create or edit content form.'),
        'block' => t('Provide a block for displaying related content.'),
      ],
      '#default_value' => $config->get('uniqueness_widgets'),
    ];
    $form['appearance']['uniqueness_default_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default title'),
      '#default_value' => $config->get('uniqueness_default_title'),
      '#description' => $this->t(
              'Note: When displayed in a block, this title may be overridden by setting the block title on the <a href="@block-settings-url">uniqueness block settings</a> page.',
              [
                  // '@block-settings-url' => url('admin/structure/block/configure/uniqueness/uniqueness'),
                '@block-settings-url' => 'todo',
              ]
      ),
      '#required' => TRUE,
    ];
    $form['appearance']['uniqueness_default_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default description'),
      '#default_value' => $config->get('uniqueness_default_description'),
      '#rows' => 2,
    ];
    $form['appearance']['uniqueness_searching_string'] = [
      '#type' => 'textfield',
      '#title' => t('Search notifier'),
      '#default_value' => $config->get('uniqueness_searching_string'),
      '#description' => $this->t('The text to display while the uniqueness search is in progress.'),
    ];
    $form['appearance']['uniqueness_no_result_string'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No related content message'),
      '#default_value' => $config->get('uniqueness_no_result_string'),
      '#description' => $this->t('The text to display if the uniqueness search no longer finds any related content.'),
    ];
    $form['appearance']['uniqueness_results_prepend'] = [
      '#type' => 'radios',
      '#title' => $this->t('Results display'),
      '#options' => [
        0 => $this->t('Replace old results with new ones'),
        1 => $this->t('Leave old results and prepend new ones'),
      ],
      '#default_value' => $config->get('uniqueness_results_prepend') ? 1 : 0,
      '#description' => $this->t('Choose if new results replace or are added to existing results. Browser cache may keep this setting from taking affect right away.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * Display the subform for the currently selected plugin.
   *
   * @param mixed[] $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  protected function addPluginForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('uniqueness.settings');
    $form['search']['uniqueness_search_mode_config'] = [
      '#prefix' => '<div id="uniqueness-search-mode-config">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];

    $search_mode = $this->getCurrentSearchMode($form_state);
    $plugin_config = [];
    if (empty($search_mode)) {
      return;
    }

    $plugin_manager = \Drupal::service('plugin.manager.uniqueness_search_mode');
    $plugin_instance = $plugin_manager->createInstance($search_mode, $plugin_config);

    $form['search']['uniqueness_search_mode_config'] = $plugin_instance->settingsForm(
      $form['search']['uniqueness_search_mode_config'],
      $form_state,
      $form
    );
  }

  /**
   * AJAX callback for uniqueness_search_mode change.
   *
   * @param mixed[] $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request.
   *
   * @return mixed
   *   The result of the AJAX callback.
   */
  public function searchModeAjaxCallback(array &$form, FormStateInterface $form_state, Request $request) {
    return $form['search']['uniqueness_search_mode_config'];
  }

  /**
   * Validate that the number of results is numeric and within range.
   *
   * @param mixed[] $element
   *   The form element being validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function validateResultsMax(array $element, FormStateInterface &$form_state): void {
    $search_mode = $form_state->getValue('uniqueness_search_mode');
    $upper_limit = $search_mode === 'drupal_search' ? 10 : 100;
    $value = $element['#value'];
    if (!is_numeric($value) || $value <= 0 || $value > $upper_limit) {
      $form_state->setError(
        $element,
        $this->t('The number of results must be between 1 and @upper.', [
          '@upper' => $upper_limit,
        ])
      );
    }
  }

  /**
   * Validate that minimum search character count is numeric and within range.
   *
   * @param mixed[] $element
   *   The form element being validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function validateQueryMin(array $element, FormStateInterface &$form_state): void {
    $search_mode = $form_state->getValue('uniqueness_search_mode');
    $lower_limit = $search_mode === 'drupal_search' ? $this->minimumWordSize() : 1;
    $value = $element['#value'];
    if (!is_numeric($value) || $value < $lower_limit || $value > 30) {
      $form_state->setError(
        $element,
        $this->t('The number of search characters must be between @lower and 30.', [
          '@lower' => $lower_limit,
        ])
      );
    }
  }

  /**
   * {@inheritdoc}
   *
   * @param mixed[] $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $submit_settings = [
      'uniqueness_search_mode',
      'uniqueness_scope',
      'uniqueness_results_max',
      'uniqueness_query_min',
      'uniqueness_widgets',
      'uniqueness_default_title',
      'uniqueness_default_description',
      'uniqueness_searching_string',
      'uniqueness_no_result_string',
      'uniqueness_results_prepend',
    ];
    $config = $this->config('uniqueness.settings');
    foreach ($submit_settings as $setting_name) {
      $config->set($setting_name, $form_state->getValue($setting_name));
    }
    $config->save();

    // Handle plugin config saves.
    $plugin_id = $form_state->getValue('uniqueness_search_mode');
    $plugin_config = [];
    if (!empty($plugin_id)) {
      $plugin_manager = \Drupal::service('plugin.manager.uniqueness_search_mode');
      $plugin_instance = $plugin_manager->createInstance($plugin_id, $plugin_config);
      $plugin_instance->submitSettingsForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

}
