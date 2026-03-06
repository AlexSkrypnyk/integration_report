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

    // Tags allowed through sanitization.
    ALLOWED_TAGS: [
      'ul',
      'ol',
      'li',
      'strong',
      'em',
      'b',
      'i',
      'br',
      'div',
      'span',
      'p',
      'a',
      'dl',
      'dt',
      'dd',
      'h3',
      'h4',
      'h5',
      'h6',
    ],

    // Attributes allowed on specific tags.
    ALLOWED_ATTRS: {
      a: ['href', 'title'],
      span: ['class'],
      div: ['class'],
      li: ['class'],
      ul: ['class'],
      ol: ['class'],
    },

    /**
     * Sanitize HTML string by removing dangerous elements and attributes.
     *
     * Preserves safe HTML tags (lists, emphasis, links) while stripping
     * scripts, event handlers, and other XSS vectors.
     *
     * @param {string} html  - The HTML string to sanitize.
     * @return {string} The sanitized HTML string.
     */
    sanitizeHtml(html) {
      const container = document.createElement('div');
      container.innerHTML = String(html);

      const allowedTags = this.ALLOWED_TAGS;
      const allowedAttrs = this.ALLOWED_ATTRS;

      function clean(node) {
        const children = Array.from(node.childNodes);
        for (let i = 0; i < children.length; i++) {
          const child = children[i];

          if (child.nodeType === Node.TEXT_NODE) {
            // Keep text nodes as-is.
          } else if (child.nodeType !== Node.ELEMENT_NODE) {
            // Remove non-element, non-text nodes (comments, etc.).
            node.removeChild(child);
          } else {
            const tag = child.nodeName.toLowerCase();

            if (allowedTags.indexOf(tag) === -1) {
              // Remove disallowed tags entirely (including their children for
              // dangerous tags like script/style).
              const dangerous = [
                'script',
                'style',
                'iframe',
                'object',
                'embed',
                'form',
                'input',
                'textarea',
                'select',
                'button',
              ];
              if (dangerous.indexOf(tag) !== -1) {
                node.removeChild(child);
              } else {
                // For non-dangerous but unallowed tags, keep text content.
                while (child.firstChild) {
                  node.insertBefore(child.firstChild, child);
                }
                node.removeChild(child);
              }
              // Re-process since DOM changed.
              clean(node);
              return;
            }

            // Remove all attributes except those explicitly allowed.
            const tagAllowed = allowedAttrs[tag] || [];
            const attrs = Array.from(child.attributes);
            for (let j = 0; j < attrs.length; j++) {
              if (tagAllowed.indexOf(attrs[j].name) === -1) {
                child.removeAttribute(attrs[j].name);
              }
            }

            // Sanitize href to prevent dangerous URL schemes.
            if (child.hasAttribute('href')) {
              const href = child.getAttribute('href').trim().toLowerCase();
              if (
                href.startsWith('data:') ||
                href.startsWith('vbscript:') ||
                /^\s*javascript\s*:/i.test(child.getAttribute('href'))
              ) {
                child.removeAttribute('href');
              }
            }

            // Recurse into children.
            clean(child);
          }
        }
      }

      clean(container);

      return container.innerHTML;
    },

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
      const safeClassName = Drupal.checkPlain(String(className));
      const $statusRow = $(`[data-status-result="${safeClassName}"]`);
      const $statusDebug = $(`[data-debug-result="${safeClassName}"]`);
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

      responseText += ` (${Number(time)}ms)`;

      $statusRow.find('.status-report-response').text(responseText);
      $statusRow
        .find('.status-report-message')
        .append(this.sanitizeHtml(message));
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
      if (e.origin !== window.location.origin) {
        return;
      }
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
