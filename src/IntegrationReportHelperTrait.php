<?php

namespace Drupal\integration_report;

trait IntegrationReportHelperTrait {

  public static function getShortClassName($class) {
    return (new \ReflectionClass($class))->getShortName();
  }

  public static function render($element) {
    return \Drupal::service('renderer')->render($element);
  }

}
