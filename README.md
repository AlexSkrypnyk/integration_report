# Integration Report Drupal module
Drupal module to report on availability status of 3rd-party endpoints.

[![CircleCI](https://circleci.com/gh/AlexSkrypnyk/integration_report.svg?style=shield)](https://circleci.com/gh/AlexSkrypnyk/integration_report)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/AlexSkrypnyk/integration_report)
![LICENSE](https://img.shields.io/github/license/drevops/generated_content)

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
