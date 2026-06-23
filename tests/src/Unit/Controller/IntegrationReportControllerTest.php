<?php

declare(strict_types=1);

namespace Drupal\Tests\integration_report\Unit\Controller;

use PHPUnit\Framework\Attributes\Group;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Drupal\integration_report\Controller\IntegrationReportController;
use Drupal\integration_report\IntegrationReportInterface;
use Drupal\integration_report\IntegrationReportManager;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests for the IntegrationReportController jsCallback and create factory.
 *
 * @covers \Drupal\integration_report\Controller\IntegrationReportController
 *
 * @group integration_report
 */
#[Group('integration_report')]
class IntegrationReportControllerTest extends UnitTestCase {

  /**
   * Mock logger captured by the test container.
   */
  protected LoggerInterface&MockObject $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->logger = $this->createMock(LoggerInterface::class);
    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->method('get')->willReturn($this->logger);

    $path_validator = $this->createMock(PathValidatorInterface::class);
    $path_validator->method('getUrlIfValidWithoutAccessCheck')->willReturn(FALSE);

    $unrouted_url_assembler = $this->createMock(UnroutedUrlAssemblerInterface::class);
    $unrouted_url_assembler->method('assemble')->willReturnCallback(fn(string $uri): string => '/' . str_replace(['internal:/', 'base:'], '', $uri));

