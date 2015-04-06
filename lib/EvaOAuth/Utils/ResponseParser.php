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

/**
 * Class ResponseParser
 * @package Eva\EvaOAuth\Utils
 */
class ResponseParser
{
    /**
     * @param Response $response
     * @return array
     */
    public static function parseJSON(Response $response)
    {
        return $response->json();
    }

    /**
     * @param Response $response
     * @return array
     */
    public static function parseJSONP(Response $response)
    {
        $responseBody = $response->getBody();

        $lpos = strpos($responseBody, "(");
        $rpos = strrpos($responseBody, ")");
        $responseBody = substr($responseBody, $lpos + 1, $rpos - $lpos - 1);
        return json_decode($responseBody, true);
    }

    /**
     * @param Response $response
     * @return array
     */
    public static function parseQuery(Response $response)
    {
        $responseBody = $response->getBody();
        $params = [];
        parse_str($responseBody, $params);
        return $params;
    }

    /**
     * @param Response $response
     * @param string $format
     * @return array
     */
    public static function parse(Response $response, $format = ResourceServerInterface::FORMAT_JSON)
    {
        switch ($format) {
            case ResourceServerInterface::FORMAT_JSON:
                return self::parseJSON($response);
            case ResourceServerInterface::FORMAT_JSONP:
                return self::parseJSONP($response);
            case ResourceServerInterface::FORMAT_QUERY:
                return self::parseQuery($response);
            default:
                throw new InvalidArgumentException(sprintf("Not able to parse format %s", $format));

        }
    }
}
