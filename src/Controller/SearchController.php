<?php

namespace Drupal\uniqueness\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Search controller.
 */
class SearchController extends ControllerBase {

  /**
   * Search endpoint.
   */
  public function search(string $entity_type = NULL, string $bundle = NULL): JsonResponse {
    $values = [];
    $parameters = \Drupal::request()->query->all();
    $config = \Drupal::config('uniqueness.settings');
    $widget = \Drupal::service('uniqueness.widget');
    foreach (['tags', 'title', 'entity_id', 'entity_type', 'bundle'] as $key) {
      if (!empty($parameters[$key])) {
        $values[$key] = strip_tags($parameters[$key]);
      }
    }
    $results = [];
    $has_more = FALSE;
    $output = [];
    if (!empty($values)) {
      $results = $widget->doSearch($values, TRUE);
      $has_more = $widget->getLastSearchHasMore();
    }
    if (!empty($results)) {
      $i = 0;
      foreach ($results as $entity_id => $entity_link) {
        $output[$entity_id]['title'] = $entity_link['label'];
        $output[$entity_id]['status'] = $entity_link['status'];
        $output[$entity_id]['href'] = $entity_link['url']->toString();
        $output[$entity_id]['more'] = ($i === count($results) - 1) && $has_more;

        $i += 1;
      }
    }
    $response = new JsonResponse($output);
    return $response;
  }

}
