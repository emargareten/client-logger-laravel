<?php

namespace Emargareten\ClientLogger\Tests;

use Emargareten\ClientLogger\ClientLoggerInterface;
use Emargareten\ClientLogger\DefaultClientLogger;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use TiMacDonald\Log\LogEntry;
use TiMacDonald\Log\LogFake;

class DefaultClientLoggerTest extends TestCase
{
    protected ClientLoggerInterface $logger;

    protected Request $request;

    public function setUp(): void
    {
        parent::setUp();

        $this->logger = new DefaultClientLogger();
        $this->request = new Request('GET', 'https://example.com/path?query=ABCDEF', ['header-key' => 'header-value'], 'TestRequestBody');

        LogFake::bind();
    }

    public function test_logs_request()
    {
        $this->logger->log($this->request, new Response(200));

        Log::assertLogged(fn (LogEntry $log) => $log->level === 'info' && $log->message === 'HTTP Client Request');
    }

    public function test_message_can_be_passed_as_argument()
    {
        $this->logger
            ->setMessage('Test Message')
            ->log($this->request, new Response(200));

        Log::assertLogged(fn (LogEntry $log) => $log->message === 'Test Message');
    }

    public function test_log_level_can_be_changed_for_a_range()
    {
        config(['client-logger.level.4xx' => 'error']);

        $this->logger = new DefaultClientLogger();

        $this->logger->log($this->request, new Response(400));

        Log::assertLogged(fn (LogEntry $log) => $log->level === 'error');
    }

    public function test_log_level_can_be_changed_for_a_specific_status_code()
    {
        config(['client-logger.level.400' => 'error']);

        $this->logger = new DefaultClientLogger();

        $this->logger->log($this->request, new Response(400));

        Log::assertLogged(fn (LogEntry $log) => $log->level === 'error');
    }

    public function test_log_level_can_be_changed_at_runtime()
    {
        $this->logger
            ->setConfig(['level' => ['400' => 'error']])
            ->log($this->request, new Response(400));

        Log::assertLogged(fn (LogEntry $log) => $log->level === 'error');
    }

    public function test_log_level_can_be_changed_for_a_range_at_runtime()
    {
        $this->logger
            ->setConfig(['level' => ['4xx' => 'error']])
            ->log($this->request, new Response(400));

        Log::assertLogged(fn (LogEntry $log) => $log->level === 'error');
    }

    public function test_log_level_can_be_disabled()
    {
        config(['client-logger.level.400' => null]);

        $this->logger = new DefaultClientLogger();

        $this->logger->log($this->request, new Response(400));

        Log::assertNothingLogged();
    }

    public function test_log_level_can_be_disabled_for_a_range()
    {
        config(['client-logger.level.4xx' => null]);

        $this->logger = new DefaultClientLogger();

        $this->logger->log($this->request, new Response(400));

        Log::assertNothingLogged();
    }

    public function test_log_level_can_be_disabled_at_runtime()
    {
        $this->logger
            ->setConfig(['level' => ['400' => null]])
            ->log($this->request, new Response(400));

        Log::assertNothingLogged();
    }

    public function test_log_level_can_be_disabled_for_a_range_at_runtime()
    {
        $this->logger
            ->setConfig(['level' => ['4xx' => null]])
            ->log($this->request, new Response(400));

        Log::assertNothingLogged();
    }

    public function test_log_context_contains_request_header()
    {
        $this->logger->log($this->request, new Response(200));

        Log::assertLogged(fn (LogEntry $log) => Arr::get($log->context, 'headers.header-key') === 'header-value');
    }

    public function test_log_context_hides_hidden_request_header()
    {
        config(['client-logger.hidden_request_headers' => ['header-key']]);

        $this->logger = new DefaultClientLogger();

        $this->logger->log($this->request, new Response(200));

        Log::assertLogged(fn (LogEntry $log) => Arr::get($log->context, 'headers.header-key') === '********');
    }

    public function test_log_context_hides_hidden_request_header_at_runtime()
    {
        $this->logger
            ->setConfig(['hidden_request_headers' => ['header-key']])
            ->log($this->request, new Response(200));

        Log::assertLogged(fn (LogEntry $log) => Arr::get($log->context, 'headers.header-key') === '********');
    }

    public function test_log_contains_request_payload_for_json_content()
    {
        $this->request = new Request('POST', 'https://example.com/path?query=ABCDEF', ['header-key' => 'header-value', 'Content-Type' => 'application/json'], '{"key":"value"}');

        $this->logger->log($this->request, new Response(200));

        Log::assertLogged(fn (LogEntry $log) => Arr::get($log->context, 'payload') === ['key' => 'value']);
    }

