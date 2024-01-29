<?php

namespace Emargareten\ClientLogger;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class NullClientLogger implements ClientLoggerInterface
{
    public function log(RequestInterface $request, ResponseInterface $response): void
    {
        //
    }

    public function setMessage(string $message): static
    {
        return $this;
    }

    public function setConfig(array $config = []): static
    {
        return $this;
    }
}