    $container = new ContainerBuilder();
    $container->set('logger.factory', $logger_factory);
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('url_generator', $this->createMock(UrlGeneratorInterface::class));
    $container->set('path.validator', $path_validator);
    $container->set('unrouted_url_assembler', $unrouted_url_assembler);
    \Drupal::setContainer($container);
  }

  /**
   * Test that jsCallback returns 200 with the report markup on success.
   */
  public function testJsCallbackSuccess(): void {
    $report = $this->createMock(IntegrationReportInterface::class);
    $report->method('menuCallback')->willReturn('<html>payload</html>');

    $manager = $this->createMock(IntegrationReportManager::class);
    $manager->expects($this->once())
      ->method('findReport')
      ->with('TestClass')
      ->willReturn($report);

    $controller = new IntegrationReportController($manager, $this->createMock(RendererInterface::class));
    $response = $controller->jsCallback('TestClass', Request::create('/'));

    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('<html>payload</html>', $response->getContent());
    $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
    $this->assertSame('no-cache', $response->headers->get('Pragma'));
    $this->assertSame('-1', $response->headers->get('Expires'));
  }

  /**
   * Test that jsCallback returns 400 and logs a warning on missing report.
   */
  public function testJsCallbackWarning(): void {
    $manager = $this->createMock(IntegrationReportManager::class);
    $manager->method('findReport')->willReturn(NULL);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with($this->stringContains('Unable to instantiate status class Missing'));

    $controller = new IntegrationReportController($manager, $this->createMock(RendererInterface::class));
    $response = $controller->jsCallback('Missing', Request::create('/'));

    $this->assertSame(400, $response->getStatusCode());
    $this->assertStringContainsString('Missing', (string) $response->getContent());
    $this->assertStringContainsString('no-cache', (string) $response->headers->get('Cache-Control'));
  }

  /**
   * Test that jsCallback sanitises the class name before lookup.
   */
  public function testJsCallbackEscapesClassName(): void {
    $manager = $this->createMock(IntegrationReportManager::class);
    $manager->expects($this->once())
      ->method('findReport')
      ->with($this->logicalAnd(
        $this->logicalNot($this->stringContains('<script>')),
        $this->logicalNot($this->stringContains('</script>')),
      ))
      ->willReturn(NULL);

    $controller = new IntegrationReportController($manager, $this->createMock(RendererInterface::class));
    $controller->jsCallback('<script>evil</script>', Request::create('/'));
  }

  /**
   * Test the static factory builds the controller from container services.
   */
  public function testCreate(): void {
    $manager = $this->createMock(IntegrationReportManager::class);
    $manager->method('getReports')->willReturn([]);
    $renderer = $this->createMock(RendererInterface::class);

    $container = $this->createMock(ContainerInterface::class);
    $container->method('get')->willReturnCallback(fn(string $id): ?MockObject => match ($id) {
      'integration_report.report_manager' => $manager,
      'renderer' => $renderer,
      default => NULL,
    }
);

    $controller = IntegrationReportController::create($container);
    $table = $controller->overview();
    $this->assertSame('table', $table['#theme']);
  }

  /**
   * Test overview returns the table skeleton when there are no reports.
   */
  public function testOverviewEmpty(): void {
    $manager = $this->createMock(IntegrationReportManager::class);
    $manager->method('getReports')->willReturn([]);

    $controller = new IntegrationReportController($manager, $this->createMock(RendererInterface::class));
    $table = $controller->overview();

    $this->assertSame('table', $table['#theme']);
    $this->assertSame(['', 'Status type', 'Result'], $table['#header']);
    $this->assertSame([], $table['#rows']);
    $this->assertSame('', $table['#suffix']);
    $this->assertSame(['integration-report-table'], $table['#attributes']['class']);
    $this->assertSame(['integration_report/integration_report'], $table['#attached']['library']);
    $this->assertNotEmpty((string) $table['#empty']);
  }

  /**
   * Test overview skips reports whose access() returns FALSE.
   */
  public function testOverviewSkipsInaccessibleReports(): void {
    $allowed = $this->createMock(IntegrationReportInterface::class);
    $allowed->method('access')->willReturn(TRUE);
    $allowed->method('getJs')->willReturn(NULL);
    $allowed->method('isUseCallback')->willReturn(FALSE);
    $allowed->method('statusPage')->willReturn('');
    $allowed->method('getName')->willReturn('Allowed');
    $allowed->method('getDescription')->willReturn('Allowed Description');

    $denied = $this->createMock(IntegrationReportInterface::class);
    $denied->method('access')->willReturn(FALSE);
    $denied->expects($this->never())->method('getJs');
    $denied->expects($this->never())->method('isUseCallback');
    $denied->expects($this->never())->method('statusPage');

    $manager = $this->createMock(IntegrationReportManager::class);
    $manager->method('getReports')->willReturn([$allowed, $denied]);

    $controller = new IntegrationReportController($manager, $this->createMock(RendererInterface::class));
    $table = $controller->overview();

    $this->assertCount(1, $table['#rows']);
  }

  /**
   * Test overview appends inline JS when a report exposes a js path.
   */
  public function testOverviewAttachesJsScript(): void {
    $report = $this->createMock(IntegrationReportInterface::class);
    $report->method('access')->willReturn(TRUE);
    $report->method('getJs')->willReturn('/modules/example/js/example.js');
    $report->method('isUseCallback')->willReturn(FALSE);
    $report->method('statusPage')->willReturn('');
    $report->method('getName')->willReturn('JS Report');
    $report->method('getDescription')->willReturn('Has JS');

    $manager = $this->createMock(IntegrationReportManager::class);
    $manager->method('getReports')->willReturn([$report]);

    $renderer = $this->createMock(RendererInterface::class);
    $renderer->method('render')->willReturn(Markup::create('<script src="/modules/example/js/example.js"></script>'));

    $controller = new IntegrationReportController($manager, $renderer);
    $table = $controller->overview();

    $this->assertStringContainsString('<script', (string) $table['#suffix']);
    $this->assertStringContainsString('/modules/example/js/example.js', (string) $table['#suffix']);
  }

  /**
   * Test overview appends an iframe when a report uses the callback flow.
   */
  public function testOverviewAttachesIframeWhenCallback(): void {
    $report = $this->createMock(IntegrationReportInterface::class);
    $report->method('access')->willReturn(TRUE);
    $report->method('getJs')->willReturn(NULL);
    $report->method('isUseCallback')->willReturn(TRUE);
    $report->method('isSecureCallback')->willReturn(FALSE);
    $report->method('statusPage')->willReturn('');
    $report->method('getName')->willReturn('Iframe Report');
    $report->method('getDescription')->willReturn('Has iframe');

    $manager = $this->createMock(IntegrationReportManager::class);
    $manager->method('getReports')->willReturn([$report]);

    $controller = new IntegrationReportController($manager, $this->createMock(RendererInterface::class));
    $table = $controller->overview();

    $suffix = (string) $table['#suffix'];
    $this->assertStringContainsString('<iframe', $suffix);
    $this->assertStringContainsString('integration-report-debug-result', $suffix);
    $this->assertStringContainsString('/admin/reports/integrations/', $suffix);
  }

  /**
   * Test overview appends report statusPage() markup to the table suffix.
   */
  public function testOverviewAppendsStatusPageMarkup(): void {
    $report = $this->createMock(IntegrationReportInterface::class);
    $report->method('access')->willReturn(TRUE);
    $report->method('getJs')->willReturn(NULL);
    $report->method('isUseCallback')->willReturn(FALSE);
    $report->method('statusPage')->willReturn('<div class="custom-marker">extra</div>');
    $report->method('getName')->willReturn('Status Page Report');
    $report->method('getDescription')->willReturn('Has status page');

    $manager = $this->createMock(IntegrationReportManager::class);
    $manager->method('getReports')->willReturn([$report]);

    $controller = new IntegrationReportController($manager, $this->createMock(RendererInterface::class));
    $table = $controller->overview();

    $this->assertStringContainsString('custom-marker', (string) $table['#suffix']);
    $this->assertStringContainsString('extra', (string) $table['#suffix']);
  }

  /**
   * Test overview builds a row with the expected class name and markers.
   */
  public function testOverviewBuildsRowStructure(): void {
    $report = $this->createMock(IntegrationReportInterface::class);
    $report->method('access')->willReturn(TRUE);
    $report->method('getJs')->willReturn(NULL);
    $report->method('isUseCallback')->willReturn(FALSE);
    $report->method('statusPage')->willReturn('');
    $report->method('getName')->willReturn('Row Report');
    $report->method('getDescription')->willReturn('Row Description');

    $manager = $this->createMock(IntegrationReportManager::class);
    $manager->method('getReports')->willReturn([$report]);

    $controller = new IntegrationReportController($manager, $this->createMock(RendererInterface::class));
    $table = $controller->overview();

    $this->assertCount(1, $table['#rows']);
    $row = $table['#rows'][0];
    $this->assertSame(['warning'], $row['class']);
    $short_name = (new \ReflectionClass($report))->getShortName();
    $this->assertSame($short_name, $row['data-status-result']);
    $this->assertCount(3, $row['data']);
    $this->assertStringContainsString('ajax-progress-throbber', (string) $row['data'][0]['data']);
    $this->assertStringContainsString('Row Report', (string) $row['data'][1]['data']);
    $this->assertStringContainsString('Row Description', (string) $row['data'][1]['data']);
    $this->assertNotEmpty((string) $row['data'][2]['data']);
  }

}
