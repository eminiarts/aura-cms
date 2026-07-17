# Security Policy

## Supported versions

Security fixes are provided for the latest 1.x release. The pre-release 0.x line is no longer supported after 1.0 is released.

## Reporting a vulnerability

Please do not open a public issue for a suspected vulnerability. Email `support@eminiarts.ch` with:

- the affected version and configuration;
- a concise description of the impact;
- reproducible steps or a proof of concept; and
- any suggested mitigation, if known.

We will acknowledge the report within five business days, investigate it privately, and coordinate disclosure and a fix with the reporter. Do not access data that is not yours or disrupt a production system while researching a report.

## Trusted extension markup

Resource action definitions are application PHP configuration, not stored CMS input. Their `icon`, `icon-view`, and `onclick` values intentionally permit trusted developers to provide markup or JavaScript; labels and descriptions shown to content users are escaped. Do not populate action definitions from request data or database content.
