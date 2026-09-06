<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Calendly Personal Access Token
    |--------------------------------------------------------------------------
    |
    | The token used to authenticate REST calls. Create one at:
    | https://calendly.com/integrations/api_webhooks
    |
    | When this is null the client falls back to config('services.calendly.token').
    |
    */
    'token' => env('CALENDLY_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The number of seconds to wait for a response before giving up.
    |
    */
    'timeout' => (int) env('CALENDLY_TIMEOUT', 8),
];
