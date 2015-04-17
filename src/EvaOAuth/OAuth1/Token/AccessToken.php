<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\OAuth1\Token;

use Eva\EvaOAuth\Exception\InvalidArgumentException;
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
    protected $tokenVersion = BaseTokenInterface::VERSION_OAUTH1;

    /**
     * @var string
     */
    protected $consumerKey;

    /**
     * @var string
     */
    protected $consumerSecret;

    /**
     * @var array
     */
    protected $extra;

    /**
     * @param Response $response
     * @param ServiceProviderInterface $serviceProvider
     * @param array $options
     * @return AccessToken
     */
    public static function factory(Response $response, ServiceProviderInterface $serviceProvider, array $options)
    {
        $rawToken = ResponseParser::parse($response, $serviceProvider->getAccessTokenFormat());
        $tokenValue = empty($rawToken['oauth_token']) ? '' : $rawToken['oauth_token'];
        $tokenSecret = empty($rawToken['oauth_token_secret']) ? '' : $rawToken['oauth_token_secret'];
        $token = new static([
            'consumer_key' => $options['consumer_key'],
            'consumer_secret' => $options['consumer_secret'],
            'token_value' => $tokenValue,
            'token_secret' => $tokenSecret,
        ]);
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
    public function getTokenVersion()
    {
        return $this->tokenVersion;
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
     * @return string
     */
    public function getConsumerKey()
    {
        return $this->consumerKey;
    }

    /**
     * @return string
     */
    public function getConsumerSecret()
    {
        return $this->consumerSecret;
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
        $fieldsMapping = [
            'consumer_key' => 'consumerKey',
            'consumer_secret' => 'consumerSecret',
            'token_value' => 'tokenValue',
            'token_secret' => 'tokenSecret',
        ];

        if (array_key_exists($name, $fieldsMapping)) {
            $field = $fieldsMapping[$name];
            $this->$field = $value;
        }

        return $this;
    }


    public function __construct(array $tokenParams)
    {
        $tokenParams = array_merge([
            'consumer_key' => '',
            'consumer_secret' => '',
            'token_value' => '',
            'token_secret' => '',
        ], $tokenParams);

        if (!$tokenParams['consumer_key'] || !$tokenParams['consumer_secret'] ||
            !$tokenParams['token_value'] || !$tokenParams['token_secret']) {
            throw new InvalidArgumentException("No token value input");
        }

        foreach ($tokenParams as $key => $value) {
            $this->$key = $value;
        }
    }
}
