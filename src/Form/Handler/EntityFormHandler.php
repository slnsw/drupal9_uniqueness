<?php

namespace Drupal\uniqueness\Form\Handler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Xss;

// Use Drupal\uniqueness\Entity\SimpleSitemap;.

/**
 * Defines the handler for entity forms.
 */
class EntityFormHandler extends EntityFormHandlerBase {

  use EntityFormHandlerTrait;

  /**
   * {@inheritdoc}
   */
  protected $operations = ['default', 'edit', 'add'];

  /**
   * {@inheritdoc}
   */
  public function formAlter(array &$form, FormStateInterface $form_state): void {
    parent::formAlter($form, $form_state);

    $config = \Drupal::config('uniqueness.settings');
    $frontend_settings = \Drupal::service('uniqueness.frontend_settings');
    $widget = \Drupal::service('uniqueness.widget');
    $widgets = $config->get('uniqueness_widgets') ?? [];

    $bundle_config = \Drupal::configFactory()->getEditable('uniqueness.bundle_settings.' . $this->getEntityTypeId() . '.' . $this->getBundleName());
    $can_show_uniqueness = $this->entity->isNew()
      ? $bundle_config->get('uniqueness_type.add')
      : $bundle_config->get('uniqueness_type.edit');

    if (\Drupal::currentUser()->hasPermission('use uniqueness widget') && $can_show_uniqueness) {
      $form['uniqueness']['#attached']['library'][] = 'uniqueness/frontend';
      $form['uniqueness']['#attached']['drupalSettings']['uniqueness'] = $frontend_settings->getSettings(
        $this->getEntityTypeId(),
        $this->getBundleName(),
        $this->entity->isNew() ? NULL : $this->entity->id()
      );

      $values = [];
      if (!$this->entity->isNew()) {
        $values['entity_id'] = $this->entity->id();
      }
      if (!empty($form['title'])) {
        $values['title'] = $form['title']['#default_value'];
      }
      // @todo taxonomy.tags???
      if (in_array('inline', $widgets)) {
        $count = 0;
        $widget_content = $widget->content($values, $count);
        $form['uniqueness'] += [
          '#type' => 'details',
          '#title' => Xss::filterAdmin($config->get('uniqueness_default_title')),
          '#open' => $count > 0,
          'uniqueness_type' => [
            '#type' => 'item',
            '#title' => '',
            '#markup' => $widget_content,
          ],
        ];
      }
    }
  }

}
