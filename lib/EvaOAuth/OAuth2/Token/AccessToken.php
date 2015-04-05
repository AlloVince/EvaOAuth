<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\OAuth2\Token;

use Eva\EvaOAuth\OAuth2\ResourceServerInterface;
use Eva\EvaOAuth\Utils\ResponseParser;
use GuzzleHttp\Message\Response;
use Eva\EvaOAuth\Token\AccessTokenInterface as BaseTokenInterface;

class AccessToken implements AccessTokenInterface, BaseTokenInterface
{
    /**
     * @var Response
     */
    protected $response;

    /**
     * @var string
     */
    protected $tokenValue;

    /**
     * @var string
     */
    protected $tokenType = AccessTokenInterface::TYPE_BEARER;

    /**
     * @var int
     */
    protected $expireTimestamp;

    /**
     * @var string
     */
    protected $refreshToken;

    /**
     * @var string
     */
    protected $scope;

    /**
     * @var array
     */
    protected $extra;

    /**
     * @var string
     */
    protected $tokenVersion = BaseTokenInterface::VERSION_OAUTH2;

    /**
     * @return string
     */
    public function getTokenVersion()
    {
        return $this->tokenVersion;
    }

    /**
     * @param array $tokenArray
     * @return AccessTokenInterface
     */
    public static function factory(array $tokenArray)
    {
        // TODO: Implement factory() method.
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return string
     */
    public function getTokenValue()
    {
        return $this->tokenValue;
    }

    /**
     * @return string
     */
    public function getTokenType()
    {
        return $this->tokenType;
    }

    /**
     * @return int
     */
    public function getExpireTimestamp()
    {
        return $this->expireTimestamp;
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @return array
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        // TODO: Implement toArray() method.
    }

    public function __set($name, $value)
    {
        if (isset($this->$name)) {
            $this->$name = $value;
            return $this;
        }

        $fieldsMapping = [
            'access_token' => 'tokenValue',
            'refresh_token' => 'refreshToken',
        ];

        if (array_key_exists($name, $fieldsMapping)) {
            $field = $fieldsMapping[$name];
            $this->$field = $value;
        } else {
            if ($name === 'expires_in') {
                $this->expireTimestamp = $value + time();
            } else {
                $this->extra[$name] = $value;
            }
        }
        return $this;
    }

    /**
     * @param Response $response
     * @param ResourceServerInterface $resouceServer
     */
    public function __construct(Response $response = null, ResourceServerInterface $resouceServer = null)
    {
        if ($response && $resouceServer) {
            $this->response = $response;
            $rawToken = ResponseParser::parse($response, $resouceServer->getAccessTokenFormat());
            foreach ($rawToken as $key => $value) {
                $this->$key = $value;
            }
        }
    }
}
