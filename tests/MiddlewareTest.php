<?php

namespace Emargareten\ClientLogger\Tests;

use Emargareten\ClientLogger\ClientLoggerInterface;
use Emargareten\ClientLogger\LoggingMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Mockery\MockInterface;

class MiddlewareTest extends TestCase
{
    public function test_logs_works()
    {
        $logger = $this->mock(ClientLoggerInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('log')->once();
            $mock->shouldReceive('setMessage')->once();
            $mock->shouldReceive('setConfig')->once();
        });

        $middleware = new LoggingMiddleware($logger);

        $mockHandler = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], 'Hello, World'),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push($middleware());
        $client = new Client(['handler' => $handlerStack]);

        $client->request('GET', '/');
    }
}
