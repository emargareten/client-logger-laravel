<?php

namespace Emargareten\ClientLogger;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\ServiceProvider;

class ClientLoggerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ClientLoggerInterface::class, function ($app) {
            return $app->make(config('client-logger.logger'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/client-logger.php', 'client-logger');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/client-logger.php' => config_path('client-logger.php'),
            ], 'client-logger-config');
        }

        $this->registerMacros();
    }

    /**
     * Register the package's macros.
     */
    public function registerMacros(): void
    {
        PendingRequest::macro('log', function (
            ?string $message = null,
            array $config = [],
            ?ClientLoggerInterface $logger = null,
        ): PendingRequest {
            $logger ??= app(ClientLoggerInterface::class);

            /** @var \Illuminate\Http\Client\PendingRequest $this */
            return $this->withMiddleware((new LoggingMiddleware($logger))->__invoke($message, $config));
        });
    }
}
