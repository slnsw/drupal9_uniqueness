<?php

namespace Drupal\uniqueness\Form;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\uniqueness\Form\Handler\EntityFormHandlerInterface;
use Drupal\uniqueness\Form\Handler\BundleEntityFormHandler;
use Drupal\uniqueness\Form\Handler\EntityFormHandler;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\uniqueness\Settings;

/**
 * Helper class for working with forms.
 */
class FormHelper {

  use StringTranslationTrait;

  protected const ENTITY_FORM_HANDLER = EntityFormHandler::class;
  protected const BUNDLE_ENTITY_FORM_HANDLER = BundleEntityFormHandler::class;

  /**
   * Proxy for the current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The uniqueness.settings service.
   *
   * @var \Drupal\uniqueness\Settings
   */
  protected $settings;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * FormHelper constructor.
   *
   * @param \Drupal\uniqueness\Settings $settings
   *   The uniqueness.settings service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Proxy for the current user account.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   */
  public function __construct(
    Settings $settings,
    AccountProxyInterface $current_user,
    ClassResolverInterface $class_resolver
  ) {
    $this->settings = $settings;
    $this->currentUser = $current_user;
    $this->classResolver = $class_resolver;
  }

  /**
   * Alters the specified form.
   *
   * @param mixed[] $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see uniqueness_form_alter()
   * @see uniqueness_engines_form_alter()
   */
  public function formAlter(array &$form, FormStateInterface $form_state): void {
    if (!$this->formAlterAccess()) {
      return;
    }

    $form_object = $form_state->getFormObject();
    if ($form_object instanceof ContentEntityFormInterface) {
      $form_handler = $this->resolveEntityFormHandler($form_object->getEntity());
      if (
        $form_handler instanceof EntityFormHandler &&
        $form_handler->isSupportedOperation($form_object->getOperation())
      ) {
        $form_handler->formAlter($form, $form_state);
      }
    }
    elseif ($form_object instanceof BundleEntityFormBase) {
      $form_handler = $this->resolveEntityFormHandler($form_object->getEntity());
      if (
        $form_handler instanceof EntityFormHandlerInterface &&
        $form_handler->isSupportedOperation($form_object->getOperation())
      ) {
        $entity_type_id = $form_handler->getEntityTypeId();
        $form_handler->formAlter($form, $form_state);
      }
    }
  }

  /**
   * Determines whether a form can be altered.
   *
   * @return bool
   *   TRUE if a form can be altered, FALSE otherwise.
   */
  protected function formAlterAccess(): bool {
    return $this->currentUser->hasPermission('administer uniqueness') ||
      $this->currentUser->hasPermission('use uniqueness widget');
  }

  /**
   * Resolves the entity form handler for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which the form handler should be resolved.
   *
   * @return \Drupal\uniqueness\Form\Handler\EntityFormHandlerInterface|null
   *   The instance of the entity form handler or NULL if there is no handler
   *   for the given entity.
   */
  public function resolveEntityFormHandler(EntityInterface $entity): ?EntityFormHandlerInterface {
    $definition = $this->resolveEntityFormHandlerDefinition($entity);

    if ($definition) {
      return $this->classResolver
        ->getInstanceFromDefinition($definition)
        ->setEntity($entity);
    }
    return NULL;
  }

  /**
   * Resolves the definition of the entity form handler for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which the definition should be resolved.
   *
   * @return string|null
   *   A string with definition of the entity form handler or NULL if there is
   *   no definition for the given entity.
   */
  protected function resolveEntityFormHandlerDefinition(EntityInterface $entity): ?string {
    if ($this->supports($entity->getEntityType())) {
      return static::ENTITY_FORM_HANDLER;
    }

    $entity_type_id = $entity->getEntityTypeId();
    foreach ($this->getSupportedEntityTypes() as $entity_type) {
      if ($entity_type->getBundleEntityType() === $entity_type_id) {
        return static::BUNDLE_ENTITY_FORM_HANDLER;
      }
    }
    return NULL;
  }

  /**
   * Get all supported entity types.
   *
   * @return mixed[]
   *   An array of supported entity type defintions.
   */
  protected function getSupportedEntityTypes() {
    $entity_type_manager = \Drupal::entityTypeManager();
    return array_filter($entity_type_manager->getDefinitions(), [
      $this,
      'supports',
    ]);
  }

  /**
   * Determines if an entity type is supported or not.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   *
   * @return bool
   *   TRUE if entity type is supported, FALSE if not.
   */
  public function supports(EntityTypeInterface $entity_type): bool {
    return $entity_type instanceof ContentEntityTypeInterface;
  }

  /**
   * Returns a form to configure the bundle settings.
   *
   * @param mixed[] $form
   *   The form where the bundle settings form is being included in.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle_name
   *   The bundle name.
   *
   * @return mixed[]
   *   The form elements for the bundle settings.
   */
  public function bundleSettingsForm(array $form, $entity_type_id, $bundle_name): array {
    /** @var \Drupal\uniqueness\Form\Handler\BundleEntityFormHandler $form_handler */
    $form_handler = $this->classResolver->getInstanceFromDefinition(static::BUNDLE_ENTITY_FORM_HANDLER);

    return $form_handler->setEntityTypeId($entity_type_id)
      ->setBundleName($bundle_name)
      ->settingsForm($form);
  }

}
