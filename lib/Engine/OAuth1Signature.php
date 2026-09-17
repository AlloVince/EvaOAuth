<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\Engine;

use League\OAuth1\Client\Signature\HmacSha1Signature;
use Psr\Http\Message\UriInterface;

final class OAuth1Signature extends HmacSha1Signature
{
    protected function normalizeArray(#[\SensitiveParameter] array $array = []): array
    {
        $normalized = [];
        foreach ($array as $key => $value) {
            $normalized[rawurlencode((string) $key)] = rawurlencode($value);
        }
        return $normalized;
    }

    protected function createUrl($uri): UriInterface
    {
        $url = parent::createUrl($uri);
        return $url->getPath() === '' ? $url->withPath('/') : $url;
    }

    public function __debugInfo(): array
    {
        return [];
    }
}
