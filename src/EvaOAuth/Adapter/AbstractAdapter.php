<?php
    
namespace EvaOAuth\Adapter;

use EvaOAuth\Adapter\AdapterInterface;
use EvaOAuth\Exception;
use ZendOAuth\OAuth as ZendOAuth;
use EvaOAuth\Service\Consumer;
use ZendOAuth\Token\Access as AccessToken;
use Zend\Http\Response;


abstract class AbstractAdapter implements AdapterInterface
{
    protected $callback;

    protected $consumerKey;

    protected $consumerSecret;

    protected $consumer;

    protected $options;

    protected $websiteName;

    protected $websiteProfileUrl;

    protected $accessToken;
    
    protected $defaultOptions = array();

    protected $httpClientOptions = array();

    public function getWebsiteName()
    {
        return $this->websiteName;
    }

    public function setWebsiteName($websiteName)
    {
        $this->websiteName = $websiteName;
        return $this;
    }

    public function getWebsiteProfileUrl()
    {
        $accessToken = $this->getAccessToken();
        if($remoteUserId = $accessToken->getParam('remoteUserId')) {
            return sprintf($this->websiteProfileUrl, $remoteUserId);
        }
    }

    public function getCallback()
    {
        return $this->callback;
    }

    public function setCallback($callback)
    {
        $this->callback = $callback;
        return $this;
    }

    public function getConsumerKey()
    {
        return $this->consumerKey;
    }

    public function setConsumerKey($consumerKey)
    {
        $this->consumerKey = $consumerKey;
        return $this;
    }

    public function getConsumerSecret()
    {
        return $this->consumerSecret;
    }

    public function setConsumerSecret($consumerSecret)
    {
        $this->consumerSecret = $consumerSecret;
        return $this;
    }

    public function getAdapterKey()
    {
        $className = get_class($this);
        $className = explode('\\', $className);
        return strtolower(array_pop($className));
    }

    public function getOptions()
    {
        return $this->options;
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

    public function getConsumerHttpClient()
    {
        return $this->getConsumer()->getHttpClient(); 
    }

    public function getHttpClient(array $oauthOptions = array(), $uri = null, $config = null, $excludeCustomParamsFromHeader = true)
    {
        $oauthOptions = array_merge($this->httpClientOptions, $oauthOptions);
        return $this->getAccessToken()->getHttpClient($oauthOptions, $uri, $config, $excludeCustomParamsFromHeader);
    }

    public function getRequest()
    {
        return $this->getConsumer()->getHttpClient()->getRequest(); 
    }

    public function getResponse()
    {
        return $this->getConsumer()->getHttpClient()->getResponse(); 
    }

    /**
     * Redirect to oauth service page
     */
    public function getRequestToken()
	{
        return $this->getConsumer()->getRequestToken();
	}

    /**
     * Redirect to oauth service page
     */
    public function getRequestTokenUrl()
    {
        return $this->getConsumer()->getRedirectUrl();
    }

    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    /**
    * Redirect to oauth service page
    */
    public function getAccessToken($queryData = null, $token = null, $httpMethod = null, $request = null)
    {
        if($this->accessToken){
            return $this->accessToken;
        }

        if(!$token) {
            throw new Exception\InvalidArgumentException('No RequestToken input for request AccessToken');
        }

        $accessToken = $this->getConsumer()->getAccessToken($queryData, $token, $httpMethod, $request);
        if(!$accessToken->getToken()) {
            throw new Exception\RuntimeException(sprintf('AccessToken not get correct by server return %s', $accessToken->getResponse()->getBody()));
        }
        return $this->accessToken = $accessToken;
    }


    protected function parseJsonResponse(Response $response)
    {
        $responseText = $response->getBody();
        if(!$responseText){
            return;
        }
        $data = \Zend\Json\Json::decode($responseText, \Zend\Json\Json::TYPE_ARRAY);
        return $data;
    }

    protected function parseJsonpResponse(Response $response)
    {
        $responseText = $response->getBody();
        if(!$responseText){
            return;
        }
        $lpos = strpos($responseText, "(");
        $rpos = strrpos($responseText, ")");
        $responseText = substr($responseText, $lpos + 1, $rpos - $lpos -1);
        $data = \Zend\Json\Json::decode($responseText, \Zend\Json\Json::TYPE_ARRAY);
        return $data;	
    }

    protected function parseXmlResponse(Response $response)
    {
        $responseText = $response->getBody();
        if(!$responseText){
            return;
        }
        $data = \Zend\Json\Json::fromXml($responseText, \Zend\Json\Json::TYPE_ARRAY);
        return $data;
    }

    public function __construct($options = array())
    {
        if($options){
            $this->setOptions($options);
        }
    }
}
