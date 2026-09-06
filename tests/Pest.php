<?php

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\Calendly\Tests\TestCase;

uses(TestCase::class)
    ->beforeEach(fn () => Http::preventStrayRequests())
    ->in('Feature');
