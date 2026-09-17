<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\Http;

use Eva\EvaOAuth\Exception\ConfigurationException;
use Eva\EvaOAuth\Exception\ProviderException;
use Psr\Http\Message\RequestInterface;

final class UrlPolicy
{
    public static function origin(string $url): string
    {
        $parts = parse_url($url);
        if (
            !is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])
            || preg_match('/[\x00-\x20\x7f\\\\]/', $url)
        ) {
            throw new ConfigurationException();
        }
        $port = isset($parts['port']) && $parts['port'] !== 443 ? ':' . $parts['port'] : '';
        return 'https://' . strtolower($parts['host']) . $port;
    }

    public static function resource(#[\SensitiveParameter] RequestInterface $request, array $origins): void
    {
        try {
            $origin = self::origin((string) $request->getUri());
            $host = $request->getUri()->getHost();
            $port = $request->getUri()->getPort();
            $authority = $host . ($port !== null ? ':' . $port : '');
            if (
                !in_array($origin, $origins, true)
                || strtolower($request->getHeaderLine('Host')) !== strtolower($authority)
                || $request->hasHeader('Proxy-Authorization')
            ) {
                throw new ProviderException();
            }
            parse_str($request->getUri()->getQuery(), $query);
            if (array_intersect(['access_token', 'oauth_token', 'client_secret'], array_keys($query))) {
                throw new ProviderException();
            }
        } catch (\Throwable) {
            throw new ProviderException();
        }
    }
}
