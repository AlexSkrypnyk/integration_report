<?php

namespace Drupal\integration_report_example;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\integration_report\IntegrationReport;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class IntegrationReportExample1.
 *
 * Example for the IntegrationReport module.
 */
class IntegrationReportExample1 extends IntegrationReport {

  use StringTranslationTrait;

  /**
   * Define the properties of the status.
   *
   * Required for each report class.
   *
   * @return array
   *   An array defining the status with the keys:
   *   - 'name' (string, required)
   *       The name of the status being checked.
   *   - 'description' (string, required)
   *       The description of the status being checked.
   *   - 'js' (string, optional)
   *       A javascript helper file to include on the status page.
   *   - 'secure_callback' (bool, optional - default: FALSE)
   *       Whether the status callback needs to be performed over https.
   *   - 'use_callback' (bool, optional - default: TRUE)
   *       Whether to use the standard iFrame callback method.
   *   - 'access' (bool, optional - default: TRUE)
   *       Whether the status check is available based on additional custom
   *       conditions such as environment or user permission.
   */
  public function info() {
    return [
      'name' => $this->t('Report example 1'),
      'description' => $this->t('Report example 1 description'),
    ];
  }

  /**
   * The callback for running checks and returning responses.
   *
   * Required for each report unless 'use_callback' is set to FALSE in
   * the info declaration.
   *
   * @return array
   *   - 'success' (bool, required)
   *       Whether the status check was a success or failure.
   *   - 'messages' (array, required)
   *       A list of string messages to be added to the response information
   *       for the test.
   */
  public function callback() {
    // Perform a request on an example url.
    // This is where you would call your own API client and return a result
    // based on received response.
    $url = 'http://example.com';
    // @codingStandardsIgnoreStart
    $response = \Drupal::httpClient()->get($url, ['headers' => ['Accept' => 'text/plain']]);
    // @codingStandardsIgnoreEnd

    // Check for a 200 response and the word 'domain' in the response.
    if ($response->getStatusCode() == Response::HTTP_OK && strpos($response->getBody(), 'domain') !== FALSE) {
      $success = TRUE;
      $messages[] = $this->t('@url was retrieved successfully.', [
        '@url' => $url,
      ]);
    }
    else {
      $success = FALSE;
      $messages[] = $this->t('@url was not retrieved successfully.', [
        '@url' => $url,
      ]);
    }

    return [
      'success' => $success,
      'messages' => $messages,
    ];
  }

  /**
   * Add any extra markup or javascript to the footer of the status table.
   *
   * Optional, not required for most status checks.
   *
   * @return string
   *   Markup to be placed in the footer of the table.
   */
  public function statusPage() {
    return '<div class="extra-status-markup">Optional footer markup from the Report example 1</div>';
  }

}
