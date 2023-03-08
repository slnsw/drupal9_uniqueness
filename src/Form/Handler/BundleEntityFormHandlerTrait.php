<?php

namespace Drupal\uniqueness\Form\Handler;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a trait for bundle entity forms.
 */
trait BundleEntityFormHandlerTrait {

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    parent::setEntity($entity);

    // @todo https://git.drupalcode.org/project/simple_sitemap/-/blob/4.x/src/Entity/EntityHelper.php
    $this->entityTypeId = $entity->getEntityType()->getBundleOf();
    $this->bundleName = $entity->id();

    if ($this->entityTypeId === NULL) {
      throw new \InvalidArgumentException('Entity does not provide bundles for another entity type');
    }
    if ($this->bundleName !== NULL) {
      $this->bundleName = (string) $this->bundleName;
    }

    return $this;
  }

  /**
   * Sets the entity type ID.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return $this
   */
  public function setEntityTypeId(string $entity_type_id): self {
    $this->entityTypeId = $entity_type_id;
    $this->entity = NULL;
    return $this;
  }

  /**
   * Sets the bundle name.
   *
   * @param string $bundle_name
   *   The bundle name.
   *
   * @return $this
   */
  public function setBundleName(string $bundle_name): self {
    $this->bundleName = $bundle_name;
    $this->entity = NULL;
    return $this;
  }

}
