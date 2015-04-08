<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\OAuth1\Token;

use Eva\EvaOAuth\OAuth1\ServiceProviderInterface;
use Eva\EvaOAuth\Token\AccessTokenInterface as BaseTokenInterface;
use GuzzleHttp\Message\Response;

class AccessToken implements AccessTokenInterface, BaseTokenInterface
{

    /**
     * @param Response $response
     * @param ServiceProviderInterface $serviceProvider
     * @return AccessTokenInterface
     */
    public static function factory(Response $response, ServiceProviderInterface $serviceProvider)
    {
        // TODO: Implement factory() method.
    }

    /**
     * @return string
     */
    public function getTokenSecret()
    {
        // TODO: Implement getTokenSecret() method.
    }

    /**
     * @param string $tokenValue
     * @param string $tokenSecret
     */
    public function __construct($tokenValue, $tokenSecret)
    {
        // TODO: Implement __construct() method.
    }

    /**
     * @return string
     */
    public function getTokenVersion()
    {
        // TODO: Implement getTokenVersion() method.
    }

    /**
     * @return string
     */
    public function getTokenValue()
    {
        // TODO: Implement getTokenValue() method.
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        // TODO: Implement getResponse() method.
    }
}
