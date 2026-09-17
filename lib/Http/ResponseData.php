<?php

declare(strict_types=1);

namespace Eva\EvaOAuth\Http;

use Eva\EvaOAuth\Exception\ProviderException;
use Psr\Http\Message\ResponseInterface;

final class ResponseData
{
    public static function object(#[\SensitiveParameter] ResponseInterface $response, bool $allowForm = false): array
    {
        try {
            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                throw new ProviderException();
            }
            $body = $response->getBody();
            if ($body->isSeekable()) {
                $body->rewind();
            }
            $content = '';
            while (!$body->eof() && strlen($content) <= 1048576) {
                $chunk = $body->read(8192);
                if ($chunk === '') {
                    throw new ProviderException();
                }
                $content .= $chunk;
            }
            if (strlen($content) > 1048576) {
                throw new ProviderException();
            }
            $type = strtolower(trim(explode(';', $response->getHeaderLine('Content-Type'))[0]));
            if ($allowForm && $type === 'application/x-www-form-urlencoded') {
                $data = [];
                foreach (explode('&', $content) as $pair) {
                    $parts = explode('=', $pair, 2);
                    $key = urldecode($parts[0]);
                    $duplicate = array_key_exists($key, $data);
                    if (count($parts) !== 2 || !preg_match('/\A[a-zA-Z_][a-zA-Z0-9_]*\z/D', $key) || $duplicate) {
                        throw new ProviderException();
                    }
                    $data[$key] = urldecode($parts[1]);
                }
            } else {
                $decoded = json_decode($content, false, 32, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
                if (!$decoded instanceof \stdClass) {
                    throw new ProviderException();
                }
                $data = json_decode($content, true, 32, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
            }
            if (!is_array($data) || array_key_exists('error', $data)) {
                throw new ProviderException();
            }
            return $data;
        } catch (\Throwable) {
            throw new ProviderException();
        }
    }
}
