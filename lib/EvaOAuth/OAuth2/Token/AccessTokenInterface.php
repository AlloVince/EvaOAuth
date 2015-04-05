<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaOAuth\OAuth2\Token;

use Eva\EvaOAuth\OAuth2\ResourceServerInterface;
use GuzzleHttp\Message\Response;

/**
 * Interface AccessTokenInterface
 * @package Eva\EvaOAuth\OAuth2\Token
 */
interface AccessTokenInterface
{
    const TYPE_BEARER = 'Bearer';

    /**
     * @param array $tokenArray
     * @return AccessTokenInterface
     */
    public static function factory(array $tokenArray);

    /**
     * @return string
     */
    public function getTokenType();

    /**
     * @return int
     */
    public function getExpireTimestamp();

    /**
     * @return string
     */
    public function getRefreshToken();

    /**
     * @return string
     */
    public function getScope();

    /**
     * @return array
     */
    public function getExtra();

    /**
     * @param Response $response
     * @param ResourceServerInterface $resouceServer
     */
    public function __construct(Response $response = null, ResourceServerInterface $resouceServer = null);
}
