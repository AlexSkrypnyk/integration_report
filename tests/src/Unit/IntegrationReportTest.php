<?php

declare(strict_types=1);

namespace Drupal\Tests\integration_report\Unit;

use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\integration_report\IntegrationReportBase;
use Drupal\Tests\UnitTestCase;

/**
 * Class IntegrationReportTest.
 *
 * @covers \Drupal\integration_report\IntegrationReportBase
 * @covers \Drupal\integration_report\IntegrationReportHelperTrait
 *
 * @group integration_report
 */
class IntegrationReportTest extends UnitTestCase {

  /**
   * Test that constructor reads info and exposes getters.
   */
  public function testIntegrationReport(): void {
    $translation_manager = $this->createMock(TranslationInterface::class);
    $renderer = $this->createMock(RendererInterface::class);

    $integration_report = new TestIntegrationReportBase($translation_manager, $renderer);
    $this->assertSame('Test Integration Report Base Name', $integration_report->getName());
    $this->assertSame('Test Integration Report Base Description', $integration_report->getDescription());
    $this->assertNull($integration_report->getJs());
    $this->assertNull($integration_report->isSecureCallback());
    $this->assertTrue($integration_report->access());
    $this->assertTrue($integration_report->isUseCallback());
    $this->assertSame('', $integration_report->statusPage());
  }

  /**
   * Test that setters update the corresponding properties.
   *
   * @dataProvider dataProviderSetters
   */
  public function testSetters(string $setter, string $getter, mixed $value): void {
    $translation = $this->createMock(TranslationInterface::class);
    $renderer = $this->createMock(RendererInterface::class);
    $report = new TestIntegrationReportBase($translation, $renderer);

    $report->$setter($value);
    $this->assertSame($value, $report->$getter());
  }

  /**
   * Data provider for testSetters.
   *
   * @return array<string, array{string, string, mixed}>
   *   Each row: [setter method, getter method, value to set].
   */
  public static function dataProviderSetters(): array {
    return [
      'name string' => ['setName', 'getName', 'A New Name'],
      'description string' => ['setDescription', 'getDescription', 'A New Description'],
      'js path' => ['setJs', 'getJs', '/path/to/file.js'],
      'useCallback false' => ['setUseCallback', 'isUseCallback', FALSE],
      'useCallback true' => ['setUseCallback', 'isUseCallback', TRUE],
      'secureCallback true' => ['setSecureCallback', 'isSecureCallback', TRUE],
      'secureCallback false' => ['setSecureCallback', 'isSecureCallback', FALSE],
      'secureCallback null' => ['setSecureCallback', 'isSecureCallback', NULL],
      'access false' => ['setAccess', 'access', FALSE],
      'access true' => ['setAccess', 'access', TRUE],
    ];
  }

  /**
   * Test menuCallback produces the expected iframe HTML.
   */
  public function testMenuCallback(): void {
    $translation = $this->createMock(TranslationInterface::class);
    $renderer = $this->createMock(RendererInterface::class);
    $renderer->method('render')->willReturn(Markup::create('<ul><li>Hello</li></ul>'));

    $report = new TestIntegrationReportWithCallback($translation, $renderer);
    $html = $report->menuCallback();

    $this->assertStringContainsString('<!DOCTYPE html>', $html);
    $this->assertStringContainsString('parent.postMessage(', $html);
    $this->assertStringContainsString('window.location.origin', $html);
    $this->assertStringContainsString('"class":"TestIntegrationReportWithCallback"', $html);
    $this->assertStringContainsString('"type":"IntegrationReportHandler"', $html);
    $this->assertStringContainsString('"success":true', $html);
    $this->assertStringContainsString('"message":"', $html);
    $this->assertStringContainsString('Hello', $html);
  }

