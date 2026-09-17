<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\Provider;

use Eva\EvaOAuth\Exception\ProviderException;
use Eva\EvaOAuth\Identity;

final readonly class Google extends OAuth2Provider
{
    public function __construct(string $clientId, #[\SensitiveParameter] string $clientSecret, string $redirectUri)
    {
        parent::__construct(
            $clientId,
            $clientSecret,
            $redirectUri,
            'https://accounts.google.com/o/oauth2/v2/auth',
            'https://oauth2.googleapis.com/token',
            'https://openidconnect.googleapis.com/v1/userinfo',
            ['openid', 'profile', 'email'],
            authorizationParameters: ['access_type' => 'offline'],
            issuer: 'https://accounts.google.com',
        );
    }

    public function identity(#[\SensitiveParameter] array $data): Identity
    {
        if (!isset($data['sub']) || !is_string($data['sub']) || $data['sub'] === '') {
            throw new ProviderException();
        }
        return new Identity(
            $data['sub'],
            self::text($data, 'name'),
            self::text($data, 'email'),
            self::text($data, 'picture'),
            isset($data['email_verified']) && is_bool($data['email_verified']) ? $data['email_verified'] : null,
        );
    }
}
