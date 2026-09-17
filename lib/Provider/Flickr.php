<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\Provider;

use Eva\EvaOAuth\Exception\ConfigurationException;
use Eva\EvaOAuth\Exception\ProviderException;
use Eva\EvaOAuth\Identity;

final readonly class Flickr extends OAuth1Provider
{
    public function __construct(
        string $clientId,
        #[\SensitiveParameter] string $clientSecret,
        string $redirectUri,
        string $permissions = 'read',
    ) {
        if (!in_array($permissions, ['read', 'write', 'delete'], true)) {
            throw new ConfigurationException();
        }
        parent::__construct(
            $clientId,
            $clientSecret,
            $redirectUri,
            'https://www.flickr.com/services/oauth/request_token',
            'https://www.flickr.com/services/oauth/authorize',
            'https://www.flickr.com/services/oauth/access_token',
            'https://api.flickr.com/services/rest/?method=flickr.test.login&format=json&nojsoncallback=1',
            authorizationParameters: ['perms' => $permissions],
        );
    }

    public function identity(#[\SensitiveParameter] array $data): Identity
    {
        $user = $data['user'] ?? null;
        if (
            ($data['stat'] ?? null) !== 'ok' || !is_array($user)
            || !isset($user['id']) || !is_string($user['id']) || $user['id'] === ''
            || !isset($user['username']) || !is_array($user['username'])
            || !isset($user['username']['_content']) || !is_string($user['username']['_content'])
        ) {
            throw new ProviderException();
        }
        return new Identity($user['id'], $user['username']['_content']);
    }
}
