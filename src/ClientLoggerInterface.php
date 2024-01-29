<?php

namespace Emargareten\ClientLogger;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface ClientLoggerInterface
{
    public function setMessage(string $message): static;

    public function setConfig(array $config = []): static;

    public function log(RequestInterface $request, ResponseInterface $response): void;
}
