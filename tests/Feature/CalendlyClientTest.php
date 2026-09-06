<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JeffersonGoncalves\Calendly\CalendlyClient;

it('fetches the current user', function () {
    Http::fake([
        'api.calendly.com/users/me' => Http::response(['resource' => ['name' => 'Jane Doe']], 200),
    ]);

    expect(CalendlyClient::me())->toBe(['resource' => ['name' => 'Jane Doe']]);

    Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer fake-token'));
});

it('returns null from me on a non-2xx', function () {
    Http::fake([
        'api.calendly.com/users/me' => Http::response('', 401),
    ]);

    expect(CalendlyClient::me())->toBeNull();
});

it('lists event types for a user', function () {
    Http::fake([
        'api.calendly.com/event_types*' => Http::response(['collection' => []], 200),
    ]);

    expect(CalendlyClient::eventTypes(user: 'https://api.calendly.com/users/AAAA'))
        ->toBe(['collection' => []]);

    Http::assertSent(fn (Request $request) => str_contains($request->url(), 'user=https'));
});

it('requires either a user or an organization for eventTypes', function () {
    expect(fn () => CalendlyClient::eventTypes())
        ->toThrow(InvalidArgumentException::class);
});

it('requires either a user or an organization for events', function () {
    expect(fn () => CalendlyClient::events())
        ->toThrow(InvalidArgumentException::class);
});

it('fetches a single event type', function () {
    Http::fake([
        'api.calendly.com/event_types/AAAA' => Http::response(['resource' => ['uuid' => 'AAAA']], 200),
    ]);

    expect(CalendlyClient::eventType('AAAA'))->toBe(['resource' => ['uuid' => 'AAAA']]);
});

it('lists scheduled events for an organization', function () {
    Http::fake([
        'api.calendly.com/scheduled_events*' => Http::response(['collection' => []], 200),
    ]);

    expect(CalendlyClient::events(organization: 'https://api.calendly.com/organizations/AAAA'))
        ->toBe(['collection' => []]);
});

it('fetches a single scheduled event', function () {
    Http::fake([
        'api.calendly.com/scheduled_events/AAAA' => Http::response(['resource' => ['uuid' => 'AAAA']], 200),
    ]);

    expect(CalendlyClient::event('AAAA'))->toBe(['resource' => ['uuid' => 'AAAA']]);
});

it('cancels an event with a reason', function () {
    Http::fake([
        'api.calendly.com/scheduled_events/AAAA/cancellation' => Http::response(['resource' => ['reason' => 'busy']], 201),
    ]);

    expect(CalendlyClient::cancelEvent('AAAA', 'busy'))->toBe(['resource' => ['reason' => 'busy']]);

    Http::assertSent(fn (Request $request) => $request['reason'] === 'busy');
});

it('lists event invitees', function () {
    Http::fake([
        'api.calendly.com/scheduled_events/AAAA/invitees*' => Http::response(['collection' => []], 200),
    ]);

    expect(CalendlyClient::eventInvitees('AAAA'))->toBe(['collection' => []]);
});

it('fetches available times', function () {
    Http::fake([
        'api.calendly.com/event_type_available_times*' => Http::response(['collection' => []], 200),
    ]);

    expect(CalendlyClient::availableTimes('https://api.calendly.com/event_types/AAAA', '2026-09-06T00:00:00Z', '2026-09-13T00:00:00Z'))
        ->toBe(['collection' => []]);
});

it('fetches user busy times', function () {
    Http::fake([
        'api.calendly.com/user_busy_times*' => Http::response(['collection' => []], 200),
    ]);

    expect(CalendlyClient::userBusyTimes('https://api.calendly.com/users/AAAA', '2026-09-06T00:00:00Z', '2026-09-13T00:00:00Z'))
        ->toBe(['collection' => []]);
});

it('lists webhook subscriptions', function () {
    Http::fake([
        'api.calendly.com/webhook_subscriptions*' => Http::response(['collection' => []], 200),
    ]);

    expect(CalendlyClient::webhooks('https://api.calendly.com/organizations/AAAA'))
        ->toBe(['collection' => []]);
});

it('creates a webhook subscription', function () {
    Http::fake([
        'api.calendly.com/webhook_subscriptions' => Http::response(['resource' => ['uri' => 'AAAA']], 201),
    ]);

    expect(CalendlyClient::createWebhook(
        'https://example.com/webhook',
        ['invitee.created'],
        'https://api.calendly.com/organizations/AAAA',
    ))->toBe(['resource' => ['uri' => 'AAAA']]);

    Http::assertSent(fn (Request $request) => $request['url'] === 'https://example.com/webhook');
});

it('deletes a webhook subscription', function () {
    Http::fake([
        'api.calendly.com/webhook_subscriptions/AAAA' => Http::response('', 204),
    ]);

    expect(CalendlyClient::deleteWebhook('AAAA'))->toBeTrue();
});

it('returns false when a webhook deletion fails', function () {
    Http::fake([
        'api.calendly.com/webhook_subscriptions/AAAA' => Http::response('', 404),
    ]);

    expect(CalendlyClient::deleteWebhook('AAAA'))->toBeFalse();
});

it('lists organization members', function () {
    Http::fake([
        'api.calendly.com/organization_memberships*' => Http::response(['collection' => []], 200),
    ]);

    expect(CalendlyClient::organizationMembers('https://api.calendly.com/organizations/AAAA'))
        ->toBe(['collection' => []]);
});

it('falls back to the services.calendly.token config value', function () {
    config()->set('calendly.token', null);
    config()->set('services.calendly.token', 'services-token');

    Http::fake([
        'api.calendly.com/users/me' => Http::response(['resource' => []], 200),
    ]);

    CalendlyClient::me();

    Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer services-token'));
});

it('returns null and logs when the request throws', function () {
    Log::spy();

    Http::fake(fn () => throw new ConnectionException('Connection timed out'));

    expect(CalendlyClient::me())->toBeNull();

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message, array $context) => $message === 'CalendlyClient outbound fetch failed'
            && $context['context'] === 'get')
        ->once();
});
