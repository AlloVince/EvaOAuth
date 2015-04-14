<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\Utils;

class Text
{
    public static function getHeaderString(array $header)
    {
        ksort($header);
        $encodedHeader = [];
        foreach ($header as $key => $value) {
            $encodedHeader[] = $key . '="' . urlencode($value) . '"';
        }
        return 'OAuth ' . implode(', ', $encodedHeader);
    }

    public static function getRandomString($length = 10)
    {
        $length = (int) $length;
        $length = $length > 0 ? $length : 10;
        return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
    }

    public static function getBaseString($method, $url, array $params)
    {
        ksort($params);

        $encodedParams = [];
        foreach ($params as $key => $value) {
            $encodedParams[] = urlencode($key) . '%3D' . urlencode($value);
        }
        return implode('&', [
            strtoupper($method),
            urlencode($url),
            implode('%26', $encodedParams)
        ]);
    }
}
