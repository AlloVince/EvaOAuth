<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\Provider;

use Closure;
use Eva\EvaOAuth\Exception\ConfigurationException;
use Eva\EvaOAuth\Exception\ProviderException;
use Eva\EvaOAuth\Http\UrlPolicy;
use Eva\EvaOAuth\Identity;

readonly class OAuth2Provider implements ProviderInterface, \JsonSerializable
{
    public function __construct(
        public string $clientId,
        #[\SensitiveParameter] public string $clientSecret,
        public string $redirect,
        public string $authorizeUrl,
        public string $accessTokenUrl,
        public string $userUrl,
        public array $scopes = [],
        private ?Closure $identityMapper = null,
        public array $authorizationParameters = [],
        public string $scopeSeparator = ' ',
        public string $clientAuthentication = 'client_secret_post',
        private array $resourceOrigins = [],
        public string $responseScopeSeparator = ' ',
        public ?string $issuer = null,
    ) {
        if (
            $clientId === '' || $clientSecret === ''
            || !in_array($clientAuthentication, ['client_secret_post', 'client_secret_basic'], true)
            || $scopeSeparator === '' || $responseScopeSeparator === ''
        ) {
            throw new ConfigurationException();
        }
        if ($issuer !== null) {
            UrlPolicy::origin($issuer);
            if (parse_url($issuer, PHP_URL_QUERY) !== null) {
                throw new ConfigurationException();
            }
        }
        foreach ([$redirect, $authorizeUrl, $accessTokenUrl, $userUrl] as $url) {
            UrlPolicy::origin($url);
        }
        if (parse_url($authorizeUrl, PHP_URL_QUERY) !== null || parse_url($accessTokenUrl, PHP_URL_QUERY) !== null) {
            throw new ConfigurationException();
        }
        foreach ($resourceOrigins as $origin) {
            if (!is_string($origin) || UrlPolicy::origin($origin) !== $origin) {
                throw new ConfigurationException();
            }
        }
        foreach ($scopes as $scope) {
            if (!is_string($scope) || !preg_match('/\A[\x21\x23-\x5b\x5d-\x7e]+\z/D', $scope)) {
                throw new ConfigurationException();
            }
        }
        foreach ($authorizationParameters as $key => $value) {
            if (
                !is_string($key) || !is_string($value)
                || !in_array(
                    $key,
                    ['access_type', 'prompt', 'login_hint', 'include_granted_scopes', 'hd', 'audience', 'resource'],
                    true,
                )
            ) {
                throw new ConfigurationException();
            }
        }
    }

    final public function protocol(): string
    {
        return 'oauth2';
    }

    final public function redirectUri(): string
    {
        return $this->redirect;
    }

    final public function binding(): string
    {
        return hash('sha256', serialize([
            static::class, $this->protocol(), $this->clientId, $this->clientSecret, $this->redirect,
            $this->authorizeUrl, $this->accessTokenUrl, $this->userUrl, $this->scopes,
            $this->authorizationParameters, $this->scopeSeparator, $this->clientAuthentication, $this->resourceOrigins,
            $this->responseScopeSeparator, $this->issuer,
        ]));
    }

    public function identity(#[\SensitiveParameter] array $data): Identity
    {
        try {
            if ($this->identityMapper !== null) {
                $identity = ($this->identityMapper)($data);
                if (!$identity instanceof Identity) {
                    throw new ProviderException();
                }
                return $identity;
            }
            $id = $data['id'] ?? null;
            if ((!is_string($id) && !is_int($id)) || (string) $id === '') {
                throw new ProviderException();
            }
            return new Identity(
                (string) $id,
                self::text($data, 'name'),
                self::text($data, 'email'),
                self::text($data, 'avatar_url'),
                isset($data['email_verified']) && is_bool($data['email_verified']) ? $data['email_verified'] : null,
            );
        } catch (\Throwable) {
            throw new ProviderException();
        }
    }

    protected static function text(array $data, string $key): ?string
    {
        return isset($data[$key]) && is_string($data[$key]) ? $data[$key] : null;
    }

    final public function resourceUrl(): string
    {
        return $this->userUrl;
    }

    final public function allowedOrigins(): array
    {
        return array_values(array_unique([UrlPolicy::origin($this->userUrl), ...$this->resourceOrigins]));
    }

    final public function jsonSerialize(): array
    {
        return ['protocol' => $this->protocol()];
    }

    final public function __debugInfo(): array
    {
        return $this->jsonSerialize();
    }

    final public function __serialize(): array
    {
        throw new ConfigurationException();
    }
}
