<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_OAuth
 */

 namespace EvaOAuth\Service\Http;

use EvaOAuth\Service\Token;
use EvaOAuth\Exception;
use Zend\Http;
use ZendOAuth\OAuth;
use ZendOAuth\Http as HTTPClient;

/**
 * @category   Zend
 * @package    Zend_OAuth
 */
class AccessToken extends \ZendOAuth\Http\AccessToken
{
    protected $_preferredRequestScheme = OAuth::REQUEST_SCHEME_QUERYSTRING;

    /**
     * Initiate a HTTP request to retrieve an Access Token.
     *
     * @return \ZendOAuth\Token\Access
     */
    public function execute()
    {
        $defaultParams = $this->getParameters();
        $params = array(
            'grant_type' => 'authorization_code',
			'client_id' => $this->_consumer->getConsumerKey(),
            'client_secret' => $this->_consumer->getConsumerSecret(),
            'redirect_uri' => $this->_consumer->getCallbackUrl(),
		);
        $params = array_merge($defaultParams, $params);
        $response = $this->startRequestCycle($params);
        $return   = new Token\Access($response, null, $this->_consumer->getAccessTokenFormat());
        return $return;
    }

    /**
     * Generate and return a HTTP Client configured for the POST Body Request
     * Scheme specified by OAuth, for use in requesting an Access Token.
     *
     * @param  array $params
     * @return Zend\Http\Client
     */
    public function getRequestSchemePostBodyClient(array $params)
    {
        $params = $this->_cleanParamsOfIllegalCustomParameters($params);
        $client = OAuth::getHttpClient();
        $client->setUri($this->_consumer->getAccessTokenUrl());
        $client->setMethod($this->_preferredRequestMethod);
        $client->setRawBody(
            $this->_httpUtility->toEncodedQueryString($params)
        );
        return $client;
    }

    public function startRequestCycle(array $params)
    {
        $response = null;
        $body     = null;
        $status   = null;
        try {
            $response = $this->_attemptRequest($params);
        } catch (\Zend\Http\Client\Exception\ExceptionInterface $e) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Error in HTTP request: %s',
                $e->getMessage()
            ), null, $e);
        }
        if ($response !== null) {
            $body   = $response->getBody();
            $status = $response->getStatusCode();
        }
        if ($response === null // Request failure/exception
            || $status == 500  // Internal Server Error
            || $status == 400  // Bad Request
            || $status == 401  // Unauthorized
            || empty($body)    // Missing token
        ) {
            $this->_assessRequestAttempt($response);
            $response = $this->startRequestCycle($params);
        }
        return $response;
    }

    protected function _cleanParamsOfIllegalCustomParameters(array $params)
    {
        $allowParams = array(
            'code',
            'client_id',
            'client_secret',
            'grant_type',
            'redirect_uri',
        );
        foreach ($params as $key=>$value) {
            if(false === in_array($key, $allowParams)){
                unset($params[$key]);
            }
        }
        return $params;
    }

    /**
     * Manages the switch from OAuth request scheme to another lower preference
     * scheme during a request cycle.
     *
     * @param  Zend\Http\Response
     * @return void
     * @throws Exception\RuntimeException if unable to retrieve valid token response
     */
    protected function _assessRequestAttempt(\Zend\Http\Response $response = null)
    {
        switch ($this->_preferredRequestScheme) {
            case OAuth::REQUEST_SCHEME_HEADER:
                $this->_preferredRequestScheme = OAuth::REQUEST_SCHEME_POSTBODY;
                break;
            case OAuth::REQUEST_SCHEME_POSTBODY:
                $this->_preferredRequestScheme = OAuth::REQUEST_SCHEME_QUERYSTRING;
                break;
            default:
                throw new Exception\RuntimeException(
                    'Could not retrieve a valid Token response from Token URL:'
                    . ($response !== null
                        ? PHP_EOL . $response->getBody()
                        : ' No body - check for headers')
                );
        }
    }

}