    public function test_log_contains_request_payload_for_form_content()
    {
        $this->request = new Request('POST', 'https://example.com/path?query=ABCDEF', ['header-key' => 'header-value', 'Content-Type' => 'application/x-www-form-urlencoded'], 'key=value');

        $this->logger->log($this->request, new Response(200));

        Log::assertLogged(fn (LogEntry $log) => Arr::get($log->context, 'payload') === ['key' => 'value']);
    }

    public function test_log_contains_request_payload_for_multipart_content()
    {
        $this->markTestSkipped('Not implemented yet');
    }

    public function test_log_context_contains_response_status()
    {
        $this->logger->log($this->request, new Response(200));

        Log::assertLogged(fn (LogEntry $log) => Arr::get($log->context, 'response_status') === 200);
    }

    public function test_log_context_contains_response_header()
    {
        $this->logger->log($this->request, new Response(200, ['header2' => 'XYZ']));

        Log::assertLogged(fn (LogEntry $log) => Arr::get($log->context, 'response_headers.header2') === 'XYZ');
    }

    public function test_log_context_hides_hidden_response_header()
    {
        config(['client-logger.hidden_response_headers' => ['header2']]);

        $this->logger = new DefaultClientLogger();

        $this->logger->log($this->request, new Response(200, ['header2' => 'XYZ']));

        Log::assertLogged(fn (LogEntry $log) => Arr::get($log->context, 'response_headers.header2') === '********');
    }

    public function test_log_context_hides_hidden_response_header_at_runtime()
    {
        $this->logger
            ->setConfig(['hidden_response_headers' => ['header2']])
            ->log($this->request, new Response(200, ['header2' => 'XYZ']));

        Log::assertLogged(fn (LogEntry $log) => Arr::get($log->context, 'response_headers.header2') === '********');
    }

    public function test_log_context_contains_response_body_for_json_content()
    {
        $this->logger->log($this->request, new Response(200, [], '{"key":"value"}'));

        Log::assertLogged(fn (LogEntry $log) => Arr::get($log->context, 'response') === ['key' => 'value']);
    }

    public function test_log_context_contains_response_body_for_text_content()
    {
        $this->logger->log($this->request, new Response(200, ['Content-Type' => 'text/plain'], 'This is a text'));

        Log::assertLogged(fn (LogEntry $log) => Arr::get($log->context, 'response') === 'This is a text');
    }

    public function test_log_context_response_body_for_text_content_gets_truncated()
    {
        $this->logger->log($this->request, new Response(200, ['Content-Type' => 'text/plain'], Str::repeat('This is a text', 100)));

        Log::assertLogged(fn (LogEntry $log) => strlen($log->context['response']) === 1003);
    }

    public function test_log_context_response_body_for_text_content_does_not_get_truncated_when_limit_is_null()
    {
        config(['client-logger.content_chars_limit' => null]);

        $this->logger = new DefaultClientLogger();

        $this->logger->log($this->request, new Response(200, ['Content-Type' => 'text/plain'], Str::repeat('This is a text', 100)));

        Log::assertLogged(fn (LogEntry $log) => strlen($log->context['response']) === 1400);
    }

    public function test_log_context_contains_response_body_for_redirect()
    {
        $this->logger->log($this->request, new Response(301, ['Location' => 'https://example.com/redirect']));

        Log::assertLogged(fn (LogEntry $log) => Arr::get($log->context, 'response') === 'Redirected to https://example.com/redirect');
    }

    public function test_log_context_contains_response_body_for_empty_response()
    {
        $this->logger->log($this->request, new Response(200));

        Log::assertLogged(fn (LogEntry $log) => Arr::get($log->context, 'response') === 'Empty Response');
    }

    public function test_log_context_hides_response_params()
    {
        config(['client-logger.hidden_response_params' => ['key']]);

        $this->logger = new DefaultClientLogger();

        $this->logger->log($this->request, new Response(200, [], '{"key":"value"}'));

        Log::assertLogged(fn (LogEntry $log) => Arr::get($log->context, 'response') === ['key' => '********']);
    }

    public function test_log_context_hides_response_params_at_runtime()
    {
        $this->logger
            ->setConfig(['hidden_response_params' => ['key']])
            ->log($this->request, new Response(200, [], '{"key":"value"}'));

        Log::assertLogged(fn (LogEntry $log) => Arr::get($log->context, 'response') === ['key' => '********']);
    }
}
