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

    const TYPE_OAUTH = 'OAuth';

    /**
     * @param Response $response
     * @param ServiceProviderInterface $serviceProvider
     * @param array $options
     * @return AccessTokenInterface
     */
    public static function factory(Response $response, ServiceProviderInterface $serviceProvider, array $options);

    /**
     * @return string
     */
    public function getTokenSecret();

    /**
     * @return string
     */
    public function getConsumerKey();

    /**
     * @return string
     */
    public function getConsumerSecret();

    /**
     * @param array $tokenArray
     */
    public function __construct(array $tokenArray);
}
