<?php

namespace Drupal\uniqueness\Form\Handler;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a trait for entity form handlers.
 */
trait EntityFormHandlerTrait {

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    parent::setEntity($entity);

    $this->entityTypeId = $entity->getEntityTypeId();
    $this->bundleName = $entity->bundle();

    return $this;
  }

}
