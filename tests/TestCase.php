<?php

declare(strict_types=1);

namespace Emeq\ExactApi\Tests;

use Emeq\ExactApi\ExactServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        // Array-store ondersteunt atomic locks — nodig voor de refresh-lock-tests.
        config()->set('cache.default', 'array');
    }

    protected function getPackageProviders($app): array
    {
        return [
            ExactServiceProvider::class,
        ];
    }
}
