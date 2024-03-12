<?php

namespace Emargareten\ClientLogger;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class DefaultClientLogger implements ClientLoggerInterface
{
    protected RequestInterface $request;

    protected ResponseInterface $response;

    protected string $message;

    protected array $config;

    public function __construct()
    {
        $this->message = config('client-logger.message');
        $this->config = config('client-logger');
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function setConfig(array $config = []): static
    {
        $this->config = array_merge($this->config, $config);

        return $this;
    }

    public function log(RequestInterface $request, ResponseInterface $response): void
    {
        $this->request = $request;
        $this->response = $response;

        $logLevel = $this->logLevel();

        if (! $logLevel) {
            return;
        }

        Log::channel($this->channel())->log(
            $logLevel,
            $this->message(),
            $this->context()
        );
    }

    protected function channel(): ?string
    {
        return $this->config['channel'];
    }

    protected function logLevel(): ?string
    {
        $levels = $this->config['level'];

        $statusCode = $this->response->getStatusCode();

        if (array_key_exists($statusCode, $levels)) {
            return $levels[$statusCode];
        }

        $statusCodeRange = substr((string) $statusCode, 0, 1).'xx';

        return $levels[$statusCodeRange];
    }

    protected function message(): string
    {
        return $this->message;
    }

    protected function context(): array
    {
        return [
            'method' => $this->request->getMethod(),
            'uri' => $this->getRequestUri(),
            'headers' => $this->headers($this->request->getHeaders(), $this->config['hidden_request_headers']),
            'payload' => $this->hideParameters($this->requestData(), $this->config['hidden_request_params']),
            'response_status' => $this->response->getStatusCode(),
            'response_headers' => $this->headers($this->response->getHeaders(), $this->config['hidden_response_headers']),
            'response' => $this->getResponseBody(),
        ];
    }

    protected function getResponseBody(): array|string
    {
        $response = $this->response;

        $content = (string) $response->getBody();

        $stream = $response->getBody();

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        if (is_array(json_decode($content, true)) &&
            json_last_error() === JSON_ERROR_NONE) {
            return $this->hideParameters(json_decode($content, true), $this->config['hidden_response_params']);
        }

        if (Str::startsWith(strtolower($response->getHeaderLine('Content-Type')), 'text/')) {
            if (isset($this->config['content_chars_limit'])) {
                return Str::limit($content, $this->config['content_chars_limit']);
            }

            return $content;
        }

        if ($response->getStatusCode() >= 300 && $response->getStatusCode() < 400) {
            return 'Redirected to '.$response->getHeaderLine('Location');
        }

        if (empty($content)) {
            return 'Empty Response';
        }

        return 'Unknown Response';
    }

    protected function getRequestUri(): string
    {
        return (string) $this->request->getUri();
    }

    protected function hideParameters(array $data, array $hidden): array
    {
        foreach ($hidden as $parameter) {
            if (! empty(array_filter(data_get($data, $parameter)))) {
                data_set($data, $parameter, '********');
            }
        }

        return $data;
    }

    protected function requestData(): array
    {
        if (! $this->request->hasHeader('Content-Type')) {
            return [];
        }

        $contentType = $this->request->getHeader('Content-Type')[0];

        if ($contentType === 'application/x-www-form-urlencoded') {
            parse_str((string) $this->request->getBody(), $parameters);

            return $parameters;
        }

        if (str_contains($contentType, 'json')) {
            return json_decode((string) $this->request->getBody(), true) ?? [];
        }

        // todo handle multipart/form-data

        return [];
    }

    protected function headers(array $headers, array $hidden = []): array
    {
        $headerNames = collect($headers)->keys()->map(function ($headerName) {
            return strtolower($headerName);
        })->toArray();

        $headerValues = collect($headers)
            ->map(fn ($header) => implode(', ', $header))
            ->all();

        $headers = array_combine($headerNames, $headerValues);

        return $this->hideParameters($headers, $hidden);
    }
}
