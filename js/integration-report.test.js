/**
 * @file
 * Unit tests for Drupal.IntegrationReport in integration-report.js.
 *
 * Run with `ahoy test-js` or `npm test` from the build/ directory.
 */

/* eslint-disable no-undef, func-names, prefer-arrow-callback */

describe('Drupal.IntegrationReport', function () {
  beforeAll(function () {
    global.Drupal = {
      checkPlain(value) {
        return String(value).replace(/[&<>"']/g, function (c) {
          return `&#${c.charCodeAt(0)};`;
        });
      },
      behaviors: {},
    };
    global.once = jest.fn(function () {
      return [];
    });

    require('./integration-report.js');
  });

  beforeEach(function () {
    document.body.innerHTML = '';
  });

  describe('sanitizeHtml()', function () {
    test('preserves safe inline and list tags', function () {
      const out = Drupal.IntegrationReport.sanitizeHtml(
        '<ul><li><strong>safe</strong> <em>text</em></li></ul>',
      );
      expect(out).toContain('<ul>');
      expect(out).toContain('<li>');
      expect(out).toContain('<strong>');
      expect(out).toContain('<em>');
      expect(out).toContain('safe');
      expect(out).toContain('text');
    });

    test('strips script tags entirely including their contents', function () {
      const out = Drupal.IntegrationReport.sanitizeHtml(
        '<p>ok</p><script>window.x = true;</script>',
      );
      expect(out).not.toContain('<script');
      expect(out).not.toContain('window.x = true');
    });

    test('strips iframe, style, object, and form elements', function () {
      ['iframe', 'style', 'object', 'embed', 'form', 'input'].forEach(
        function (tag) {
          const out = Drupal.IntegrationReport.sanitizeHtml(
            `<${tag}>content</${tag}>`,
          );
          expect(out).not.toContain(`<${tag}`);
        },
      );
    });

    test('strips img tag with onerror handler', function () {
      const out = Drupal.IntegrationReport.sanitizeHtml(
        '<img src="x" onerror="window.xss=true">',
      );
      expect(out).not.toContain('<img');
      expect(out).not.toContain('onerror');
    });

    test('strips event handler attributes from allowed tags', function () {
      const out = Drupal.IntegrationReport.sanitizeHtml(
        '<ul onmouseover="x()"><li onclick="y()">item</li></ul>',
      );
      expect(out).not.toContain('onmouseover');
      expect(out).not.toContain('onclick');
      expect(out).toContain('<ul>');
      expect(out).toContain('<li>');
      expect(out).toContain('item');
    });

    test('strips javascript: href', function () {
      const out = Drupal.IntegrationReport.sanitizeHtml(
        '<a href="javascript:alert(1)">link</a>',
      );
      expect(out).not.toMatch(/href="javascript:/i);
    });

    test('strips javascript: href with whitespace and casing variations', function () {
      const out = Drupal.IntegrationReport.sanitizeHtml(
        '<a href="  JavaScript:alert(1)">link</a>',
      );
      expect(out).not.toMatch(/javascript:/i);
    });

    test('strips data: href', function () {
      const out = Drupal.IntegrationReport.sanitizeHtml(
        '<a href="data:text/html,<script>evil</script>">link</a>',
      );
      expect(out).not.toMatch(/href="data:/i);
    });

    test('strips vbscript: href', function () {
      const out = Drupal.IntegrationReport.sanitizeHtml(
        '<a href="vbscript:msgbox(1)">link</a>',
      );
      expect(out).not.toMatch(/href="vbscript:/i);
    });

    test('preserves a safe relative href', function () {
      const out = Drupal.IntegrationReport.sanitizeHtml(
        '<a href="/safe/path" title="t">link</a>',
      );
      expect(out).toContain('/safe/path');
      expect(out).toContain('title="t"');
    });

    test('preserves text content of disallowed but non-dangerous tags', function () {
      const out = Drupal.IntegrationReport.sanitizeHtml(
        '<section>hello <article>world</article></section>',
      );
      expect(out).not.toContain('<section');
      expect(out).not.toContain('<article');
      expect(out).toContain('hello');
      expect(out).toContain('world');
    });

    test('coerces non-string input', function () {
      expect(Drupal.IntegrationReport.sanitizeHtml(123)).toBe('123');
      expect(Drupal.IntegrationReport.sanitizeHtml(null)).toBe('null');
    });
  });

  describe('statusReceived()', function () {
    function setupRow(className, withDebug) {
      const debugMarkup = withDebug
        ? `<div data-debug-result="${className}" class="open"></div>`
        : '';
      document.body.innerHTML = `
        <div data-status-result="${className}" class="warning">
          <div class="ajax-progress"><div class="throbber"></div></div>
          <div class="status-report-message"></div>
          <div class="status-report-response">Loading...</div>
        </div>
        ${debugMarkup}
      `;
    }

    test('marks row as ok when passed and time below threshold', function () {
      setupRow('Test', true);
      Drupal.IntegrationReport.statusReceived(true, 'Test', '<p>ok</p>', 120);

      const row = document.querySelector('[data-status-result="Test"]');
      expect(row.classList.contains('ok')).toBe(true);
      expect(row.classList.contains('warning')).toBe(false);
      expect(row.classList.contains('status-report-complete')).toBe(true);
      expect(
        row.querySelector('.status-report-response').textContent,
      ).toBe('OK (120ms)');
    });

    test('closes the debug section on success', function () {
      setupRow('Test', true);
      Drupal.IntegrationReport.statusReceived(true, 'Test', '', 50);

      const debug = document.querySelector('[data-debug-result="Test"]');
      expect(debug.classList.contains('open')).toBe(false);
    });

    test('marks row as error and opens it on failure', function () {
      setupRow('Test', true);
      const debug = document.querySelector('[data-debug-result="Test"]');
      debug.classList.remove('open');

      Drupal.IntegrationReport.statusReceived(false, 'Test', 'oops', 75);

      const row = document.querySelector('[data-status-result="Test"]');
      expect(row.classList.contains('error')).toBe(true);
      expect(row.classList.contains('open')).toBe(true);
      expect(row.classList.contains('warning')).toBe(false);
      expect(
        row.querySelector('.status-report-response').textContent,
      ).toBe('FAIL (75ms)');
      expect(debug.classList.contains('open')).toBe(true);
    });

    test('keeps row in warning state when time exceeds threshold', function () {
      setupRow('Test', false);
      Drupal.IntegrationReport.statusReceived(
        true,
        'Test',
        '',
        Drupal.IntegrationReport.FAILURE_THRESHOLD + 1,
      );

      const row = document.querySelector('[data-status-result="Test"]');
      expect(row.classList.contains('ok')).toBe(false);
      expect(row.classList.contains('warning')).toBe(true);
    });

    test('returns silently when target row is missing', function () {
      expect(function () {
        Drupal.IntegrationReport.statusReceived(true, 'NotInDom', '', 100);
      }).not.toThrow();
    });

    test('removes the throbber after completion', function () {
      setupRow('Test', false);
      expect(document.querySelector('.ajax-progress')).not.toBeNull();

      Drupal.IntegrationReport.statusReceived(true, 'Test', '', 100);

      expect(document.querySelector('.ajax-progress')).toBeNull();
    });

    test('inserts a sanitized message into the message element', function () {
      setupRow('Test', false);

      Drupal.IntegrationReport.statusReceived(
        true,
        'Test',
        '<ul><li>safe</li></ul><script>window.xss=true</script>',
        100,
      );

      const messageHtml = document.querySelector(
        '.status-report-message',
      ).innerHTML;
      expect(messageHtml).toContain('<ul>');
      expect(messageHtml).toContain('safe');
      expect(messageHtml).not.toContain('<script');
      expect(messageHtml).not.toContain('window.xss=true');
    });
  });
});
