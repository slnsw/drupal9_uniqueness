<?php

namespace Drupal\uniqueness\Form\Handler;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the handler for bundle entity forms.
 */
class BundleEntityFormHandler extends EntityFormHandlerBase {

  use BundleEntityFormHandlerTrait;

  /**
   * {@inheritdoc}
   */
  public function formAlter(array &$form, FormStateInterface $form_state): void {
    parent::formAlter($form, $form_state);

    $settings = $this->getSettings();

    $form['uniqueness'] = [
      '#type' => 'details',
      '#group' => 'advanced',
      '#title' => $this->t('Uniqueness'),
      '#attributes' => ['class' => ['uniqueness-fieldset']],
      '#tree' => TRUE,
      '#weight' => 10,
    ];

    $form['uniqueness']['add'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('When adding new content of this content type'),
      '#default_value' => !empty($settings['uniqueness_type']['add']),
      '#attributes' => [
        'data-uniqueness-label' => $this->t('adding new content'),
      ],
    ];

    $form['uniqueness']['edit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('When editing existing content of this content type'),
      '#default_value' => !empty($settings['uniqueness_type']['edit']),
      '#attributes' => [
        'data-uniqueness-label' => $this->t('editing existing content'),
      ],
    ];

    // Only attach fieldset summary js to 'additional settings' vertical tabs.
    if (isset($form['additional_settings'])) {
      $form['uniqueness']['#attached']['library'][] = 'uniqueness/fieldsetSummaries';
      $form['uniqueness']['#group'] = 'additional_settings';
    }

    $form['uniqueness'] = $this->settingsForm($form['uniqueness']);

    $this->addSubmitHandlers($form, [$this, 'submitForm']);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form): array {
    $form = parent::settingsForm($form);

    if ($this->bundleName !== NULL) {
      $entity_type = \Drupal::entityTypeManager()->getDefinition($this->entityTypeId);
      if ($bundle_entity_type_id = $entity_type->getBundleEntityType()) {
        $bundle_label = \Drupal::entityTypeManager()
          ->getDefinition($bundle_entity_type_id)
          ->getLabel();
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $entity_uniqueness_config = $form_state->getValue(['uniqueness']);
    $config = \Drupal::configFactory()->getEditable('uniqueness.bundle_settings.' . $this->entityTypeId . '.' . $this->bundleName);
    $config->set('uniqueness_type.add', $entity_uniqueness_config['add']);
    $config->set('uniqueness_type.edit', $entity_uniqueness_config['edit']);
    $config->save();
  }

}
