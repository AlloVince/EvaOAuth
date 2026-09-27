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

    private const STAGES = [
        'authorize', 'request_token', 'token_exchange', 'token_refresh', 'identity', 'resource_request', 'callback',
    ];

    private readonly ClientInterface $client;
    private readonly LoggerInterface $logger;
    private readonly array $context;

    public function __construct(
        ?ClientInterface $client = null,
        ?LoggerInterface $logger = null,
        array $context = [],
    ) {
        $this->client = $client ?? new Client([
            'allow_redirects' => false,
            'http_errors' => false,
            'timeout' => 15,
            'connect_timeout' => 5,
        ]);
        $this->logger = $logger ?? new NullLogger();
        $this->context = $context;
    }

    public function withContext(string $provider, string $stage): self
    {
        $alias = preg_match('/\A[a-zA-Z][a-zA-Z0-9_-]{0,63}\z/D', $provider) === 1 ? $provider : null;
        if ($alias === null || !in_array($stage, self::STAGES, true)) {
            return $this;
        }
        return new self($this->client, $this->logger, ['provider' => $alias, 'stage' => $stage]);
    }

    public function at(string $stage): self
    {
        if (!isset($this->context['provider']) || !in_array($stage, self::STAGES, true)) {
            return $this;
        }
        return new self($this->client, $this->logger, ['provider' => $this->context['provider'], 'stage' => $stage]);
    }

    public function report(string $category, float $duration): void
    {
        $this->log('oauth.failure', $this->context + [
            'category' => self::category($category),
            'duration_ms' => $duration,
        ]);
    }

    public function send(#[\SensitiveParameter] RequestInterface $request): ResponseInterface
    {
        UrlPolicy::origin((string) $request->getUri());
        $started = hrtime(true);
        $method = strtoupper($request->getMethod());
        $metadata = $this->context + [
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

    private static function category(string $category): string
    {
        return in_array($category, ['callback', 'configuration', 'provider', 'transport', 'unsupported'], true)
            ? $category
            : 'provider';
    }

    public function __debugInfo(): array
    {
        return [];
    }
}
