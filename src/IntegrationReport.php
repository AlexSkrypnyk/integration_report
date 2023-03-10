<?php

namespace Drupal\integration_report;

use Drupal\Component\Serialization\Json;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class IntegrationReport.
 *
 * Extend this class in your custom implementation.
 */
abstract class IntegrationReport {

  use StringTranslationTrait;

  /**
   * Name of the report, set by info().
   *
   * @var string
   */
  public $name;

  /**
   * Description of the report, set by info().
   *
   * @var string
   */
  public $description;

  /**
   * Javascript file for the report, set by info().
   *
   * @var string
   */
  public $js;

  /**
   * Whether or not to use the callback in the report, set by info().
   *
   * @var bool
   */
  public $useCallback;

  /**
   * Whether the status callback needs to be performed over https.
   *
   * If NULL, then the current protocol will be used.
   *
   * @var bool|null
   */
  public $secureCallback;

  /**
   * Class constructor.
   *
   * Extract status meta information and fill the class variables.
   */
  public function __construct() {
    if ($info = $this->info()) {
      $this->name = $info['name'];
      $this->description = $info['description'];
      if (isset($info['js'])) {
        $this->js = $info['js'];
      }
      $this->useCallback = $info['use_callback'] ?: TRUE;
      $this->secureCallback = $info['secure_callback'] ?: NULL;
    }
  }

  /**
   * Retrieve a JavaScript parent post message script for a given status class.
   *
   * JavaScript is printed inside of an iFrame that is loaded on the status
   * page.
   * After the iFrame has loaded, the post message script is executed to the
   * parent window and the status is interpreted by the status table.
   *
   * @return string
   *   JavaScript markup that performs a postMessage to the parent window.
   */
  public function menuCallback() {
    // Log the time in which the menu callback php task begun.
    $start_time = round(microtime(TRUE) * 1000);

    // Perform the report's callback function.
    $results = $this->callback();

    // Log the amount of time it took for the callback function to complete.
    $results['time'] = round(microtime(TRUE) * 1000) - $start_time;

    // Get the class name for the current class.
    $results['class'] = IntegrationReportHelperTrait::getShortClassName($this);

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
      $results['message'] = IntegrationReportHelperTrait::render($element);
    }
    else {
      $results['message'] = '';
    }

    // Encode the results as a json response and return in a postMessage.
    $encoded_js_result = Json::encode($results);

    return '<script type="text/javascript">parent.postMessage(' . $encoded_js_result . ',"*");</script>';
  }

  /**
   * Check whether the status check should be available in this environment.
   *
   * @return bool
   *   Status check availability.
   */
  public function access() {
    $info = $this->info();
    if (isset($info['access']) && !$info['access']) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Info callback for injecting markup into status table suffix.
   *
   * @see integration_report.api.php
   */
  public function info() {
    return [
      'name' => $this->t('Missing info hook'),
      'description' => $this->t('Please specify an info hook for status %class.', [
        '%class' => IntegrationReportHelperTrait::getShortClassName($this),
      ]),
    ];
  }

  /**
   * Status page callback for injecting markup into status table suffix.
   *
   * @see integration_report.api.php
   */
  public function statusPage() {
    return '';
  }

  /**
   * Callback handler for performing status operations and returning messages.
   *
   * @see integration_report.api.php
   */
  public function callback() {
    return [
      'success' => FALSE,
      'messages' => $this->t('Specify a callback for status %class or use the "no_callback" flag in status info.', [
        '%class' => IntegrationReportHelperTrait::getShortClassName($this),
      ]),
    ];
  }

}
