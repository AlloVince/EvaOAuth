<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\Engine;

use Eva\EvaOAuth\Exception\UnsupportedOperationException;
use Eva\EvaOAuth\Http\Transport;
use Eva\EvaOAuth\Provider\OAuth1Provider;
use GuzzleHttp\Client;
use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Server\Server;

final class OAuth1Server extends Server
{
    public function __construct(private readonly OAuth1Provider $provider, private readonly Transport $transport)
    {
        parent::__construct([
            'identifier' => $provider->clientId,
            'secret' => $provider->clientSecret,
            'callback_uri' => $provider->redirectUri(),
        ]);
        $this->signature = new OAuth1Signature($this->clientCredentials);
    }

    public function createHttpClient(): Client
    {
        $client = $this->transport->guzzle();
        $handler = $client->getConfig('handler');
        $client = new Client([
            'handler' => static function (#[\SensitiveParameter] $request, $options) use ($handler) {
                return $handler($request, $options)->then(static function (#[\SensitiveParameter] $response) {
                    $body = \GuzzleHttp\Psr7\Utils::streamFor(OAuth1ResponseClient::body($response));
                    return $response->withBody($body);
                });
            },
            'allow_redirects' => false,
            'http_errors' => false,
        ]);
        return $client;
    }

    protected function nonce($length = 32): string
    {
        return bin2hex(random_bytes(32));
    }

    protected function getHttpClientDefaultHeaders(): array
    {
        return ['Accept' => 'application/x-www-form-urlencoded', 'User-Agent' => 'EvaOAuth/2.0'];
    }

    public function urlTemporaryCredentials(): string
    {
        return $this->provider->requestTokenUrl;
    }

    public function urlAuthorization(): string
    {
        return $this->provider->authorizeUrl;
    }

    public function urlTokenCredentials(): string
    {
        return $this->provider->accessTokenUrl;
    }

    public function urlUserDetails(): string
    {
        return $this->provider->resourceUrl();
    }

    public function userDetails(
        #[\SensitiveParameter] $data,
        #[\SensitiveParameter] TokenCredentials $tokenCredentials,
    ): never {
        throw new UnsupportedOperationException();
    }

    public function userUid(
        #[\SensitiveParameter] $data,
        #[\SensitiveParameter] TokenCredentials $tokenCredentials,
    ): never {
        throw new UnsupportedOperationException();
    }

    public function userEmail(
        #[\SensitiveParameter] $data,
        #[\SensitiveParameter] TokenCredentials $tokenCredentials,
    ): never {
        throw new UnsupportedOperationException();
    }

    public function userScreenName(
        #[\SensitiveParameter] $data,
        #[\SensitiveParameter] TokenCredentials $tokenCredentials,
    ): never {
        throw new UnsupportedOperationException();
    }

    public function __debugInfo(): array
    {
        return ['protocol' => 'oauth1'];
    }
}
