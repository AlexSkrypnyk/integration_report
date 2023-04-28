<?php

namespace Drupal\integration_report;

/**
 * Class IntegrationReportManager.
 *
 * Manages gathered reports.
 *
 * @package Drupal\integration_report
 */
class IntegrationReportManager {

  use IntegrationReportHelperTrait;

  /**
   * Array of discovered instantiated report objects.
   *
   * @var \Drupal\integration_report\IntegrationReport[]
   */
  protected $reports = [];

  /**
   * Get all available reports.
   *
   * @return array
   *   Array of available reports.
   */
  public function getReports() {
    return $this->sortReports();
  }

  /**
   * Add report to the list of reports.
   *
   * @param \Drupal\integration_report\IntegrationReport $report
   *   The report to add.
   * @param int $priority
   *   Priority to add. Read from the service tags. Used to sort reports.
   *
   * @return $this
   */
  public function addReport(IntegrationReport $report, $priority = 0) {
    $this->reports[$priority][] = $report;
    return $this;
  }

  /**
   * Find report by the short class name.
   *
   * @param string $class
   *   Short class name.
   *
   * @return \Drupal\integration_report\IntegrationReport
   *   Found report object.
   */
  public function findReport($class) {
    $reports = $this->getReports();
    foreach ($reports as $report) {
      if (strpos(static::getShortClassName($report), $class) !== FALSE) {
        return $report;
      }
    }

    return NULL;
  }

  /**
   * Sort reports.
   *
   * @return array
   *   Sorted reports.
   */
  protected function sortReports() {
    $sorted = [];

    krsort($this->reports);

    foreach ($this->reports as $items) {
      $sorted = array_merge($sorted, is_array($items) ? $items : [$items]);
    }

    return $sorted;
  }

}
