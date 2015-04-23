<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\Utils;

class Text
{
    public static function buildHeaderString(array $header)
    {
        ksort($header);
        $encodedHeader = [];
        foreach ($header as $key => $value) {
            $encodedHeader[] = $key . '="' . urlencode($value) . '"';
        }
        return 'OAuth ' . implode(', ', $encodedHeader);
    }

    public static function generateRandomString($length = 10)
    {
        $length = (int) $length;
        $length = $length > 0 ? $length : 10;
        return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
    }

    public static function buildBaseString($method, $url, array $params)
    {
        ksort($params);

        $encodedParams = [];
        foreach ($params as $key => $value) {
            $encodedParams[] = urlencode($key) . '=' . urlencode($value);
        }
        return implode('&', [
            strtoupper($method),
            urlencode($url),
            urlencode(implode('&', $encodedParams))
        ]);
    }
}
