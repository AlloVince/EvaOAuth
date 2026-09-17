<?php

declare(strict_types=1);

namespace Eva\EvaOAuth;

use Eva\EvaOAuth\Exception\ConfigurationException;

final readonly class Token implements \JsonSerializable
{
    public function __construct(
        public string $provider,
        public string $protocol,
        #[\SensitiveParameter] public string $accessToken,
        #[\SensitiveParameter] public ?string $refreshToken = null,
        public ?int $expiresAt = null,
        #[\SensitiveParameter] public ?string $tokenSecret = null,
        public array $scopes = [],
    ) {
        if (
            $provider === '' || !in_array($protocol, ['oauth1', 'oauth2'], true)
            || $accessToken === '' || preg_match('/[\x00-\x20\x7f]/', $accessToken)
            || ($refreshToken !== null && $refreshToken === '')
            || ($expiresAt !== null && $expiresAt < 0)
            || ($protocol === 'oauth1' && ($tokenSecret === null || $tokenSecret === '' || $refreshToken !== null))
            || ($protocol === 'oauth2'
                && ($tokenSecret !== null || !preg_match('/\A[A-Za-z0-9._~+\/=-]+\z/D', $accessToken)))
        ) {
            throw new ConfigurationException();
        }
        foreach ($scopes as $scope) {
            if (!is_string($scope) || !preg_match('/\A[\x21\x23-\x5b\x5d-\x7e]+\z/D', $scope)) {
                throw new ConfigurationException();
            }
        }
    }

    public function isExpired(?int $now = null): bool
    {
        return $this->expiresAt !== null && $this->expiresAt <= ($now ?? time());
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public static function fromArray(#[\SensitiveParameter] array $data): self
    {
        try {
            if (
                array_diff(array_keys($data), [
                'provider', 'protocol', 'accessToken', 'refreshToken', 'expiresAt', 'tokenSecret', 'scopes',
                ])
            ) {
                throw new ConfigurationException();
            }
            return new self(...$data);
        } catch (\Throwable) {
            throw new ConfigurationException();
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'provider' => $this->provider,
            'protocol' => $this->protocol,
            'expiresAt' => $this->expiresAt,
            'scopes' => $this->scopes,
        ];
    }

    public function __debugInfo(): array
    {
        return $this->jsonSerialize();
    }

    public function __serialize(): array
    {
        throw new ConfigurationException();
    }
}
