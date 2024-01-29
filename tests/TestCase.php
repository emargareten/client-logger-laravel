<?php

namespace Emargareten\ClientLogger\Tests;

use Emargareten\ClientLogger\ClientLoggerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            ClientLoggerServiceProvider::class,
        ];
    }
}
