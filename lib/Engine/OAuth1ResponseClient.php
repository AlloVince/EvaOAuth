<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\Engine;

use Eva\EvaOAuth\Exception\ProviderException;
use Eva\EvaOAuth\Http\ResponseData;
use Psr\Http\Message\ResponseInterface;

final class OAuth1ResponseClient
{
    public static function body(#[\SensitiveParameter] ResponseInterface $response): string
    {
        $type = strtolower(trim(explode(';', $response->getHeaderLine('Content-Type'))[0]));
        if ($response->getStatusCode() !== 200 || $type !== 'application/x-www-form-urlencoded') {
            throw new ProviderException();
        }
        $stream = $response->getBody();
        if ($stream->isSeekable()) {
            $stream->rewind();
        }
        $content = \GuzzleHttp\Psr7\Utils::copyToString($stream, 1048577);
        if (strlen($content) > 1048576 || preg_match('/%(?![a-fA-F0-9]{2})/', $content)) {
            throw new ProviderException();
        }
        $data = ResponseData::object($response->withBody(\GuzzleHttp\Psr7\Utils::streamFor($content)), true);
        foreach ($data as $value) {
            if (!is_string($value) || preg_match('/[\x00-\x1f\x7f]/', $value)) {
                throw new ProviderException();
            }
        }
        foreach (['oauth_token', 'oauth_token_secret'] as $key) {
            $value = $data[$key] ?? null;
            if (
                !is_string($value) || $value === '' || strlen($value) > 8192
                || preg_match('/[\x00-\x20\x7f]/', $value)
            ) {
                throw new ProviderException();
            }
        }
        if (
            isset($data['oauth_problem'])
            || (isset($data['oauth_callback_confirmed']) && $data['oauth_callback_confirmed'] !== 'true')
        ) {
            throw new ProviderException();
        }
        return http_build_query($data, '', '&', PHP_QUERY_RFC3986);
    }

    public function __debugInfo(): array
    {
        return [];
    }
}
