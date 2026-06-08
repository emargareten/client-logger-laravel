<?php

namespace Emargareten\ClientLogger;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class DefaultClientLogger implements ClientLoggerInterface
{
    protected const MASK = '********';

    protected const APPEND_CONFIG_KEYS = [
        'hidden_request_headers',
        'hidden_request_params',
        'hidden_response_headers',
        'hidden_response_params',
    ];

    protected RequestInterface $request;

    protected ResponseInterface $response;

    protected string $message;

    protected array $config;

    public function __construct()
    {
        $this->config = config('client-logger');
        $this->message = $this->config['message'];
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function setConfig(array $config = []): static
    {
        $this->config = $this->mergeConfig($this->config, $config);

        return $this;
    }

    public function log(RequestInterface $request, ResponseInterface $response): void
    {
        $this->request = $request;
        $this->response = $response;

        $logLevel = $this->logLevel();

        if ($logLevel === null) {
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

        return $levels[$statusCodeRange] ?? null;
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
        $stream = $response->getBody();

        if (! $stream->isSeekable()) {
            return 'Streamed Response';
        }

        $content = (string) $stream;
        $stream->rewind();

        $decoded = json_decode($content, true);

        if (is_array($decoded) && json_last_error() === JSON_ERROR_NONE) {
            return $this->hideParameters($decoded, $this->config['hidden_response_params']);
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
        $uri = $this->request->getUri();
        $query = $uri->getQuery();

        if ($query === '') {
            return (string) $uri;
        }

        return (string) $uri->withQuery($this->hideQueryParameters($query, $this->config['hidden_request_params']));
    }

    protected function hideParameters(array $data, array $hidden): array
    {
        $hiddenKeys = array_flip($hidden);

        foreach ($data as $key => $value) {
            if (isset($hiddenKeys[$key])) {
                $data[$key] = self::MASK;

                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->hideParameters($value, $hidden);
            }
        }

        return $data;
    }

    protected function hideQueryParameters(string $query, array $hidden): string
    {
        $hiddenKeys = array_flip($hidden);

        return implode('&', array_map(function (string $part) use ($hiddenKeys): string {
            $hasValue = str_contains($part, '=');
            [$key] = array_pad(explode('=', $part, 2), 2, null);
            $decodedKey = rawurldecode(str_replace('+', ' ', $key));
            $baseKey = Str::before($decodedKey, '[');

            if (isset($hiddenKeys[$decodedKey]) || isset($hiddenKeys[$baseKey])) {
                return $hasValue ? $key.'='.self::MASK : $key;
            }

            return $part;
        }, explode('&', $query)));
    }

    protected function requestData(): array
    {
        if (! $this->request->hasHeader('Content-Type')) {
            return [];
        }

        $contentType = strtolower($this->request->getHeaderLine('Content-Type'));
        $stream = $this->request->getBody();

        if (! $stream->isSeekable()) {
            return [];
        }

        $body = (string) $stream;
        $stream->rewind();

        if (Str::startsWith($contentType, 'application/x-www-form-urlencoded')) {
            parse_str($body, $parameters);

            return $parameters;
        }

        if (str_contains($contentType, 'json')) {
            return json_decode($body, true) ?? [];
        }

        // todo handle multipart/form-data

        return [];
    }

    protected function headers(array $headers, array $hidden = []): array
    {
        $hidden = array_map(fn (string $headerName) => strtolower($headerName), $hidden);

        $headerNames = collect($headers)->keys()->map(function ($headerName) {
            return strtolower($headerName);
        })->toArray();

        $headerValues = collect($headers)
            ->map(fn ($header) => implode(', ', $header))
            ->all();

        $headers = array_combine($headerNames, $headerValues);

        return $this->hideParameters($headers, $hidden);
    }

    protected function mergeConfig(array $base, array $overrides): array
    {
        $merged = array_merge($base, Arr::except($overrides, self::APPEND_CONFIG_KEYS));

        foreach (self::APPEND_CONFIG_KEYS as $key) {
            if (! array_key_exists($key, $overrides)) {
                continue;
            }

            $baseValues = is_array($base[$key] ?? null) ? $base[$key] : [];
            $overrideValues = is_array($overrides[$key]) ? $overrides[$key] : [];

            $merged[$key] = array_values(array_unique(array_merge($baseValues, $overrideValues)));
        }

        return $merged;
    }
}
