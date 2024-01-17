<?php

declare(strict_types = 1);

namespace Drupal\Tests\integration_report\FunctionalJavascript;

use Drupal\Tests\BrowserTestBase;

/**
 * Test functional integration report.
 *
 * @group integration_report
 */
class IntegrationReportTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['integration_report', 'integration_report_example'];

  /**
   * Test integration report.
   */
  public function testIntegrationReport(): void {
    $this->drupalGet('/admin/reports/integrations');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/reports/integrations/IntegrationReportExample1');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/reports/integrations/IntegrationReportExample2');
    $this->assertSession()->statusCodeEquals(403);

    $acccount = $this->drupalCreateUser(['access integration report']);
    $this->assertNotEmpty($acccount);
    $this->drupalLogin($acccount);

    $this->drupalGet('/admin/reports/integrations');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Report example 1');
    $this->assertSession()->pageTextContains('Report example 1 description');

    $this->assertSession()->pageTextContains('Report example 2');
    $this->assertSession()->pageTextContains('Report example 2 description - open browser developer tools and assert that the test string was posted');

    $this->drupalGet('admin/reports/integrations/IntegrationReportExample1');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('admin/reports/integrations/IntegrationReportExample2');
    $this->assertSession()->statusCodeEquals(200);
  }

}
