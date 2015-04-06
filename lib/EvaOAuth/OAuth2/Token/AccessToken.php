<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\OAuth2\Token;

use Eva\EvaOAuth\OAuth2\ResourceServerInterface;
use Eva\EvaOAuth\Utils\ResponseParser;
use Eva\EvaOAuth\Exception\InvalidArgumentException;
use GuzzleHttp\Message\Response;
use Eva\EvaOAuth\Token\AccessTokenInterface as BaseTokenInterface;

/**
 * Class AccessToken
 * @package Eva\EvaOAuth\OAuth2\Token
 */
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
     * @var string
     */
    protected $tokenVersion = BaseTokenInterface::VERSION_OAUTH2;

    /**
     * @var array
     */
    protected $extra;


    /**
     * @return string
     */
    public function getTokenVersion()
    {
        return $this->tokenVersion;
    }

    /**
     * @param Response $response
     * @param ResourceServerInterface $resourceServer
     * @return AccessTokenInterface
     */
    public static function factory(Response $response, ResourceServerInterface $resourceServer)
    {
        $rawToken = ResponseParser::parse($response, $resourceServer->getAccessTokenFormat());
        $tokenValue = empty($rawToken['access_token']) ? '' : $rawToken['access_token'];
        $token = new static($tokenValue);
        $token->setResponse($response);
        foreach ($rawToken as $key => $value) {
            $token->$key = $value;
        }
        return $token;
    }

    public function setResponse(Response $response)
    {
        $this->response = $response;
        return $this;
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

    /**
     * @param $name
     * @param $value
     * @return $this
     */
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
     * @param string $tokenValue
     * @param array $tokenArray
     */
    public function __construct($tokenValue, array $tokenArray = array())
    {
        $this->tokenValue = (string) $tokenValue;
        if (!$this->tokenValue) {
            throw new InvalidArgumentException("No token value input");
        }

        foreach ($tokenArray as $key => $value) {
            $this->$key = $value;
        }
    }
}
