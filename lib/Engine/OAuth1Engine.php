<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\Engine;

use Eva\EvaOAuth\Exception\ProviderException;
use Eva\EvaOAuth\Exception\TransportException;
use Eva\EvaOAuth\Exception\UnsupportedOperationException;
use Eva\EvaOAuth\Http\Transport;
use Eva\EvaOAuth\Http\UrlPolicy;
use Eva\EvaOAuth\Provider\OAuth1Provider;
use Eva\EvaOAuth\Token;
use League\OAuth1\Client\Credentials\TemporaryCredentials;
use League\OAuth1\Client\Credentials\TokenCredentials;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class OAuth1Engine
{
    public function __construct(private readonly OAuth1Provider $provider, private readonly Transport $transport)
    {
    }

    public function begin(): array
    {
        try {
            $server = new OAuth1Server($this->provider, $this->transport);
            $credentials = $server->getTemporaryCredentials();
            return [
                'url' => $server->getAuthorizationUrl($credentials, $this->provider->authorizationParameters),
                'token' => $credentials->getIdentifier(),
                'secret' => $credentials->getSecret(),
            ];
        } catch (TransportException) {
            throw new TransportException();
        } catch (\Throwable) {
            throw new ProviderException();
        }
    }

    public function exchange(
        #[\SensitiveParameter] string $token,
        #[\SensitiveParameter] string $secret,
        #[\SensitiveParameter] string $verifier,
    ): Token {
        try {
            foreach ([$token, $secret, $verifier] as $value) {
                if ($value === '' || strlen($value) > 8192 || preg_match('/[\x00-\x20\x7f]/', $value)) {
                    throw new ProviderException();
                }
            }
            $temporary = new TemporaryCredentials();
            $temporary->setIdentifier($token);
            $temporary->setSecret($secret);
            $server = new OAuth1Server($this->provider, $this->transport);
            $credentials = $server->getTokenCredentials($temporary, $token, $verifier);
            return new Token(
                $this->provider->tokenBinding(),
                'oauth1',
                $credentials->getIdentifier(),
                tokenSecret: $credentials->getSecret(),
            );
        } catch (TransportException) {
            throw new TransportException();
        } catch (\Throwable) {
            throw new ProviderException();
        }
    }

    public function request(
        #[\SensitiveParameter] Token $token,
        #[\SensitiveParameter] RequestInterface $request,
    ): ResponseInterface {
        try {
            UrlPolicy::resource($request, $this->provider->allowedOrigins());
            if (
                $token->protocol !== 'oauth1' || !hash_equals($this->provider->tokenBinding(), $token->provider)
                || $token->isExpired()
            ) {
                throw new ProviderException();
            }
            $uri = $request->getUri();
            $target = ($uri->getPath() === '' ? '/' : $uri->getPath())
                . ($uri->getQuery() === '' ? '' : '?' . $uri->getQuery());
            if ($request->getRequestTarget() !== $target || $request->hasHeader('Authorization')) {
                throw new UnsupportedOperationException();
            }
            $query = $this->parameters($uri->getQuery());
            $body = [];
            $contentType = strtolower(trim($request->getHeaderLine('Content-Type')));
            if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
                if (!preg_match('/\Aapplication\/x-www-form-urlencoded(?:\s*;\s*charset=utf-8)?\z/D', $contentType)) {
                    throw new UnsupportedOperationException();
                }
                $stream = $request->getBody();
                if (!$stream->isSeekable() || !$stream->isReadable()) {
                    throw new UnsupportedOperationException();
                }
                $position = $stream->tell();
                try {
                    $stream->rewind();
                    $content = \GuzzleHttp\Psr7\Utils::copyToString($stream, 1048577);
                    $body = $this->parameters($content);
                } finally {
                    $stream->seek($position);
                }
                $request = $request->withBody(\GuzzleHttp\Psr7\Utils::streamFor($content));
            }
            if (array_intersect_key($query, $body) !== []) {
                throw new UnsupportedOperationException();
            }
            $credentials = new TokenCredentials();
            $credentials->setIdentifier($token->accessToken);
            $credentials->setSecret($token->tokenSecret);
            $server = new OAuth1Server($this->provider, $this->transport);
            $headers = $server->getHeaders(
                $credentials,
                $request->getMethod(),
                (string) $uri->withQuery(''),
                $query + $body,
            );
            return $this->transport->send($request->withHeader('Authorization', $headers['Authorization']));
        } catch (UnsupportedOperationException) {
            throw new UnsupportedOperationException();
        } catch (TransportException) {
            throw new TransportException();
        } catch (\Throwable) {
            throw new ProviderException();
        }
    }

    private function parameters(#[\SensitiveParameter] string $encoded): array
    {
        if (strlen($encoded) > 1048576 || preg_match('/%(?![a-fA-F0-9]{2})/', $encoded)) {
            throw new UnsupportedOperationException();
        }
        if ($encoded === '') {
            return [];
        }
        $parameters = [];
        foreach (explode('&', $encoded) as $pair) {
            $parts = explode('=', $pair, 2);
            $name = urldecode($parts[0]);
            if (
                !preg_match('/\A[a-zA-Z_][a-zA-Z0-9_-]*\z/D', $name)
                || str_starts_with($name, 'oauth_') || in_array($name, ['access_token', 'client_secret'], true)
                || array_key_exists($name, $parameters) || count($parameters) >= 100
            ) {
                throw new UnsupportedOperationException();
            }
            $parameters[$name] = urldecode($parts[1] ?? '');
        }
        return $parameters;
    }

    public function __debugInfo(): array
    {
        return ['protocol' => 'oauth1'];
    }
}
