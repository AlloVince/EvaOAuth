<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\Engine;

use Eva\EvaOAuth\Exception\ProviderException;
use Eva\EvaOAuth\Http\ResponseData;
use League\OAuth2\Client\Provider\GenericProvider;
use Psr\Http\Message\ResponseInterface;

final class HardenedOAuth2Provider extends GenericProvider
{
    protected function getRandomPkceCode($length = 64)
    {
        return rtrim(strtr(base64_encode(random_bytes(64)), '+/', '-_'), '=');
    }

    protected function parseResponse(#[\SensitiveParameter] ResponseInterface $response)
    {
        return ResponseData::object($response, true);
    }

    protected function checkResponse(#[\SensitiveParameter] ResponseInterface $response, #[\SensitiveParameter] $data)
    {
        $valid = $response->getStatusCode() >= 200 && $response->getStatusCode() < 300
            && is_array($data) && !array_key_exists('error', $data);
        if (!$valid) {
            throw new ProviderException();
        }
    }

    protected function prepareAccessTokenResponse(#[\SensitiveParameter] array $result)
    {
        if (
            !isset($result['access_token'], $result['token_type']) || !is_string($result['access_token'])
            || !preg_match('/\A[A-Za-z0-9._~+\/=-]+\z/D', $result['access_token'])
            || strlen($result['access_token']) > 16384
            || !is_string($result['token_type']) || strtolower($result['token_type']) !== 'bearer'
        ) {
            throw new ProviderException();
        }
        $clean = ['access_token' => $result['access_token'], 'token_type' => 'Bearer'];
        if (array_key_exists('expires_in', $result)) {
            $expiry = $result['expires_in'];
            $malformed = (!is_int($expiry) && !is_string($expiry))
                || !preg_match('/\A[0-9]{1,10}\z/D', (string) $expiry) || (int) $expiry > 2147483647;
            if ($malformed) {
                throw new ProviderException();
            }
            $clean['expires_in'] = (int) $expiry;
        } elseif (array_key_exists('expires', $result)) {
            throw new ProviderException();
        }
        if (array_key_exists('refresh_token', $result)) {
            $token = $result['refresh_token'];
            $invalid = !is_string($token) || $token === ''
                || strlen($token) > 16384 || preg_match('/[\x00-\x20\x7f]/', $token);
            if ($invalid) {
                throw new ProviderException();
            }
            $clean['refresh_token'] = $token;
        }
        if (array_key_exists('scope', $result)) {
            $pattern = '/\A[\x21\x23-\x5b\x5d-\x7e]+(?: [\x21\x23-\x5b\x5d-\x7e]+)*\z/D';
            $invalid = !is_string($result['scope'])
                || ($result['scope'] !== '' && preg_match($pattern, $result['scope']) !== 1);
            if ($invalid) {
                throw new ProviderException();
            }
            $clean['scope'] = $result['scope'];
        }
        return $clean;
    }

    protected function getDefaultHeaders()
    {
        return ['Accept' => 'application/json'];
    }

    public function __debugInfo(): array
    {
        return ['protocol' => 'oauth2'];
    }
}
