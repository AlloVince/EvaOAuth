<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\OAuth1\Token;

use Eva\EvaOAuth\OAuth1\ServiceProviderInterface;
use Eva\EvaOAuth\Token\AccessTokenInterface as BaseTokenInterface;
use Eva\EvaOAuth\Utils\ResponseParser;
use GuzzleHttp\Message\Response;

/**
 * Class AccessToken
 * @package Eva\EvaOAuth\OAuth1\Token
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
    protected $tokenSecret;

    /**
     * @var string
     */
    protected $tokenType = AccessTokenInterface::TYPE_BEARER;

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
     * @param ServiceProviderInterface $serviceProvider
     * @return static
     */
    public static function factory(Response $response, ServiceProviderInterface $serviceProvider)
    {
        $rawToken = ResponseParser::parse($response, $serviceProvider->getAccessTokenFormat());
        $tokenValue = empty($rawToken['oauth_token']) ? '' : $rawToken['oauth_token'];
        $tokenSecret = empty($rawToken['oauth_token_secret']) ? '' : $rawToken['oauth_token_secret'];
        $token = new static($tokenValue, $tokenSecret);
        $token->setResponse($response);
        foreach ($rawToken as $key => $value) {
            $token->$key = $value;
        }
        return $token;
    }

    /**
     * @param Response $response
     * @return $this
     */
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

    public function getTokenSecret()
    {
        return $this->tokenSecret;
    }

    /**
     * @return string
     */
    public function getTokenType()
    {
        return $this->tokenType;
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
     * @param string $tokenValue
     * @param string $tokenSecret
     * @param array $tokenArray
     * @throws InvalidArgumentException
     */
    public function __construct($tokenValue, $tokenSecret, array $tokenArray = [])
    {
        $this->tokenValue = (string)$tokenValue;
        $this->tokenSecret = (string)$tokenSecret;
        if (!$this->tokenValue) {
            throw new InvalidArgumentException("No token value input");
        }

        foreach ($tokenArray as $key => $value) {
            $this->$key = $value;
        }
    }
}
