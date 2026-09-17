<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\Tests;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class MockHttpClient implements ClientInterface
{
    public array $requests = [];

    public function __construct(public array $queue = [])
    {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;
        $response = array_shift($this->queue);
        if ($response instanceof \Throwable) {
            throw $response;
        }
        if (!$response instanceof ResponseInterface) {
            throw new \RuntimeException('Mock response queue exhausted.');
        }
        return $response;
    }
}
