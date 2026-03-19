<?php

namespace Emargareten\ClientLogger\Tests;

use Emargareten\ClientLogger\ClientLoggerInterface;
use Emargareten\ClientLogger\LoggingMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery\MockInterface;

class MiddlewareTest extends TestCase
{
    public function test_logs_works()
    {
        $logger = $this->mock(ClientLoggerInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('setMessage')->once()->andReturnSelf();
            $mock->shouldReceive('setConfig')->once()->andReturnSelf();
            $mock->shouldReceive('log')->once();
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

    public function test_does_not_log_on_request_exception()
    {
        $logger = $this->mock(ClientLoggerInterface::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('log');
        });

        $middleware = new LoggingMiddleware($logger);

        $mockHandler = new MockHandler([
            new RequestException('Connection error', new Request('GET', '/')),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push($middleware());
        $client = new Client(['handler' => $handlerStack]);

        $this->expectException(RequestException::class);

        $client->request('GET', '/');
    }
}
