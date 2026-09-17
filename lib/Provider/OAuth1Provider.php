<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\Provider;

use Closure;
use Eva\EvaOAuth\Exception\ConfigurationException;
use Eva\EvaOAuth\Exception\ProviderException;
use Eva\EvaOAuth\Http\UrlPolicy;
use Eva\EvaOAuth\Identity;

readonly class OAuth1Provider implements ProviderInterface, \JsonSerializable
{
    public function __construct(
        public string $clientId,
        #[\SensitiveParameter] public string $clientSecret,
        public string $redirect,
        public string $requestTokenUrl,
        public string $authorizeUrl,
        public string $accessTokenUrl,
        public string $userUrl,
        private ?Closure $identityMapper = null,
        public array $authorizationParameters = [],
        private array $resourceOrigins = [],
    ) {
        if ($clientId === '' || $clientSecret === '') {
            throw new ConfigurationException();
        }
        foreach ([$redirect, $requestTokenUrl, $authorizeUrl, $accessTokenUrl, $userUrl] as $url) {
            UrlPolicy::origin($url);
        }
        foreach ([$requestTokenUrl, $authorizeUrl, $accessTokenUrl] as $url) {
            if (parse_url($url, PHP_URL_QUERY) !== null) {
                throw new ConfigurationException();
            }
        }
        foreach ($resourceOrigins as $origin) {
            if (!is_string($origin) || UrlPolicy::origin($origin) !== $origin) {
                throw new ConfigurationException();
            }
        }
        foreach ($authorizationParameters as $key => $value) {
            if (
                !is_string($key) || !is_string($value)
                || !in_array($key, ['perms', 'force_login', 'screen_name'], true)
            ) {
                throw new ConfigurationException();
            }
        }
    }

    final public function protocol(): string
    {
        return 'oauth1';
    }

    final public function redirectUri(): string
    {
        return $this->redirect;
    }

    final public function binding(): string
    {
        return hash('sha256', serialize([
            static::class, $this->protocol(), $this->clientId, $this->clientSecret, $this->redirect,
            $this->requestTokenUrl, $this->authorizeUrl, $this->accessTokenUrl, $this->userUrl,
            $this->authorizationParameters, $this->resourceOrigins,
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
