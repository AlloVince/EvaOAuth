<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\Engine;

use Eva\EvaOAuth\Exception\ProviderException;
use Eva\EvaOAuth\Exception\TransportException;
use Eva\EvaOAuth\Exception\UnsupportedOperationException;
use Eva\EvaOAuth\Http\Transport;
use Eva\EvaOAuth\Http\UrlPolicy;
use Eva\EvaOAuth\Provider\OAuth2Provider;
use Eva\EvaOAuth\Token;
use League\OAuth2\Client\OptionProvider\PostAuthOptionProvider;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class OAuth2Engine
{
    public function __construct(private readonly OAuth2Provider $provider, private readonly Transport $transport)
    {
    }

    public function begin(): array
    {
        $engine = $this->engine();
        $state = bin2hex(random_bytes(32));
        $url = $engine->getAuthorizationUrl($this->provider->authorizationParameters + ['state' => $state]);
        return ['url' => $url, 'state' => $state, 'verifier' => $engine->getPkceCode()];
    }

    public function exchange(#[\SensitiveParameter] string $code, #[\SensitiveParameter] string $verifier): Token
    {
        $engine = $this->engine();
        $engine->setPkceCode($verifier);
        return $this->grant($engine, 'authorization_code', ['code' => $code]);
    }

    public function refresh(#[\SensitiveParameter] Token $token): Token
    {
        if ($token->refreshToken === null) {
            throw new UnsupportedOperationException();
        }
        return $this->grant($this->engine(), 'refresh_token', ['refresh_token' => $token->refreshToken], $token);
    }

    public function request(
        #[\SensitiveParameter] Token $token,
        #[\SensitiveParameter] RequestInterface $request,
    ): ResponseInterface {
        UrlPolicy::resource($request, $this->provider->allowedOrigins());
        return $this->transport->send($request->withHeader('Authorization', 'Bearer ' . $token->accessToken));
    }

    private function engine(): HardenedOAuth2Provider
    {
        $basic = $this->provider->clientAuthentication === 'client_secret_basic';
        return new HardenedOAuth2Provider([
            'clientId' => $this->provider->clientId,
            'clientSecret' => $this->provider->clientSecret,
            'redirectUri' => $this->provider->redirectUri(),
            'urlAuthorize' => $this->provider->authorizeUrl,
            'urlAccessToken' => $this->provider->accessTokenUrl,
            'urlResourceOwnerDetails' => $this->provider->userUrl,
            'scopes' => $this->provider->scopes,
            'scopeSeparator' => $this->provider->scopeSeparator,
            'pkceMethod' => 'S256',
        ], [
            'httpClient' => $this->transport->guzzle(),
            'optionProvider' => $basic ? new EncodedBasicAuthOptionProvider() : new PostAuthOptionProvider(),
        ]);
    }

    private function grant(
        #[\SensitiveParameter] HardenedOAuth2Provider $engine,
        string $grant,
        #[\SensitiveParameter] array $options,
        #[\SensitiveParameter] ?Token $previous = null,
    ): Token {
        try {
            $result = $engine->getAccessToken($grant, $options);
            $values = $result->getValues();
            $scopes = array_key_exists('scope', $values)
                ? ($values['scope'] === ''
                    ? [] : explode($this->provider->responseScopeSeparator, $values['scope']))
                : (($previous !== null ? $previous->scopes : null) ?? $this->provider->scopes);
            return new Token(
                $this->provider->binding(),
                'oauth2',
                $result->getToken(),
                $result->getRefreshToken() ?? $previous?->refreshToken,
                $result->getExpires(),
                scopes: $scopes,
            );
        } catch (TransportException) {
            throw new TransportException();
        } catch (\Throwable) {
            throw new ProviderException();
        }
    }

    public function __debugInfo(): array
    {
        return ['protocol' => 'oauth2'];
    }
}
