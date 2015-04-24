<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\Events;

use GuzzleHttp\Event\AbstractEvent;
use Eva\EvaOAuth\OAuth2\Client;
use Eva\EvaOAuth\OAuth1\Consumer;
use GuzzleHttp\Client as HttpClient;
use Eva\EvaOAuth\OAuth2\ResourceServerInterface;
use Eva\EvaOAuth\OAuth1\ServiceProviderInterface;
use GuzzleHttp\Message\Request;

class BeforeGetAccessToken extends AbstractEvent
{
    /**
     * @var Client|Consumer
     */
    protected $adapter;

    /**
     * @var ResourceServerInterface|ServiceProviderInterface
     */
    protected $provider;

    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return Consumer|Client
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * @return ResourceServerInterface|ServiceProviderInterface
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param Request $request
     * @param $provider
     * @param $adapter
     */
    public function __construct(Request $request, $provider, $adapter = null)
    {
        $this->request = $request;
        $this->adapter = $adapter;
        $this->provider = $provider;
    }
}
