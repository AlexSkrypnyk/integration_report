<?php

declare(strict_types = 1);

namespace Drupal\integration_report;

/**
 * Class IntegrationReportManager.
 *
 * Manages gathered reports.
 *
 * @package Drupal\integration_report
 */
class IntegrationReportManager implements IntegrationReportManagerInterface {

  use IntegrationReportHelperTrait;

  /**
   * Array of discovered instantiated report objects.
   *
   * @var array<int, \Drupal\integration_report\IntegrationReportInterface[]>
   */
  protected array $reports = [];

  /**
   * {@inheritDoc}
   */
  public function getReports(): array {
    return $this->sortReports();
  }

  /**
   * {@inheritDoc}
   */
  public function addReport(IntegrationReportInterface $report, int $priority = 0): IntegrationReportManager {
    $this->reports[$priority][] = $report;

    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function findReport(string $class): ?IntegrationReportInterface {
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
   * @return \Drupal\integration_report\IntegrationReportInterface[]
   *   Sorted reports.
   */
  protected function sortReports(): array {
    $sorted = [];

    krsort($this->reports);

    foreach ($this->reports as $items) {
      $sorted = array_merge($sorted, $items);
    }

    return $sorted;
  }

}
