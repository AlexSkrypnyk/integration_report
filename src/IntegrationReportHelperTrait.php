<?php

declare(strict_types=1);

namespace Drupal\integration_report;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Trait IntegrationReportHelperTrait.
 *
 * Utilities for the integration report.
 *
 * @package Drupal\integration_report
 */
trait IntegrationReportHelperTrait {

  /**
   * Renderer service.
   */
  protected RendererInterface $renderer;

  /**
   * Get short class name from the namespaced class.
   *
   * @param object|class-string $class
   *   Class name prefixed by a namespace.
   *
   * @return string
   *   Short class name.
   *
   * @throws \ReflectionException
   */
  public static function getShortClassName(object|string $class): string {
    return (new \ReflectionClass($class))->getShortName();
  }

  /**
   * Render element.
   *
   * @param mixed $element
   *   Element to render.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   Rendered element.
   *
   * @throws \Exception
   */
  public function render(mixed $element): MarkupInterface|string {
    return $this->renderer->render($element);
  }

}
