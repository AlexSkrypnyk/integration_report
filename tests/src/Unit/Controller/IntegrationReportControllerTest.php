<?php

declare(strict_types=1);

namespace Drupal\Tests\integration_report\Unit\Controller;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\RendererInterface;
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

    $container = new ContainerBuilder();
    $container->set('logger.factory', $logger_factory);
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
    $renderer = $this->createMock(RendererInterface::class);

    $container = $this->createMock(ContainerInterface::class);
    $container->method('get')->willReturnCallback(function (string $id) use ($manager, $renderer) {
      return match ($id) {
        'integration_report.report_manager' => $manager,
        'renderer' => $renderer,
        default => NULL,
      };
    });

    $controller = IntegrationReportController::create($container);
    $this->assertInstanceOf(IntegrationReportController::class, $controller);
  }

}
