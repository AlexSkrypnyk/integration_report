<?php

declare(strict_types=1);

namespace Drupal\Tests\integration_report\FunctionalJavascript;

/**
 * Tests iframe-based integration report status flow.
 *
 * @group integration_report
 */
class IntegrationReportIframeJsTest extends IntegrationReportJsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['integration_report', 'integration_report_example'];

  /**
   * Tests that the report page renders with iframes and receives postMessages.
   */
  public function testIframeStatusFlow(): void {
    $account = $this->drupalCreateUser(['access integration report']);
    $this->assertNotFalse($account);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/reports/integrations');
    $this->createAutoScreenshot();

    $page = $this->getSession()->getPage();

    // Verify the report table renders.
    $table = $page->find('css', 'table.integration-report-table');
    $this->assertNotNull($table, 'Integration report table is present.');

    // Verify report rows exist for both examples.
    $row1 = $page->find('css', '[data-status-result="IntegrationReportExample1"]');
    $row2 = $page->find('css', '[data-status-result="IntegrationReportExample2"]');
    $this->assertNotNull($row1, 'Report row for Example 1 exists.');
    $this->assertNotNull($row2, 'Report row for Example 2 exists.');

    // Verify rows have a status class (may already be resolved by postMessage).
    $this->assertTrue($row1->hasClass('warning') || $row1->hasClass('ok') || $row1->hasClass('error'), 'Row 1 has a status class.');
    $this->assertTrue($row2->hasClass('warning') || $row2->hasClass('ok') || $row2->hasClass('error'), 'Row 2 has a status class.');

    // Verify iframes are present for callback-based reports.
    $iframes = $page->findAll('css', '.integration-report-debug-result iframe');
    $this->assertNotEmpty($iframes, 'Iframes are present on the page.');

    // Verify iframe src attributes point to the JS callback route.
    $iframe_srcs = [];
    foreach ($iframes as $iframe) {
      $iframe_srcs[] = $iframe->getAttribute('src');
    }
    $this->assertNotEmpty(array_filter($iframe_srcs, fn(?string $src): bool => str_contains((string) $src, '/admin/reports/integrations/IntegrationReportExample1')));
    $this->assertNotEmpty(array_filter($iframe_srcs, fn(?string $src): bool => str_contains((string) $src, '/admin/reports/integrations/IntegrationReportExample2')));

    // Wait for postMessage responses to update the table rows.
    // Rows get 'status-report-complete' class when postMessage is received.
    $this->assertJsCondition('document.querySelectorAll(".status-report-complete").length >= 2', 15000);
    $this->createAutoScreenshot();

    // Verify rows are no longer in "warning" state after receiving responses.
    $row1 = $page->find('css', '[data-status-result="IntegrationReportExample1"]');
    $row2 = $page->find('css', '[data-status-result="IntegrationReportExample2"]');
    $this->assertTrue($row1->hasClass('ok') || $row1->hasClass('error'), 'Row 1 resolved to ok or error.');
    $this->assertTrue($row2->hasClass('ok') || $row2->hasClass('error'), 'Row 2 resolved to ok or error.');

    // Verify "Loading..." text has been replaced with a response.
    $response1 = $row1->find('css', '.status-report-response')->getText();
    $response2 = $row2->find('css', '.status-report-response')->getText();
    $this->assertStringContainsString('ms)', $response1, 'Row 1 shows response time.');
    $this->assertStringContainsString('ms)', $response2, 'Row 2 shows response time.');

    // Verify the throbber has been removed after completion.
    $this->assertNull($row1->find('css', '.ajax-progress'), 'Row 1 throbber removed.');
    $this->assertNull($row2->find('css', '.ajax-progress'), 'Row 2 throbber removed.');

    // Verify debug result sections exist.
    $debug1 = $page->find('css', '[data-debug-result="IntegrationReportExample1"]');
    $debug2 = $page->find('css', '[data-debug-result="IntegrationReportExample2"]');
    $this->assertNotNull($debug1, 'Debug section for Example 1 exists.');
    $this->assertNotNull($debug2, 'Debug section for Example 2 exists.');

    // Verify clicking a row toggles the 'open' class.
    // Use JavaScript click to avoid WebDriver compatibility issues with D10.
    $this->assertFalse($row1->hasClass('open'), 'Row 1 is not open before click.');
    $this->getSession()->executeScript('document.querySelector("[data-status-result=IntegrationReportExample1]").click()');
    $this->assertTrue($row1->hasClass('open'), 'Row 1 is open after click.');
    $this->getSession()->executeScript('document.querySelector("[data-status-result=IntegrationReportExample1]").click()');
    $this->assertFalse($row1->hasClass('open'), 'Row 1 is closed after second click.');
  }

}
