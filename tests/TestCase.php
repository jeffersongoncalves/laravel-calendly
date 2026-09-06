<?php

namespace JeffersonGoncalves\Calendly\Tests;

use JeffersonGoncalves\Calendly\CalendlyServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            CalendlyServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('calendly.token', 'fake-token');
        $app['config']->set('calendly.timeout', 5);
    }
}
