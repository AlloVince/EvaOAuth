<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\Http;

use Eva\EvaOAuth\Exception\ProviderException;
use Eva\EvaOAuth\Exception\TransportException;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Create;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class Transport
{
    private const METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

    private readonly ClientInterface $client;
    private readonly LoggerInterface $logger;

    public function __construct(?ClientInterface $client = null, ?LoggerInterface $logger = null)
    {
        $this->client = $client ?? new Client([
            'allow_redirects' => false,
            'http_errors' => false,
            'timeout' => 15,
            'connect_timeout' => 5,
        ]);
        $this->logger = $logger ?? new NullLogger();
    }

    public function send(#[\SensitiveParameter] RequestInterface $request): ResponseInterface
    {
        UrlPolicy::origin((string) $request->getUri());
        $started = hrtime(true);
        $method = strtoupper($request->getMethod());
        $metadata = [
            'operation' => 'http',
            'method' => in_array($method, self::METHODS, true) ? $method : 'OTHER',
        ];
        try {
            $response = $this->client->sendRequest($request);
        } catch (\Throwable) {
            $this->log('oauth.http.failure', $metadata + [
                'category' => 'transport',
                'duration_ms' => (hrtime(true) - $started) / 1e6,
            ]);
            throw new TransportException();
        }
        $this->log('oauth.http.response', $metadata + [
            'status' => $response->getStatusCode(),
            'duration_ms' => (hrtime(true) - $started) / 1e6,
        ]);
        if ($response->getStatusCode() >= 300 && $response->getStatusCode() < 400) {
            throw new ProviderException();
        }
        return $response;
    }

    public function guzzle(): Client
    {
        $handler = function (#[\SensitiveParameter] RequestInterface $request): \GuzzleHttp\Promise\PromiseInterface {
            try {
                return Create::promiseFor($this->send($request));
            } catch (\Throwable $error) {
                return Create::rejectionFor($error);
            }
        };
        return new Client([
            'handler' => $handler,
            'allow_redirects' => false,
            'http_errors' => false,
        ]);
    }

    private function log(string $event, array $metadata): void
    {
        try {
            $this->logger->debug($event, $metadata);
        } catch (\Throwable) {
        }
    }

    public function __debugInfo(): array
    {
        return [];
    }
}
