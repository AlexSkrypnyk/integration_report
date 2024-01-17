<?php

declare(strict_types = 1);

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
  protected ?RendererInterface $renderer = NULL;

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
   * @return \Drupal\Component\Render\MarkupInterface
   *   Rendered element as a string.
   *
   * @throws \Exception
   */
  public function render(mixed $element): MarkupInterface {
    if (!$this->renderer) {
      return \Drupal::service('renderer')->render($element);
    }

    return $this->renderer->render($element);
  }

}
