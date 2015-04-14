<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\OAuth1\Token;

use Eva\EvaOAuth\OAuth1\ServiceProviderInterface;
use GuzzleHttp\Message\Response;

/**
 * Interface AccessTokenInterface
 * @package Eva\EvaOAuth\OAuth1\Token
 */
interface AccessTokenInterface
{
    const TYPE_BEARER = 'Bearer';

    /**
     * @param Response $response
     * @param ServiceProviderInterface $serviceProvider
     * @return AccessTokenInterface
     */
    public static function factory(Response $response, ServiceProviderInterface $serviceProvider);

    /**
     * @return string
     */
    public function getTokenSecret();

    /**
     * @param $tokenValue
     * @param $tokenSecret
     * @param array $tokenArray
     */
    public function __construct($tokenValue, $tokenSecret, array $tokenArray = []);
}
