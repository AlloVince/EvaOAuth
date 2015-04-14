<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\OAuth1\Token;

use Eva\EvaOAuth\Exception\InvalidArgumentException;
use Eva\EvaOAuth\Utils\ResponseParser;
use GuzzleHttp\Message\Response;
use Eva\EvaOAuth\OAuth1\ServiceProviderInterface;

/**
 * Class RequestToken
 * @package Eva\EvaOAuth\OAuth1\Token
 */
class RequestToken implements RequestTokenInterface
{
    /**
     * @var string
     */
    protected $tokenValue;

    /**
     * @var string
     */
    protected $tokenSecret;

    /**
     * @var Response
     */
    protected $response;

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
     * @param Response $response
     * @param ServiceProviderInterface $serviceProvider
     * @return RequestTokenInterface
     */
    public static function factory(Response $response, ServiceProviderInterface $serviceProvider)
    {
        $rawToken = ResponseParser::parse($response, $serviceProvider->getRequestTokenFormat());
        $tokenValue = empty($rawToken['oauth_token']) ? '' : $rawToken['oauth_token'];
        $tokenSecret = empty($rawToken['oauth_token_secret']) ? '' : $rawToken['oauth_token_secret'];
        //$callbackConfirmed = empty($rawToken['oauth_callback_confirmed']) ? false : true;
        //TODO callback confirm handle

        $token = new static($tokenValue, $tokenSecret);
        $token->setResponse($response);
        return $token;
    }


    /**
     * @return string
     */
    public function getTokenValue()
    {
        return $this->tokenValue;
    }

    /**
     * @return array
     */
    public function getTokenSecret()
    {
        return $this->tokenSecret;
    }

    /**
     * @param $tokenValue
     * @param $tokenSecret
     */
    public function __construct($tokenValue, $tokenSecret)
    {
        if (!$tokenValue || !$tokenSecret) {
            throw new InvalidArgumentException("No token value or secret input");
        }

        $this->tokenValue = (string)$tokenValue;
        $this->tokenSecret = (string)$tokenSecret;
    }
}
