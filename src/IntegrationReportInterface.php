<?php

declare(strict_types = 1);

namespace Drupal\integration_report;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Integration Report Interface.
 */
interface IntegrationReportInterface {

  /**
   * Status page callback for injecting markup into status table suffix.
   *
   * @see integration_report.api.php
   */
  public function statusPage(): string;

  /**
   * Callback handler for performing status operations and returning messages.
   *
   * @return array<mixed>
   *   Callback status message.
   *
   * @see integration_report.api.php
   */
  public function callback(): array;

  /**
   * Info callback for injecting markup into status table suffix.
   *
   * @return array<mixed>
   *   Info.
   *
   * @see integration_report.api.php
   */
  public function info(): array;

  /**
   * Retrieve a JavaScript parent post message script for a given status class.
   *
   * JavaScript is printed inside an iFrame that is loaded on the status
   * page.
   * After the iFrame has loaded, the post message script is executed to the
   * parent window and the status is interpreted by the status table.
   *
   * @return string
   *   JavaScript markup that performs a postMessage to the parent window.
   */
  public function menuCallback(): string;

  /**
   * Set name.
   */
  public function setName(string|TranslatableMarkup $name): void;

  /**
   * Get name.
   */
  public function getName(): string|TranslatableMarkup;

  /**
   * Set description.
   */
  public function setDescription(string|TranslatableMarkup $description): void;

  /**
   * Get description.
   */
  public function getDescription(): string|TranslatableMarkup;

  /**
   * Set js.
   */
  public function setJs(string $js_path): void;

  /**
   * Get js.
   */
  public function getJs(): ?string;

  /**
   * Set useCallback.
   */
  public function setUseCallback(bool $useCallback): void;

  /**
   * Get useCallback.
   */
  public function isUseCallback(): bool;

  /**
   * Set SecureCallback.
   */
  public function setSecureCallback(bool $secureCallback = NULL): void;

  /**
   * Get SecureCallback.
   */
  public function isSecureCallback(): bool|NULL;

  /**
   * Set access.
   */
  public function setAccess(bool $access): void;

  /**
   * Check whether the status check should be available in this environment.
   *
   * @return bool
   *   Status check availability.
   */
  public function access(): bool;

}
