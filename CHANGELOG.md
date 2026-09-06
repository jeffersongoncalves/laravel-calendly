# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.0.0 - 2026-09-06

Initial release.

- Calendly REST API client (users, event types, scheduled events, invitees, cancellation)
- Availability endpoints (available times, user busy times)
- Webhook subscriptions (list, create, delete)
- Organization membership lookup
- Config-driven personal access token

## [Unreleased]

### Added

- Initial release.
- `CalendlyClient` REST wrapper covering users, event types, scheduled events, availability, webhooks, and organization members.
- Configurable token and timeout via `config/calendly.php`.
