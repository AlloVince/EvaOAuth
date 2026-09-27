<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\Tests\Fixture;

use Eva\EvaOAuth\Exception\ProviderException;
use Eva\EvaOAuth\Identity;
use Eva\EvaOAuth\Provider\OAuth2Provider;

final readonly class DemoProvider extends OAuth2Provider
{
    public function __construct(string $clientId, #[\SensitiveParameter] string $clientSecret, string $redirectUri)
    {
        parent::__construct(
            $clientId,
            $clientSecret,
            $redirectUri,
            'https://auth.demo.example/oauth2/authorize',
            'https://auth.demo.example/oauth2/token',
            'https://api.demo.example/v1/me',
            ['profile:read', 'offline'],
            authorizationParameters: ['audience' => 'demo'],
        );
    }

    public function identity(#[\SensitiveParameter] array $data): Identity
    {
        $account = $data['account'] ?? null;
        if (
            !is_array($account)
            || !isset($account['id'], $account['handle'])
            || !is_string($account['id'])
            || !is_string($account['handle'])
        ) {
            throw new ProviderException();
        }
        return new Identity($account['id'], $account['handle'], self::text($data, 'email'));
    }
}
