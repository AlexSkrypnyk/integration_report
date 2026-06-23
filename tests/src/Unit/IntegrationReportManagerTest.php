<?php

declare(strict_types=1);

namespace Drupal\Tests\integration_report\Unit;

use PHPUnit\Framework\Attributes\Group;
use Drupal\integration_report\IntegrationReportInterface;
use Drupal\integration_report\IntegrationReportManager;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for the IntegrationReportManager service.
 *
 * @covers \Drupal\integration_report\IntegrationReportManager
 *
 * @group integration_report
 */
#[Group('integration_report')]
class IntegrationReportManagerTest extends UnitTestCase {

  /**
   * Test Integration report manager.
   */
  public function testIntegrationReportManager(): void {
    $manager = new IntegrationReportManager();

    $integration_report_mock_1 = $this->createMock(IntegrationReportInterface::class);
    $integration_report_mock_1->method('info')
      ->willReturn(['name' => 'Integration Report 1', 'Description' => 'Integration Report 1 Description']);
    $manager->addReport($integration_report_mock_1, 1);

    $integration_report_mock_2 = $this->createMock(IntegrationReportInterface::class);
    $integration_report_mock_2->method('info')
      ->willReturn(['name' => 'Integration Report 2', 'Description' => 'Integration Report 2 Description']);
    $manager->addReport($integration_report_mock_2, 5);

    $this->assertSame($integration_report_mock_2, $manager->findReport($integration_report_mock_2::class));
    $this->assertEquals(2, count($manager->getReports()));
    $this->assertSame($integration_report_mock_2, $manager->getReports()[0]);
    $this->assertSame($integration_report_mock_1, $manager->getReports()[1]);

  }

  /**
   * Test that findReport returns NULL when no report matches.
   */
  public function testFindReportNotFound(): void {
    $manager = new IntegrationReportManager();
    $mock = $this->createMock(IntegrationReportInterface::class);
    $manager->addReport($mock);

    $this->assertNull($manager->findReport('NonExistentReportName'));
  }

  /**
   * Test that getReports returns an empty array when no reports are added.
   */
  public function testGetReportsEmpty(): void {
    $manager = new IntegrationReportManager();
    $this->assertSame([], $manager->getReports());
  }

  /**
   * Test that addReport returns the manager instance for chaining.
   */
  public function testAddReportFluent(): void {
    $manager = new IntegrationReportManager();
    $mock = $this->createMock(IntegrationReportInterface::class);

    $this->assertSame($manager, $manager->addReport($mock));
    $this->assertSame($manager, $manager->addReport($mock, 10));
  }

  /**
   * Test that addReport with the default priority places the report last.
   */
  public function testAddReportDefaultPriority(): void {
    $manager = new IntegrationReportManager();
    $high = $this->createMock(IntegrationReportInterface::class);
    $low = $this->createMock(IntegrationReportInterface::class);

    $manager->addReport($high, 10);
    $manager->addReport($low);

    $reports = $manager->getReports();
    $this->assertSame($high, $reports[0]);
    $this->assertSame($low, $reports[1]);
  }

}
