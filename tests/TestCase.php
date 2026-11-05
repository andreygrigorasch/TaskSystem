<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        // This check runs before RefreshDatabase / DatabaseMigrations can act.
        $database = config('database.connections.'.config('database.default').'.database');
        if (! $app->environment('testing') || ! str_ends_with($database, '_test')) {
            throw new RuntimeException('Tests require APP_ENV=testing and a separate database ending in _test.');
        }

        return $app;
    }
}
