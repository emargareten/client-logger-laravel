<?php

namespace Emargareten\ClientLogger;

use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class LoggingMiddleware
{
    public function __construct(protected ClientLoggerInterface $logger)
    {
    }

    public function __invoke(?string $message = null, $config = []): Closure
    {
        $message ??= config('client-logger.message');

        return function (callable $handler) use ($message, $config): callable {
            return function (RequestInterface $request, array $options) use ($message, $config, $handler): PromiseInterface {
                $promise = $handler($request, $options);

                return $promise->then(function (ResponseInterface $response) use ($message, $config, $request) {

                    $this->logger
                        ->setMessage($message)
                        ->setConfig($config)
                        ->log($request, $response);

                    return $response;
                });
            };
        };
    }
}
