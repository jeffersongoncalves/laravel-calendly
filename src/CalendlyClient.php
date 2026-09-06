<?php

namespace JeffersonGoncalves\Calendly;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Calendly REST API v2 HTTP layer. Wraps the `api.calendly.com` calls behind
 * a small static client, threading the bearer token and defensively
 * returning null (rather than throwing) on network failures or non-2xx
 * responses so callers never have to wrap every call in a try/catch.
 */
class CalendlyClient
{
    private const BASE_URL = 'https://api.calendly.com';

    /**
     * @return array<string, mixed>|null
     */
    public static function me(): ?array
    {
        return self::json(self::get('/users/me'));
    }

    /**
     * @param  array<string, mixed>  $params  Additional filters: active, count, page_token.
     * @return array<string, mixed>|null
     */
    public static function eventTypes(?string $user = null, ?string $organization = null, array $params = []): ?array
    {
        self::assertUserOrOrganization($user, $organization);

        $query = array_merge(['user' => $user, 'organization' => $organization], $params);

        return self::json(self::get('/event_types', $query));
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function eventType(string $uuid): ?array
    {
        return self::json(self::get("/event_types/{$uuid}"));
    }

    /**
     * @param  array<string, mixed>  $params  Additional filters: min_start_time, max_start_time, status, count, page_token, sort.
     * @return array<string, mixed>|null
     */
    public static function events(?string $user = null, ?string $organization = null, array $params = []): ?array
    {
        self::assertUserOrOrganization($user, $organization);

        $query = array_merge(['user' => $user, 'organization' => $organization], $params);

        return self::json(self::get('/scheduled_events', $query));
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function event(string $uuid): ?array
    {
        return self::json(self::get("/scheduled_events/{$uuid}"));
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function cancelEvent(string $uuid, ?string $reason = null): ?array
    {
        return self::json(self::post("/scheduled_events/{$uuid}/cancellation", ['reason' => $reason]));
    }

    /**
     * @param  array<string, mixed>  $params  Additional filters: count, page_token, email, status.
     * @return array<string, mixed>|null
     */
    public static function eventInvitees(string $uuid, array $params = []): ?array
    {
        return self::json(self::get("/scheduled_events/{$uuid}/invitees", $params));
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function availableTimes(string $eventType, string $startTime, string $endTime): ?array
    {
        return self::json(self::get('/event_type_available_times', [
            'event_type' => $eventType,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]));
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function userBusyTimes(string $user, string $startTime, string $endTime): ?array
    {
        return self::json(self::get('/user_busy_times', [
            'user' => $user,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]));
    }

    /**
     * @param  array<string, mixed>  $params  Additional filters: count, page_token.
     * @return array<string, mixed>|null
     */
    public static function webhooks(string $organization, string $scope = 'organization', array $params = []): ?array
    {
        $query = array_merge(['organization' => $organization, 'scope' => $scope], $params);

        return self::json(self::get('/webhook_subscriptions', $query));
    }

    /**
     * @param  list<string>  $events
     * @return array<string, mixed>|null
     */
    public static function createWebhook(string $url, array $events, string $organization, ?string $user = null, string $scope = 'organization'): ?array
    {
        $body = [
            'url' => $url,
            'events' => $events,
            'organization' => $organization,
            'scope' => $scope,
            'user' => $user,
        ];

        return self::json(self::post('/webhook_subscriptions', $body));
    }

    public static function deleteWebhook(string $uuid): bool
    {
        $response = self::delete("/webhook_subscriptions/{$uuid}");

        return $response !== null && $response->successful();
    }

    /**
     * @param  array<string, mixed>  $params  Additional filters: count, page_token.
     * @return array<string, mixed>|null
     */
    public static function organizationMembers(string $organization, array $params = []): ?array
    {
        $query = array_merge(['organization' => $organization], $params);

        return self::json(self::get('/organization_memberships', $query));
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private static function get(string $path, array $query = []): ?Response
    {
        try {
            return self::client()->get(self::BASE_URL.$path, self::filter($query));
        } catch (Throwable $e) {
            self::logFailure('get', $path, $e);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private static function post(string $path, array $body = []): ?Response
    {
        try {
            return self::client()->post(self::BASE_URL.$path, self::filter($body));
        } catch (Throwable $e) {
            self::logFailure('post', $path, $e);

            return null;
        }
    }

    private static function delete(string $path): ?Response
    {
        try {
            return self::client()->delete(self::BASE_URL.$path);
        } catch (Throwable $e) {
            self::logFailure('delete', $path, $e);

            return null;
        }
    }

    private static function client(): PendingRequest
    {
        $request = Http::timeout(self::timeout());

        if ($token = self::token()) {
            $request = $request->withToken($token);
        }

        return $request;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function json(?Response $response): ?array
    {
        if ($response === null || ! $response->successful()) {
            return null;
        }

        $data = $response->json();

        return is_array($data) ? $data : null;
    }

    /**
     * Drop null values from a query/body payload before it's sent.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private static function filter(array $params): array
    {
        return array_filter($params, fn (mixed $value): bool => $value !== null);
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function assertUserOrOrganization(?string $user, ?string $organization): void
    {
        if ($user === null && $organization === null) {
            throw new InvalidArgumentException('Either $user or $organization must be provided.');
        }
    }

    private static function logFailure(string $context, string $target, Throwable $e): void
    {
        Log::warning('CalendlyClient outbound fetch failed', [
            'context' => $context,
            'target' => $target,
            'exception' => $e::class,
            'message' => $e->getMessage(),
        ]);
    }

    private static function token(): ?string
    {
        $token = config('calendly.token') ?? config('services.calendly.token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    private static function timeout(): int
    {
        return (int) config('calendly.timeout', 8);
    }
}
