<?php

declare(strict_types = 1);

namespace Drupal\Tests\integration_report\Unit;

use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\integration_report\IntegrationReportBase;
use Drupal\Tests\UnitTestCase;

/**
 * Class IntegrationReportTest.
 *
 * Example test case class.
 *
 * @group integration_report
 */
class IntegrationReportTest extends UnitTestCase {

  /**
   * Test.
   */
  public function testIntegrationReport(): void {
    $translationManager = $this->createMock(TranslationInterface::class);
    $renderer = $this->createMock(RendererInterface::class);

    $integrationReport = new TestIntegrationReportBase($translationManager, $renderer);
    $this->assertSame('Test Integration Report Base Name', $integrationReport->getName());
    $this->assertSame('Test Integration Report Base Description', $integrationReport->getDescription());
    $this->assertNull($integrationReport->getJs());
    $this->assertNull($integrationReport->isSecureCallback());
    $this->assertTrue($integrationReport->access());
    $this->assertTrue($integrationReport->isUseCallback());
    $this->assertSame('', $integrationReport->statusPage());
  }

}

/**
 * A test class to test IntegrationReportBase abstract.
 */
class TestIntegrationReportBase extends IntegrationReportBase {

  /**
   * {@inheritDoc}
   */
  public function info(): array {
    $info = parent::info();

    return [
      'name' => 'Test Integration Report Base Name',
      'description' => 'Test Integration Report Base Description',
      'js' => NULL,
      'use_callback' => TRUE,
      'secure_callback' => NULL,
      'access' => TRUE,
    ] + $info;
  }

}
