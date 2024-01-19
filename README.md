<p align="center">
  <a href="" rel="noopener">
  <img width=200px height=200px src="https://placehold.jp/000000/ffffff/200x200.png?text=Integration+Report&css=%7B%22border-radius%22%3A%22%20100px%22%7D" alt="Integration Report Drupal module"></a>
</p>

<h1 align="center">Integration Report Drupal module</h1>

<div align="center">

[![GitHub Issues](https://img.shields.io/github/issues/AlexSkrypnyk/integration_report.svg)](https://github.com/AlexSkrypnyk/integration_report/issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/AlexSkrypnyk/integration_report.svg)](https://github.com/AlexSkrypnyk/integration_report/pulls)
[![CircleCI](https://circleci.com/gh/AlexSkrypnyk/integration_report.svg?style=shield)](https://circleci.com/gh/AlexSkrypnyk/integration_report)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/AlexSkrypnyk/integration_report)
![LICENSE](https://img.shields.io/github/license/AlexSkrypnyk/integration_report)
![Renovate](https://img.shields.io/badge/renovate-enabled-green?logo=renovatebot)

![Drupal 9](https://img.shields.io/badge/Drupal-9-blue.svg) ![Drupal 10](https://img.shields.io/badge/Drupal-10-blue.svg)

</div>

![screenshot](https://user-images.githubusercontent.com/378794/39668688-daf598bc-5117-11e8-9d15-5459278d164e.png)

## Why?

If your website has 3rd-party integration with one or multiple 3rd-party
services via API endpoints, this module allows to call the endpoints and
see all the response information within a single page.

You may also implement status check on behalf of any other Drupal module that
does not have such information page.

## Features

- Single page for all status checks.
- `<iframe>`-based status checks (useful for SSO with redirects).

## Getting started

1. Extend `IntegrationReport` class with your 3rd-party endpoint request
   methods.
2. Register this class as a service in your custom module and tag it:

        services:
          example_integration_report:
            class: Drupal\example\ExampleIntegrationReport
            tags:
              - { name: integration_report }

3. Go to `/admin/reports/integrations` to check the status.

Refer to [integration_report.api.php](integration_report.api.php) and
`modules/integration_report_example` for an implementation example.

## Local development

Provided that you have PHP installed locally, you can develop an extension using
the provided scripts.

### Build

Run `.devtools/build-codebase.sh` (or `ahoy build-codebase`
if [Ahoy](https://github.com/ahoy-cli/ahoy) is installed) to start inbuilt PHP
server locally and run the same commands as in CI, plus installing a site and
your extension automatically.

### Code linting

Run tools individually (or `ahoy lint` to run all tools
if [Ahoy](https://github.com/ahoy-cli/ahoy) is installed) to lint your code
according to
the [Drupal coding standards](https://www.drupal.org/docs/develop/standards).

```
cd build

vendor/bin/phpcs
vendor/bin/phpstan
vendor/bin/rector --clear-cache --dry-run
vendor/bin/phpmd . text phpmd.xml
vendor/bin/twigcs
```

- PHPCS config: [`phpcs.xml`](phpcs.xml)
- PHPStan config: [`phpstan.neon`](phpstan.neon)
- PHPMD config: [`phpmd.xml`](phpmd.xml)
- Rector config: [`rector.php`](rector.php)
- TwigCS config: [`.twig_cs.php`](.twig_cs.php)

### Tests

Run `.devtools/test.sh` (or `ahoy test`
if [Ahoy](https://github.com/ahoy-cli/ahoy) is installed) to run all test for
your extension.

### Browsing SQLite database

To browse the contents of created SQLite database
(located at `/tmp/site_[EXTENSION_NAME].sqlite`),
use [DB Browser for SQLite](https://sqlitebrowser.org/).
