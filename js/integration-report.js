/**
 * @file
 * Integration Report response handlers.
 *
 * phpcs:disable Generic.PHP.UpperCaseConstant.Found.
 * @param {Object} $  - jQuery object.
 */

(function integrationReport($) {
  /**
   * Integration Report helpers.
   */
  Drupal.IntegrationReport = Drupal.IntegrationReport || {
    // Responses after this threshold are considered failed.
    FAILURE_THRESHOLD: 10000,

    /**
     * Update the status table with a status response.
     *
     * @param {boolean} passed     - Whether the status is a pass or fail.
     * @param {string}  className  - The name of the PHP class the status
     *                             response belongs to.
     * @param {string}  message    - The messages returned by the status
     *                             response class callback.
     * @param {number}  time       - The amount of milliseconds it took to
     *                             complete the callback.
     */
    statusReceived(passed, className, message, time) {
      const $statusRow = $(`[data-status-result="${className}"]`);
      const $statusDebug = $(`[data-debug-result="${className}"]`);
      let responseText = '';

      $statusRow.addClass('status-report-complete');

      if (time < this.FAILURE_THRESHOLD && passed) {
        $statusRow.removeClass('warning').addClass('ok');
        $statusDebug.removeClass('open');
        responseText = 'OK';
      } else if (!passed) {
        $statusRow.removeClass('warning').addClass('error');
        responseText = 'FAIL';
        $statusRow.addClass('open');
        $statusDebug.addClass('open');
      }

      responseText += ` (${time}ms)`;

      $statusRow.find('.status-report-response').html(responseText);
      $statusRow.find('.status-report-message').append(message);
      $statusRow.find('.ajax-progress').remove();
    },
  };

  /**
   * Respond to postMessages on the page.
   *
   * Only fire statusReceived if the message is of the 'IntegrationReport' type.
   */
  window.addEventListener(
    'message',
    function handleMessage(e) {
      if (e.data.type === 'IntegrationReportHandler') {
        Drupal.IntegrationReport.statusReceived(
          e.data.success,
          e.data.class,
          e.data.message,
          e.data.time,
        );
      }
    },
    false,
  );

  /**
   * User status behavior for opening and closing status messages.
   */
  Drupal.behaviors.integrationReport = {
    attach(context) {
      $(once('integration-report-open', '[data-status-result]', context)).click(
        function toggleOpen() {
          $(this).toggleClass('open');
        },
      );
    },
  };
})(jQuery);
