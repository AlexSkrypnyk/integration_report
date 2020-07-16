<?php

namespace Drupal\integration_report;

/**
 * Trait IntegrationReportHelperTrait.
 *
 * @package Drupal\integration_report
 */
trait IntegrationReportHelperTrait {

  /**
   * Get short class name from the namespaced class.
   *
   * @param string $class
   *   Class name prefixed by a namespace.
   *
   * @return string
   *   Short class name.
   */
  public static function getShortClassName($class) {
    return (new \ReflectionClass($class))->getShortName();
  }

  /**
   * Render ekement.
   *
   * @param mixed $element
   *   Element to render.
   *
   * @return string
   *   Rendered element as a string.
   */
  public static function render($element) {
    return \Drupal::service('renderer')->render($element);
  }

}
