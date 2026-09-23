<div class="filament-hidden">

![Laravel Calendly](https://raw.githubusercontent.com/jeffersongoncalves/laravel-calendly/master/art/jeffersongoncalves-laravel-calendly.png)

</div>

# Laravel Calendly

[![Tests](https://github.com/jeffersongoncalves/laravel-calendly/actions/workflows/tests.yml/badge.svg)](https://github.com/jeffersongoncalves/laravel-calendly/actions/workflows/tests.yml)
[![PHPStan](https://github.com/jeffersongoncalves/laravel-calendly/actions/workflows/phpstan.yml/badge.svg)](https://github.com/jeffersongoncalves/laravel-calendly/actions/workflows/phpstan.yml)
[![Code Style](https://github.com/jeffersongoncalves/laravel-calendly/actions/workflows/fix-php-code-style-issues.yml/badge.svg)](https://github.com/jeffersongoncalves/laravel-calendly/actions/workflows/fix-php-code-style-issues.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/jeffersongoncalves/laravel-calendly.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-calendly)
[![Total Downloads](https://img.shields.io/packagist/dt/jeffersongoncalves/laravel-calendly.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-calendly)
[![License](https://img.shields.io/packagist/l/jeffersongoncalves/laravel-calendly.svg?style=flat-square)](LICENSE.md)

A lightweight Calendly REST API client for Laravel. It wraps the `api.calendly.com` v2 endpoints behind a small static client, threads your Personal Access Token, and defensively returns `null` (rather than throwing) on network failures or non-2xx responses.

## Features

- **Users** — `me()` fetches the authenticated user
- **Event types** — `eventTypes()` and `eventType()`
- **Scheduled events** — `events()`, `event()`, `cancelEvent()`, `eventInvitees()`
- **Availability** — `availableTimes()` and `userBusyTimes()`
- **Webhooks** — `webhooks()`, `createWebhook()`, `deleteWebhook()`
- **Organizations** — `organizationMembers()`

## Installation

```bash
composer require jeffersongoncalves/laravel-calendly
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag="laravel-calendly-config"
```

## Configuration

Add to your `.env`:

```env
CALENDLY_TOKEN=eyJra...
```

Create a [Personal Access Token](https://calendly.com/integrations/api_webhooks) from your Calendly integrations settings.

### Config Options

```php
// config/calendly.php
return [
    'token' => env('CALENDLY_TOKEN'),
    'timeout' => (int) env('CALENDLY_TIMEOUT', 8),
];
```

When `calendly.token` is null the client falls back to `config('services.calendly.token')`.

## Usage

```php
use JeffersonGoncalves\Calendly\CalendlyClient;

// Users
$me = CalendlyClient::me();

// Event types
$eventTypes = CalendlyClient::eventTypes(user: $me['resource']['uri']);
$eventType = CalendlyClient::eventType('AAAAAAAAAAAAAAAA');

// Scheduled events
$events = CalendlyClient::events(user: $me['resource']['uri'], params: ['status' => 'active']);
$event = CalendlyClient::event('AAAAAAAAAAAAAAAA');
CalendlyClient::cancelEvent('AAAAAAAAAAAAAAAA', 'Rescheduling');
$invitees = CalendlyClient::eventInvitees('AAAAAAAAAAAAAAAA');

// Availability
$slots = CalendlyClient::availableTimes(
    eventType: 'https://api.calendly.com/event_types/AAAAAAAAAAAAAAAA',
    startTime: '2026-09-06T00:00:00Z',
    endTime: '2026-09-13T00:00:00Z',
);
$busy = CalendlyClient::userBusyTimes(
    user: $me['resource']['uri'],
    startTime: '2026-09-06T00:00:00Z',
    endTime: '2026-09-13T00:00:00Z',
);

// Webhooks
$webhooks = CalendlyClient::webhooks(organization: $me['resource']['current_organization']);
CalendlyClient::createWebhook(
    url: 'https://example.com/webhooks/calendly',
    events: ['invitee.created', 'invitee.canceled'],
    organization: $me['resource']['current_organization'],
);
CalendlyClient::deleteWebhook('AAAAAAAAAAAAAAAA');

// Organization members
$members = CalendlyClient::organizationMembers($me['resource']['current_organization']);
```

All methods return `array<string, mixed>|null` (or `bool` for `deleteWebhook()`), returning `null`/`false` on any network failure or non-2xx response instead of throwing. `eventTypes()` and `events()` throw `InvalidArgumentException` when neither `user` nor `organization` is provided, since Calendly requires at least one.

## Testing

```bash
composer test
```

## Static Analysis

```bash
composer analyse
```

## Code Formatting

```bash
composer format
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
