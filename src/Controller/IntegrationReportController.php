<?php

namespace Drupal\integration_report\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\integration_report\IntegrationReportHelperTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class IntegrationReportController.
 *
 * @package Drupal\dblog\Controller
 */
class IntegrationReportController extends ControllerBase {

  use IntegrationReportHelperTrait;

  /**
   * The report manager.
   *
   * @var \Drupal\integration_report\IntegrationReportManager
   */
  protected $reportManager;

  /**
   * Displays a listing of database log messages.
   *
   * Messages are truncated at 56 chars.
   * Full-length messages can be viewed on the message details page.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function overview() {
    $table = [
      '#theme' => 'table',
      '#header' => ['', 'Status type', 'Result'],
      '#rows' => [],
      '#empty' => t('No reports found.'),
      '#suffix' => '',
      '#attributes' => [
        'class' => [
          'integration-report-table',
        ],
      ],
      '#attached' => [
        'library' => [
          'integration_report/integration_report',
        ],
      ],
    ];

    $reports = $this->getReportManager()->getReports();

    foreach ($reports as $report) {
      $class_name = IntegrationReportHelperTrait::getShortClassName($report);

      // Skip processing reports which aren't available for executing.
      if (!$report->access()) {
        continue;
      }

      // Attach the JavaScript file defined by the report class as an inline
      // javascript at the end of the table between Drupal settings and
      // library scripts and the footer of the page.
      if ($report->js) {
        $inline_js = [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#value' => '',
          '#attributes' => [
            'type' => 'text/javascript',
            'src' => $report->js,
          ],
        ];
        $table['#suffix'] .= IntegrationReportHelperTrait::render($inline_js);
      }

      // Attach any JavaScript firing callbacks in iFrames underneath the table.
      if ($report->useCallback) {
        $url_attributes = [
          'absolute' => TRUE,
          'https' => $report->secureCallback ? TRUE : FALSE,
        ];
        $iframe_url = Url::fromUserInput('/admin/reports/integrations/' . $class_name, $url_attributes);
        $table['#suffix'] .= new FormattableMarkup('<div class="integration-report-debug-result" data-debug-result="@class_name"><strong>Content debug for @report_name</strong><br/><iframe src="@iframe_url"></iframe></div>', [
          '@class_name' => $class_name,
          '@report_name' => $report->name,
          '@iframe_url' => $iframe_url->toString(),
        ]);
      }

      // Any hooks or additional page markup required by the report for the
      // status page get invoked here and added underneath the table.
      if ($output = $report->statusPage()) {
        $table['#suffix'] .= $output;
      }

      // Convert suffix to the proper markup.
      $table['#suffix'] = new FormattableMarkup($table['#suffix'], []);

      // Add the table row for the report.
      $table['#rows'][] = [
        'data' => [
          // Column 1: Throbber.
          [
            'data' => new FormattableMarkup('<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div></div>', []),
            'class' => ['status-report-icon'],
          ],
          // Column 2: Report name and description.
          [
            'data' => new FormattableMarkup('<strong>@name</strong><br />@description', [
              '@name' => $report->name,
              '@description' => $report->description,
            ]),
            'class' => ['status-report-message'],
          ],
          // Column 3: Response area.
          [
            'data' => t('Loading...'),
            'class' => ['status-report-response'],
          ],
        ],
        // Add the class name for the handler in the data-status-result attribute
        // for the row for referencing by JavaScript.
        'data-status-result' => $class_name,
        // By default all status rows start their life in amber mode.
        'class' => [
          'warning',
        ],
      ];
    }
    return $table;
  }

  /**
   * Callback triggered by JavaScript from the iFrame.
   *
   * @param string $report_class
   *   The report class short name.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function jsCallback($report_class, Request $request) {
    // Sanitise class name.
    $class = Html::escape($report_class);

    $report = $this->getReportManager()->findReport($class);
    if ($report) {
      $headers['Cache-Control'] = 'no-cache';
      $headers['Pragma'] = 'no-cache';
      $headers['Expires'] = '-1';
      $result = $report->menuCallback();
      $response = new Response($result, 200, $headers);
      return $response;
    }

    $this->getLogger('integration_report')->warning(t('Unable to instantiate status class @class in JS callback.', ['@class' => $report_class]));
  }

  /**
   * Get report manager.
   *
   * @return \Drupal\integration_report\IntegrationReportManager
   *   The report manager.
   */
  protected function getReportManager() {
    if (!isset($this->reportManager)) {
      $this->reportManager = \Drupal::getContainer()->get('integration_report.report_manager');
    }
    return $this->reportManager;
  }

}
