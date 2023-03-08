<?php

namespace Drupal\uniqueness\Form\Handler;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\uniqueness\Form\FormHelper;

/**
 * Defines a base class for altering an entity form.
 */
abstract class EntityFormHandlerBase implements EntityFormHandlerInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * Helper class for working with forms.
   *
   * @var \Drupal\uniqueness\Form\FormHelper
   */
  protected $formHelper;

  /**
   * The entity being used by this form handler.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $entity;

  /**
   * The entity type ID.
   *
   * @var string|null
   */
  protected $entityTypeId;

  /**
   * The bundle name.
   *
   * @var string|null
   */
  protected $bundleName;

  /**
   * The sitemap settings.
   *
   * @var mixed[]|null
   */
  protected $settings;

  /**
   * Supported form operations.
   *
   * @var string[]
   */
  protected $operations = ['default', 'edit', 'add'];

  /**
   * EntityFormHandlerBase constructor.
   *
   * @param \Drupal\uniqueness\Form\FormHelper $form_helper
   *   Helper class for working with forms.
   */
  public function __construct(FormHelper $form_helper) {
    $this->formHelper = $form_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): EntityFormHandlerBase {
    return new static(
      $container->get('uniqueness.form_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formAlter(array &$form, FormStateInterface $form_state): void {
    $this->processFormState($form_state);
    $this->addSubmitHandlers($form, [$this, 'submitForm']);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form): array {
    $form['#markup'] = '<strong>' . $this->t('Uniqueness') . '</strong>';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->processFormState($form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId(): ?string {
    return $this->entityTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleName(): ?string {
    return $this->bundleName;
  }

  /**
   * {@inheritdoc}
   */
  public function isSupportedOperation(string $operation): bool {
    return in_array($operation, $this->operations, TRUE);
  }

  /**
   * Retrieves data from form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \InvalidArgumentException
   *   In case the form is not an entity form.
   */
  protected function processFormState(FormStateInterface $form_state): void {
    $form_object = $form_state->getFormObject();

    if (!$form_object instanceof EntityFormInterface) {
      throw new \InvalidArgumentException('Invalid form state');
    }

    $this->setEntity($form_object->getEntity());
  }

  /**
   * Get the settings ID for the current entity type and bundle.
   *
   * @return string
   *   The settings object ID.
   */
  protected function settingsId(): string {
    return 'simple_sitemap.bundle_settings.' . $this->entityTypeId . '.' . $this->bundleName;
  }

  /**
   * Gets the sitemap settings.
   *
   * @return mixed[]
   *   The sitemap settings.
   */
  protected function getSettings(): array {
    if (!isset($this->settings)) {
      $config = \Drupal::config($this->settingsId());
      $this->settings = [
        'uniqueness_type' => [
          'add' => $config->get('uniqueness_type.add') ?? FALSE,
          'edit' => $config->get('uniqueness_type.edit') ?? FALSE,
        ],
      ];
    }
    return $this->settings;
  }

  /**
   * Adds the submit handlers to the structured form array.
   *
   * @param mixed[] $element
   *   An associative array containing the structure of the current element.
   * @param callable ...$handlers
   *   The submit handlers to add.
   */
  protected function addSubmitHandlers(array &$element, callable ...$handlers): void {
    // Add new handlers only if a handler for the 'save' action is present.
    if (!empty($element['#submit']) && in_array('::save', $element['#submit'], TRUE)) {
      array_push($element['#submit'], ...$handlers);
    }

    // Process child elements.
    foreach (Element::children($element) as $key) {
      $this->addSubmitHandlers($element[$key], ...$handlers);
    }
  }

}
