<?php

declare(strict_types = 1);

namespace Drupal\integration_report\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\integration_report\IntegrationReportHelperTrait;
use Drupal\integration_report\IntegrationReportManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class IntegrationReportController.
 *
 * Controller for integration report.
 *
 * @package Drupal\dblog\Controller
 *
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class IntegrationReportController extends ControllerBase {

  use IntegrationReportHelperTrait;
  use StringTranslationTrait;

  /**
   * The report manager.
   *
   * @var \Drupal\integration_report\IntegrationReportManager
   */
  protected IntegrationReportManager $reportManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): IntegrationReportController {
    // @phpstan-ignore-next-line
    return new static(
      $container->get('integration_report.report_manager')
    );
  }

  /**
   * IntegrationReportController constructor.
   *
   * @param \Drupal\integration_report\IntegrationReportManager $report_manager
   *   The report manager.
   */
  public function __construct(IntegrationReportManager $report_manager) {
    $this->reportManager = $report_manager;
  }

  /**
   * Displays a listing of database log messages.
   *
   * Messages are truncated at 56 chars.
   * Full-length messages can be viewed on the message details page.
   *
   * @return array<mixed>
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   *
   * @throws \ReflectionException
   */
  public function overview(): array {
    $table = [
      '#theme' => 'table',
      '#header' => ['', 'Status type', 'Result'],
      '#rows' => [],
      '#empty' => $this->t('No reports found.'),
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

    $reports = $this->reportManager->getReports();

    foreach ($reports as $report) {
      $class_name = static::getShortClassName($report);

      // Skip processing reports which aren't available for executing.
      if (!$report->access()) {
        continue;
      }

      // Attach the JavaScript file defined by the report class as an inline
      // javascript at the end of the table between Drupal settings and
      // library scripts and the footer of the page.
      if ($report->getJs()) {
        $inline_js = [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#value' => '',
          '#attributes' => [
            'type' => 'text/javascript',
            'src' => $report->getJs(),
          ],
        ];
        $table['#suffix'] .= static::render($inline_js);
      }

      // Attach any JavaScript firing callbacks in iFrames underneath the table.
      if ($report->isUseCallback()) {
        $url_attributes = [
          'absolute' => TRUE,
          'https' => $report->isSecureCallback(),
        ];
        $iframe_url = Url::fromUserInput('/admin/reports/integrations/' . $class_name, $url_attributes);
        $table['#suffix'] .= new FormattableMarkup('<div class="integration-report-debug-result" data-debug-result="@class_name"><strong>Content debug for @report_name</strong><br/><iframe src="@iframe_url"></iframe></div>', [
          '@class_name' => $class_name,
          '@report_name' => $report->getName(),
          '@iframe_url' => $iframe_url->toString(),
        ]);
      }

      // Any hooks or additional page markup required by the report for the
      // status page get invoked here and added underneath the table.
      $output = $report->statusPage();
      if ($output) {
        $table['#suffix'] .= $output;
      }

      // Convert suffix to the proper markup.
      $table['#suffix'] = new FormattableMarkup((string) $table['#suffix'], []);

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
              '@name' => $report->getName(),
              '@description' => $report->getDescription(),
            ]),
            'class' => ['status-report-message'],
          ],
          // Column 3: Response area.
          [
            'data' => $this->t('Loading...'),
            'class' => ['status-report-response'],
          ],
        ],
        // Add the class name for the handler in the data-status-result
        // attribute for the row for referencing by JavaScript.
        'data-status-result' => $class_name,
        // By default, all status rows start their life in amber mode.
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
   *   The response object.
   *
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  public function jsCallback($report_class, Request $request): Response {
    // Sanitise class name.
    $class = Html::escape($report_class);

    $report = $this->reportManager->findReport($class);
    $headers = [];
    $headers['Cache-Control'] = 'no-cache';
    $headers['Pragma'] = 'no-cache';
    $headers['Expires'] = '-1';
    if ($report) {
      $result = $report->menuCallback();

      return new Response($result, 200, $headers);
    }

    $warning = sprintf('Unable to instantiate status class %s in JS callback.', $report_class);
    $this->getLogger('integration_report')->warning($warning);

    return new Response($warning, 400, $headers);
  }

}
