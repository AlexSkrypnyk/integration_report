<?php

declare(strict_types = 1);

namespace Drupal\Tests\integration_report\Unit;

use Drupal\integration_report\IntegrationReportInterface;
use Drupal\integration_report\IntegrationReportManager;
use Drupal\Tests\UnitTestCase;

/**
 * Class IntegrationReportManagerTest.
 *
 * Example test case class.
 *
 * @covers \Drupal\integration_report\IntegrationReportManager
 *
 * @group integration_report
 */
class IntegrationReportManagerTest extends UnitTestCase {

  /**
   * Test Integration report manager.
   */
  public function testIntegrationReportManager(): void {
    $manager = new IntegrationReportManager();

    $integrationReportMock1 = $this->createMock(IntegrationReportInterface::class);
    $integrationReportMock1->method('info')
      ->willReturn(['name' => 'Integration Report 1', 'Description' => 'Integration Report 1 Description']);
    $manager->addReport($integrationReportMock1, 1);

    $integrationReportMock2 = $this->createMock(IntegrationReportInterface::class);
    $integrationReportMock2->method('info')
      ->willReturn(['name' => 'Integration Report 2', 'Description' => 'Integration Report 2 Description']);
    $manager->addReport($integrationReportMock2, 5);

    $this->assertSame($integrationReportMock2, $manager->findReport($integrationReportMock2::class));
    $this->assertEquals(2, count($manager->getReports()));
    $this->assertSame($integrationReportMock2, $manager->getReports()[0]);
    $this->assertSame($integrationReportMock1, $manager->getReports()[1]);

  }

}
