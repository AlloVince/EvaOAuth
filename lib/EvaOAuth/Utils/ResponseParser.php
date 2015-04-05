<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\Utils;

use Eva\EvaOAuth\OAuth2\ResourceServerInterface;
use EvaOAuth\Exception\InvalidArgumentException;
use GuzzleHttp\Message\Response;

class ResponseParser
{
    public static function parseJSON(Response $response)
    {
        return $response->json();
    }

    public static function parseJSONP(Response $response)
    {
        return $response->json();
    }

    public static function parseXML(Response $response)
    {
        return $response->xml();
    }

    public static function parse(Response $response, $format = ResourceServerInterface::FORMAT_JSON)
    {
        switch ($format) {
            case ResourceServerInterface::FORMAT_JSON:
                return self::parseJSON($response);
            case ResourceServerInterface::FORMAT_JSONP:
                return self::parseJSONP($response);
            case ResourceServerInterface::FORMAT_XML:
                return self::parseXML($response);
            default:
                throw new InvalidArgumentException(sprintf("Not able to parse format %s", $format));

        }
    }
}
