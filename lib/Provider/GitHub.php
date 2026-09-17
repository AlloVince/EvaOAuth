<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\Provider;

final readonly class GitHub extends OAuth2Provider
{
    public function __construct(string $clientId, #[\SensitiveParameter] string $clientSecret, string $redirectUri)
    {
        parent::__construct(
            $clientId,
            $clientSecret,
            $redirectUri,
            'https://github.com/login/oauth/authorize',
            'https://github.com/login/oauth/access_token',
            'https://api.github.com/user',
            ['read:user', 'user:email'],
            responseScopeSeparator: ',',
        );
    }
}
