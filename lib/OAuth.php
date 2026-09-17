<?php

declare(strict_types=1);

namespace Eva\EvaOAuth;

use Eva\EvaOAuth\Engine\OAuth1Engine;
use Eva\EvaOAuth\Engine\OAuth2Engine;
use Eva\EvaOAuth\Exception\CallbackException;
use Eva\EvaOAuth\Exception\ConfigurationException;
use Eva\EvaOAuth\Exception\OAuthException;
use Eva\EvaOAuth\Exception\ProviderException;
use Eva\EvaOAuth\Exception\UnsupportedOperationException;
use Eva\EvaOAuth\Http\ResponseData;
use Eva\EvaOAuth\Http\Transport;
use Eva\EvaOAuth\Http\UrlPolicy;
use Eva\EvaOAuth\Provider\OAuth1Provider;
use Eva\EvaOAuth\Provider\OAuth2Provider;
use Eva\EvaOAuth\Provider\ProviderInterface;
use Eva\EvaOAuth\State\OAuth1Transaction;
use Eva\EvaOAuth\State\OAuth2Transaction;
use Eva\EvaOAuth\State\SessionStateStore;
use Eva\EvaOAuth\State\StateStore;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class OAuth
{
    private readonly StateStore $stateStore;
    private readonly Transport $transport;

    public function __construct(
        private readonly array $providers,
        ?StateStore $stateStore = null,
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null,
    ) {
        $redirects = [];
        foreach ($providers as $name => $provider) {
            $validName = is_string($name) && preg_match('/\A[a-zA-Z][a-zA-Z0-9_-]{0,63}\z/D', $name) === 1;
            if (!$validName || !$provider instanceof ProviderInterface) {
                throw new ConfigurationException();
            }
            if (
                (!$provider instanceof OAuth2Provider && !$provider instanceof OAuth1Provider)
                || in_array($provider->redirectUri(), $redirects, true)
            ) {
                throw new ConfigurationException();
            }
            UrlPolicy::origin($provider->redirectUri());
            $redirects[] = $provider->redirectUri();
        }
        if ($providers === []) {
            throw new ConfigurationException();
        }
        $this->stateStore = $stateStore ?? new SessionStateStore();
        $this->transport = new Transport($httpClient, $logger);
    }

    public function authorize(string $name): string
    {
        $provider = $this->provider($name);
        $start = $this->engine($provider)->begin();
        if ($provider instanceof OAuth2Provider) {
            $transaction = new OAuth2Transaction(
                $provider->binding(),
                $provider->redirectUri(),
                $start['verifier'],
                $provider->issuer,
            );
            $key = 'oauth2:' . $start['state'];
        } else {
            $transaction = new OAuth1Transaction(
                $provider->binding(),
                $provider->redirectUri(),
                $start['token'],
                $start['secret'],
            );
            $key = 'oauth1:' . $start['token'];
        }
        $this->stateStore->put($key, $transaction);
        return $start['url'];
    }

    public function callback(string $name, #[\SensitiveParameter] array $query): AuthorizationResult
    {
        $token = $this->exchange($name, $query);
        return new AuthorizationResult($name, $token, $this->user($name, $token));
    }

    public function exchange(string $name, #[\SensitiveParameter] array $query): Token
    {
        $provider = $this->provider($name);
        if ($provider instanceof OAuth2Provider) {
            $state = $this->parameter($query, 'state');
            if (!preg_match('/\A[a-f0-9]{64}\z/D', $state)) {
                throw new CallbackException();
            }
            $transaction = $this->stateStore->consume('oauth2:' . $state);
            if (!$transaction instanceof OAuth2Transaction) {
                throw new CallbackException();
            }
            $this->binding($provider, $transaction->binding, $transaction->redirectUri);
            $this->callbackShape(
                $query,
                ['state', 'code', 'error', 'error_description', 'error_uri', 'scope', 'iss'],
                ['oauth_token', 'oauth_verifier', 'oauth_problem'],
            );
            $this->issuer($query, $transaction->issuer);
            $code = $this->parameter($query, 'code');
            return (new OAuth2Engine($provider, $this->transport))->exchange($code, $transaction->verifier);
        }
        $token = $this->parameter($query, array_key_exists('denied', $query) ? 'denied' : 'oauth_token');
        $transaction = $this->stateStore->consume('oauth1:' . $token);
        if (!$transaction instanceof OAuth1Transaction || !hash_equals($transaction->token, $token)) {
            throw new CallbackException();
        }
        $this->binding($provider, $transaction->binding, $transaction->redirectUri);
        $this->callbackShape(
            $query,
            ['oauth_token', 'oauth_verifier', 'denied', 'error'],
            ['code', 'state', 'iss', 'oauth_problem'],
        );
        if (array_key_exists('denied', $query) || !$provider instanceof OAuth1Provider) {
            throw new CallbackException();
        }
        return (new OAuth1Engine($provider, $this->transport))->exchange(
            $token,
            $transaction->secret,
            $this->parameter($query, 'oauth_verifier'),
        );
    }

    public function refresh(string $name, #[\SensitiveParameter] Token $token): Token
    {
        $provider = $this->provider($name);
        $this->tokenBinding($provider, $token);
        if (!$provider instanceof OAuth2Provider) {
            throw new UnsupportedOperationException();
        }
        return (new OAuth2Engine($provider, $this->transport))->refresh($token);
    }

    public function user(string $name, #[\SensitiveParameter] Token $token): Identity
    {
        $provider = $this->provider($name);
        $response = $this->request(
            $name,
            $token,
            new Request('GET', $provider->resourceUrl(), [
                'Accept' => 'application/json',
                'User-Agent' => 'EvaOAuth/2.0',
            ]),
        );
        try {
            return $provider->identity(ResponseData::object($response));
        } catch (\Throwable) {
            throw new ProviderException();
        }
    }

    public function request(
        string $name,
        #[\SensitiveParameter] Token $token,
        #[\SensitiveParameter] RequestInterface $request,
    ): ResponseInterface {
        $provider = $this->provider($name);
        $this->tokenBinding($provider, $token);
        if ($token->isExpired()) {
            throw new ProviderException();
        }
        UrlPolicy::resource($request, $provider->allowedOrigins());
        try {
            $response = $this->engine($provider)->request($token, $request);
            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                throw new ProviderException();
            }
            return $response;
        } catch (OAuthException $error) {
            throw $error;
        } catch (\Throwable) {
            throw new ProviderException();
        }
    }

    private function provider(string $name): ProviderInterface
    {
        return $this->providers[$name] ?? throw new ConfigurationException();
    }

    private function engine(ProviderInterface $provider): OAuth2Engine|OAuth1Engine
    {
        if ($provider instanceof OAuth2Provider) {
            return new OAuth2Engine($provider, $this->transport);
        }
        if ($provider instanceof OAuth1Provider) {
            return new OAuth1Engine($provider, $this->transport);
        }
        throw new ConfigurationException();
    }

    private function parameter(#[\SensitiveParameter] array $query, string $key): string
    {
        $value = $query[$key] ?? null;
        if (!is_string($value) || $value === '' || strlen($value) > 8192 || preg_match('/[\x00-\x20\x7f]/', $value)) {
            throw new CallbackException();
        }
        return $value;
    }

    private function callbackShape(#[\SensitiveParameter] array $query, array $recognized, array $forbidden): void
    {
        foreach ($query as $key => $value) {
            if (is_string($key) && in_array($key, $forbidden, true)) {
                throw new CallbackException();
            }
            if (!is_string($key) || !in_array($key, $recognized, true)) {
                continue;
            }
            if (!is_string($value) || strlen($value) > 8192) {
                throw new CallbackException();
            }
        }
        if (array_key_exists('error', $query)) {
            throw new CallbackException();
        }
    }

    private function issuer(#[\SensitiveParameter] array $query, ?string $expected): void
    {
        $received = $query['iss'] ?? null;
        if ($received === null) {
            return;
        }
        if ($expected === null || !is_string($received) || !hash_equals($expected, $received)) {
            throw new CallbackException();
        }
    }

    private function binding(ProviderInterface $provider, string $binding, string $redirect): void
    {
        if (!hash_equals($provider->binding(), $binding) || $provider->redirectUri() !== $redirect) {
            throw new CallbackException();
        }
    }

    private function tokenBinding(ProviderInterface $provider, #[\SensitiveParameter] Token $token): void
    {
        if (!hash_equals($provider->binding(), $token->provider) || $provider->protocol() !== $token->protocol) {
            throw new ConfigurationException();
        }
    }

    public function __debugInfo(): array
    {
        return ['providers' => array_keys($this->providers)];
    }
}
