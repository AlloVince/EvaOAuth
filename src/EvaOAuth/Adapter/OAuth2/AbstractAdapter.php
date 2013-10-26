<?php
    
namespace EvaOAuth\Adapter\OAuth2;

use EvaOAuth\Adapter\OAuth2\AdapterInterface;
use EvaOAuth\Exception;
use ZendOAuth\OAuth as ZendOAuth;
use EvaOAuth\Service\Consumer;
use EvaOAuth\Service\Token\Access as AccessToken;


abstract class AbstractAdapter extends \EvaOAuth\Adapter\AbstractAdapter implements AdapterInterface
{
    const ACCESS_TOKEN_FORMAT_JSON = 'json';
    const ACCESS_TOKEN_FORMAT_JSONP = 'jsonp';
    const ACCESS_TOKEN_FORMAT_XML = 'xml';
    const ACCESS_TOKEN_FORMAT_PAIR = 'pair';

    protected $accessTokenFormat = 'json';

    protected $accessTokenRequestMethod = ZendOAuth::POST;

    public function getAccessTokenFormat()
    {
        return $this->accessTokenFormat;
    }

    public function setAccessTokenFormat($accessToken)
    {
        if($accessToken != self::ACCESS_TOKEN_FORMAT_JSON
            && $accessToken != self::ACCESS_TOKEN_FORMAT_JSONP
            && $accessToken != self::ACCESS_TOKEN_FORMAT_XML
            && $accessToken != self::ACCESS_TOKEN_FORMAT_PAIR
        ){
            throw new Exception\InvalidArgumentException(sprintf(
                'Undefined access token format %s input, accept format are json|jsonp|xml',
                $accessToken
            ));
        }

        $this->accessTokenFormat = $accessToken;
        return $this;
    }

    public function setOptions(array $options = array())
    {
		$defaultOptions = array(
            'requestScheme' => ZendOAuth::REQUEST_SCHEME_HEADER,
            'version' => '2.0', 
            'callbackUrl' =>  $this->getCallback(),
            'consumerKey' => $this->getConsumerKey(),
            'consumerSecret' => $this->getConsumerSecret(),
            'authorizeUrl' => $this->authorizeUrl,
            'accessTokenUrl' => $this->accessTokenUrl,
            'accessTokenFormat' => $this->accessTokenFormat,
		);

        $options = array_merge($defaultOptions, $this->defaultOptions, $options);

        if(!$options['consumerKey']){
            throw new Exception\InvalidArgumentException(sprintf('No consumer key found in %s', get_class($this)));
        }

        if(!$options['consumerSecret']){
            throw new Exception\InvalidArgumentException(sprintf('No consumer secret found in %s', get_class($this)));
        }

        if(!$options['callbackUrl']){
            throw new Exception\InvalidArgumentException(sprintf('No callback url found in %s', get_class($this)));
        }

        $this->setConsumerKey($options['consumerKey']);
        $this->setConsumerSecret($options['consumerSecret']);
        $this->setCallback($options['callbackUrl']);
        $this->setAccessTokenFormat($options['accessTokenFormat']);

        $this->options = $options;
        return $this;
    }

    public function getConsumer()
    {
        if($this->consumer){
            return $this->consumer;
        }

        $consumer = new Consumer($this->getOptions());
        //to void the error :  make sure the "sslcapath" option points to a valid SSL certificate directory
        $consumer->getHttpClient()->setOptions(array(
            'sslverifypeer' => false
        ));
        return $this->consumer = $consumer;
    }

    public function refreshAccessToken()
    {
    }

    public function getHttpClient(array $oauthOptions = array(), $uri = null, $config = null, $excludeCustomParamsFromHeader = true)
    {
        $defaultHttpClientOptions = array(
            'requestScheme' => ZendOAuth::REQUEST_SCHEME_QUERYSTRING,
        );
        $oauthOptions = array_merge($defaultHttpClientOptions, $this->httpClientOptions, $oauthOptions);
        $client = $this->getAccessToken()->getHttpClient($oauthOptions, $uri, $config, $excludeCustomParamsFromHeader);
        if(isset($oauthOptions['callback']) && $oauthOptions['callback']){
            $func = $oauthOptions['callback'];
            $client = $this->$func($client);
        }
        return $client;
    }

    public function accessTokenToArray(AccessToken $accessToken)
    {
        return array(
            'adapterKey' => $this->getAdapterKey(),
            'token' => $accessToken->getToken(),
            'expireTime' => $accessToken->getExpiredTime(),
            'refreshToken' => $accessToken->getRefreshToken(),
            'version' => 'OAuth2',
        );
    }

    public function arrayToAccessToken(array $accessTokenArray)
    {
        $accessToken = new AccessToken();
        $accessToken->setToken($accessTokenArray['token']);
        $accessToken->setTokenSecret($accessTokenArray['tokenSecret']);
        foreach($accessTokenArray as $key => $value) {
            $accessToken->setParam($key, $value);
        }
        return $accessToken;
    }
}
