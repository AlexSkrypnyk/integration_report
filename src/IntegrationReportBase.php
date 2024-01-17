<?php

declare(strict_types = 1);

namespace Drupal\integration_report;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Class IntegrationReport.
 *
 * Extend this class in your custom implementation.
 */
abstract class IntegrationReportBase implements IntegrationReportInterface {

  use IntegrationReportHelperTrait;
  use StringTranslationTrait;

  /**
   * Name of the report, set by info().
   *
   * @var string|\Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected mixed $name = '';

  /**
   * Description of the report, set by info().
   *
   * @var string|\Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected mixed $description = '';

  /**
   * Javascript file for the report, set by info().
   *
   * @var string|null
   */
  protected ?string $js = NULL;

  /**
   * Whether to use the callback in the report, set by info().
   *
   * @var bool
   */
  protected bool $useCallback = FALSE;

  /**
   * Whether the status callback needs to be performed over https.
   *
   * If NULL, then the current protocol will be used.
   *
   * @var bool|null
   */
  protected ?bool $secureCallback = NULL;

  /**
   * Check whether the status check should be available in this environment.
   */
  protected bool $access = TRUE;

  /**
   * Class constructor.
   *
   * Extract status meta information and fill the class variables.
   *
   * @throws \ReflectionException
   */
  public function __construct(TranslationInterface $translation, RendererInterface $renderer) {
    $this->stringTranslation = $translation;
    $this->renderer = $renderer;

    $info = $this->info();
    if ($info) {
      $this->name = $info['name'] ?? '';
      $this->description = $info['description'] ?? '';
      $this->js = $info['js'] ?? NULL;
      $this->useCallback = $info['use_callback'] ?? TRUE;
      $this->secureCallback = $info['secure_callback'] ?? NULL;
      $this->access = $info['access'] ?? TRUE;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function setName(string|TranslatableMarkup $name): void {
    $this->name = $name;
  }

  /**
   * {@inheritDoc}
   */
  public function getName(): string|TranslatableMarkup {
    return $this->name;
  }

  /**
   * {@inheritDoc}
   */
  public function setJs(string $js_path): void {
    $this->js = $js_path;
  }

  /**
   * {@inheritDoc}
   */
  public function getJs(): ?string {
    return $this->js;
  }

  /**
   * {@inheritDoc}
   */
  public function setDescription(string|TranslatableMarkup $description): void {
    $this->description = $description;
  }

  /**
   * {@inheritDoc}
   */
  public function getDescription(): string|TranslatableMarkup {
    return $this->description;
  }

  /**
   * {@inheritDoc}
   */
  public function setSecureCallback(bool $secureCallback = NULL): void {
    $this->secureCallback = $secureCallback;
  }

  /**
   * {@inheritDoc}
   */
  public function isSecureCallback(): bool|NULL {
    return $this->secureCallback;
  }

  /**
   * {@inheritDoc}
   */
  public function setUseCallback(bool $useCallback): void {
    $this->useCallback = $useCallback;
  }

  /**
   * {@inheritDoc}
   */
  public function isUseCallback(): bool {
    return $this->useCallback;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccess(bool $access): void {
    $this->access = $access;
  }

  /**
   * {@inheritdoc}
   */
  public function access(): bool {
    return $this->access;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \ReflectionException
   * @throws \Exception
   *
   * @SuppressWarnings(PHPMD.ElseExpression)
   */
  public function menuCallback(): string {
    // Log the time in which the menu callback php task begun.
    $start_time = round(microtime(TRUE) * 1000);

    // Perform the report's callback function.
    $results = $this->callback();

    // Log the amount of time it took for the callback function to complete.
    $results['time'] = round(microtime(TRUE) * 1000) - $start_time;

    // Get the class name for the current class.
    $results['class'] = static::getShortClassName($this);

    // Add the type so that postMessages are distinguished.
    $results['type'] = 'IntegrationReportHandler';

    // Format any messages as an unordered list.
    if (isset($results['messages'])) {
      $element = [
        '#theme' => 'item_list',
        '#items' => $results['messages'],
        '#title' => '',
        '#type' => 'ul',
        '#attributes' => [],
      ];
      $results['message'] = $this->render($element);
    }
    else {
      $results['message'] = '';
    }

    // Encode the results as a json response and return in a postMessage.
    $encoded_js_result = Json::encode($results);

    return '<!DOCTYPE html><head><script type="text/javascript">parent.postMessage(' . $encoded_js_result . ', "*");</script></head><body></body></html>';
  }

  /**
   * {@inheritdoc}
   *
   * @throws \ReflectionException
   */
  public function callback(): array {
    return [
      'success' => FALSE,
      'messages' => $this->t('Specify a callback for status %class or use the "no_callback" flag in status info.', [
        '%class' => static::getShortClassName($this),
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @throws \ReflectionException
   */
  public function info(): array {
    return [
      'name' => $this->t('Missing info hook'),
      'description' => $this->t('Please specify an info hook for status %class.', [
        '%class' => static::getShortClassName($this),
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function statusPage(): string {
    return '';
  }

}
