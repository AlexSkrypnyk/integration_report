<?php

declare(strict_types = 1);

namespace Drupal\integration_report;

/**
 * Provides an interface defining IntegrationReportManager.
 */
interface IntegrationReportManagerInterface {

  /**
   * Get all available reports.
   *
   * @return \Drupal\integration_report\IntegrationReportInterface[]
   *   Array of available reports.
   */
  public function getReports(): array;

  /**
   * Add report to the list of reports.
   *
   * @param \Drupal\integration_report\IntegrationReportInterface $report
   *   The report to add.
   * @param int $priority
   *   Priority to add. Read from the service tags. Used to sort reports.
   *
   * @return \Drupal\integration_report\IntegrationReportManager
   *   Integration report manager.
   */
  public function addReport(IntegrationReportInterface $report, int $priority = 0): IntegrationReportManager;

  /**
   * Find report by the short class name.
   *
   * @param string $class
   *   Short class name.
   *
   * @return \Drupal\integration_report\IntegrationReportInterface|null
   *   Found report object.
   *
   * @throws \ReflectionException
   */
  public function findReport(string $class): ?IntegrationReportInterface;

}