  /**
   * Test menuCallback when the callback returns no messages.
   */
  public function testMenuCallbackWithoutMessages(): void {
    $translation = $this->createMock(TranslationInterface::class);
    $renderer = $this->createMock(RendererInterface::class);
    $report = new TestIntegrationReportNoMessages($translation, $renderer);

    $html = $report->menuCallback();

    $this->assertStringContainsString('"message":""', $html);
    $this->assertStringContainsString('"success":true', $html);
    $this->assertStringContainsString('"class":"TestIntegrationReportNoMessages"', $html);
  }

  /**
   * Test that the default callback returns a placeholder failure payload.
   */
  public function testDefaultCallback(): void {
    $translation = $this->getStringTranslationStub();
    $renderer = $this->createMock(RendererInterface::class);
    $report = new TestIntegrationReportDefaults($translation, $renderer);

    $result = $report->callback();

    $this->assertArrayHasKey('success', $result);
    $this->assertArrayHasKey('messages', $result);
    $this->assertFalse($result['success']);
    $this->assertInstanceOf(TranslatableMarkup::class, $result['messages']);
    $this->assertStringContainsString('Specify a callback', (string) $result['messages']);
    $this->assertStringContainsString('TestIntegrationReportDefaults', (string) $result['messages']);
  }

  /**
   * Test that the default info returns placeholder markup.
   */
  public function testDefaultInfo(): void {
    $translation = $this->getStringTranslationStub();
    $renderer = $this->createMock(RendererInterface::class);
    $report = new TestIntegrationReportDefaults($translation, $renderer);

    $info = $report->info();

    $this->assertArrayHasKey('name', $info);
    $this->assertArrayHasKey('description', $info);
    $this->assertInstanceOf(TranslatableMarkup::class, $info['name']);
    $this->assertInstanceOf(TranslatableMarkup::class, $info['description']);
    $this->assertStringContainsString('Missing info hook', (string) $info['name']);
    $this->assertStringContainsString('TestIntegrationReportDefaults', (string) $info['description']);
  }

  /**
   * Test that an empty info array keeps the initial property values.
   */
  public function testEmptyInfo(): void {
    $translation = $this->createMock(TranslationInterface::class);
    $renderer = $this->createMock(RendererInterface::class);
    $report = new TestIntegrationReportEmptyInfo($translation, $renderer);

    $this->assertSame('', $report->getName());
    $this->assertSame('', $report->getDescription());
    $this->assertNull($report->getJs());
    $this->assertNull($report->isSecureCallback());
    $this->assertFalse($report->isUseCallback());
    $this->assertTrue($report->access());
  }

  /**
   * Test that getShortClassName resolves a short class name from any object.
   */
  public function testGetShortClassName(): void {
    $translation = $this->createMock(TranslationInterface::class);
    $renderer = $this->createMock(RendererInterface::class);
    $report = new TestIntegrationReportBase($translation, $renderer);

    $this->assertSame('TestIntegrationReportBase', TestIntegrationReportBase::getShortClassName($report));
    $this->assertSame('TestIntegrationReportBase', TestIntegrationReportBase::getShortClassName(TestIntegrationReportBase::class));
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

/**
 * A test class with a known callback payload used to assert menuCallback.
 */
class TestIntegrationReportWithCallback extends IntegrationReportBase {

  /**
   * {@inheritDoc}
   */
  public function info(): array {
    return [
      'name' => 'With Callback',
      'description' => 'With Callback Description',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function callback(): array {
    return [
      'success' => TRUE,
      'messages' => ['Hello'],
    ];
  }

}

/**
 * A test class whose callback returns no messages key.
 */
class TestIntegrationReportNoMessages extends IntegrationReportBase {

  /**
   * {@inheritDoc}
   */
  public function info(): array {
    return [
      'name' => 'No Messages',
      'description' => 'No Messages Description',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function callback(): array {
    return ['success' => TRUE];
  }

}

/**
 * A test class with no overrides; exercises base default callback and info.
 */
class TestIntegrationReportDefaults extends IntegrationReportBase {
}

/**
 * A test class whose info returns an empty array.
 */
class TestIntegrationReportEmptyInfo extends IntegrationReportBase {

  /**
   * {@inheritDoc}
   */
  public function info(): array {
    return [];
  }

}
