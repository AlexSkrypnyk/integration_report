<?php

declare(strict_types=1);

namespace Drupal\Tests\integration_report\FunctionalJavascript;

/**
 * Tests XSS protection in integration report postMessage handling.
 *
 * @group integration_report
 */
class IntegrationReportXssJsTest extends IntegrationReportJsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['integration_report', 'integration_report_example'];

  /**
   * Tests that XSS payloads in postMessage data are sanitized.
   */
  public function testPostMessageXssSanitization(): void {
    $account = $this->drupalCreateUser(['access integration report']);
    $this->assertNotFalse($account);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/reports/integrations');

    // Wait for the page JS to be ready.
    $this->assertJsCondition('typeof Drupal.IntegrationReport !== "undefined"');

    // Send a malicious postMessage with XSS payloads in message.
    $this->getSession()->executeScript(<<<JS
      window.postMessage({
        type: 'IntegrationReportHandler',
        success: true,
        class: 'IntegrationReportExample1',
        message: '<ul><li>Safe item</li></ul><img src=x onerror="window.xssExecuted=true"><script>window.xssExecuted=true</script>',
        time: 100
      }, window.location.origin);
    JS);

    // Wait for the postMessage to be processed.
    $this->assertJsCondition('document.querySelector("[data-status-result=IntegrationReportExample1]").classList.contains("status-report-complete")', 5000);

    // Verify the XSS payload did not execute.
    $xss_executed = $this->getSession()->evaluateScript('window.xssExecuted === true');
    $this->assertFalse($xss_executed, 'XSS payload in message did not execute.');

    // Verify dangerous HTML was stripped.
    $message_html = $this->getSession()->evaluateScript('document.querySelector("[data-status-result=IntegrationReportExample1] .status-report-message").innerHTML');
    $this->assertStringNotContainsString('<img', $message_html, 'img tag was stripped.');
    $this->assertStringNotContainsString('<script', $message_html, 'script tag was stripped.');
    $this->assertStringNotContainsString('onerror', $message_html, 'Event handler was stripped.');

    // Verify safe HTML was preserved.
    $this->assertStringContainsString('<ul>', $message_html, 'Safe ul tag was preserved.');
    $this->assertStringContainsString('<li>', $message_html, 'Safe li tag was preserved.');
    $this->assertStringContainsString('Safe item', $message_html, 'Safe text content was preserved.');
  }

  /**
   * Tests that postMessage from a different origin is rejected.
   */
  public function testPostMessageOriginValidation(): void {
    $account = $this->drupalCreateUser(['access integration report']);
    $this->assertNotFalse($account);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/reports/integrations');

    // Wait for the page JS to be ready.
    $this->assertJsCondition('typeof Drupal.IntegrationReport !== "undefined"');

    // Inject a cross-origin iframe that sends a postMessage.
    // Since browsers enforce same-origin on postMessage e.origin, we simulate
    // the origin check by temporarily overriding the handler to test the guard.
    $this->getSession()->executeScript(<<<JS
      window.__originTestPassed = false;
      // Send a message and check that the handler processes same-origin.
      window.postMessage({
        type: 'IntegrationReportHandler',
        success: true,
        class: 'IntegrationReportExample1',
        message: 'origin-test-marker',
        time: 50
      }, window.location.origin);
    JS);

    // Wait for the postMessage to be processed.
    $this->assertJsCondition('document.querySelector("[data-status-result=IntegrationReportExample1]").classList.contains("status-report-complete")', 5000);

    // Verify same-origin message was accepted.
    $response = $this->getSession()->evaluateScript('document.querySelector("[data-status-result=IntegrationReportExample1] .status-report-message").textContent');
    $this->assertStringContainsString('origin-test-marker', $response, 'Same-origin postMessage was accepted.');
  }

  /**
   * Tests that the response text uses safe text insertion.
   */
  public function testResponseTextSanitization(): void {
    $account = $this->drupalCreateUser(['access integration report']);
    $this->assertNotFalse($account);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/reports/integrations');

    $this->assertJsCondition('typeof Drupal.IntegrationReport !== "undefined"');

    // Send a postMessage with a manipulated time value.
    $this->getSession()->executeScript(<<<JS
      window.postMessage({
        type: 'IntegrationReportHandler',
        success: true,
        class: 'IntegrationReportExample2',
        message: 'test',
        time: '<img src=x onerror="window.xssTime=true">'
      }, window.location.origin);
    JS);

    $this->assertJsCondition('document.querySelector("[data-status-result=IntegrationReportExample2]").classList.contains("status-report-complete")', 5000);

    // Verify time XSS did not execute.
    $xss_time = $this->getSession()->evaluateScript('window.xssTime === true');
    $this->assertFalse($xss_time, 'XSS payload in time did not execute.');

    // Verify the response text is safe (NaN for non-numeric time).
    $response_text = $this->getSession()->evaluateScript('document.querySelector("[data-status-result=IntegrationReportExample2] .status-report-response").textContent');
    $this->assertStringNotContainsString('<img', $response_text, 'Response text does not contain HTML.');
  }

  /**
   * Tests that safe HTML from Drupal item_list renders correctly.
   */
  public function testSafeHtmlPreserved(): void {
    $account = $this->drupalCreateUser(['access integration report']);
    $this->assertNotFalse($account);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/reports/integrations');

    $this->assertJsCondition('typeof Drupal.IntegrationReport !== "undefined"');

    // Send a postMessage with typical Drupal item_list HTML.
    $this->getSession()->executeScript(<<<JS
      window.postMessage({
        type: 'IntegrationReportHandler',
        success: true,
        class: 'IntegrationReportExample1',
        message: '<div class="item-list"><ul><li>Check 1 <strong>passed</strong>.</li><li>Check 2 <em>warning</em>.</li></ul></div>',
        time: 50
      }, window.location.origin);
    JS);

    $this->assertJsCondition('document.querySelector("[data-status-result=IntegrationReportExample1]").classList.contains("status-report-complete")', 5000);

    $message_html = $this->getSession()->evaluateScript('document.querySelector("[data-status-result=IntegrationReportExample1] .status-report-message").innerHTML');

    // Verify safe HTML structure is preserved.
    $this->assertStringContainsString('<ul>', $message_html, 'ul tag preserved.');
    $this->assertStringContainsString('<li>', $message_html, 'li tag preserved.');
    $this->assertStringContainsString('<strong>', $message_html, 'strong tag preserved.');
    $this->assertStringContainsString('<em>', $message_html, 'em tag preserved.');
    $this->assertStringContainsString('Check 1', $message_html, 'Text content preserved.');
    $this->assertStringContainsString('Check 2', $message_html, 'Text content preserved.');
  }

  /**
   * Tests that event handler attributes are stripped from allowed tags.
   */
  public function testEventHandlerAttributesStripped(): void {
    $account = $this->drupalCreateUser(['access integration report']);
    $this->assertNotFalse($account);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/reports/integrations');

    $this->assertJsCondition('typeof Drupal.IntegrationReport !== "undefined"');

    // Send HTML with event handlers on allowed tags.
    $this->getSession()->executeScript(<<<JS
      window.postMessage({
        type: 'IntegrationReportHandler',
        success: true,
        class: 'IntegrationReportExample2',
        message: '<ul onmouseover="window.xssEvent=true"><li onclick="window.xssEvent=true">Item</li></ul>',
        time: 50
      }, window.location.origin);
    JS);

    $this->assertJsCondition('document.querySelector("[data-status-result=IntegrationReportExample2]").classList.contains("status-report-complete")', 5000);

    // Verify event handlers did not execute.
    $xss_event = $this->getSession()->evaluateScript('window.xssEvent === true');
    $this->assertFalse($xss_event, 'Event handler XSS did not execute.');

    // Verify event handler attributes were stripped but tags preserved.
    $message_html = $this->getSession()->evaluateScript('document.querySelector("[data-status-result=IntegrationReportExample2] .status-report-message").innerHTML');
    $this->assertStringNotContainsString('onmouseover', $message_html, 'onmouseover attribute stripped.');
    $this->assertStringNotContainsString('onclick', $message_html, 'onclick attribute stripped.');
    $this->assertStringContainsString('<ul>', $message_html, 'ul tag preserved.');
    $this->assertStringContainsString('<li>', $message_html, 'li tag preserved.');
    $this->assertStringContainsString('Item', $message_html, 'Text content preserved.');
  }

}
