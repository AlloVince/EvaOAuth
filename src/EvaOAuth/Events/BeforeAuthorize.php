<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\Events;

use Eva\EvaOAuth\OAuth1\Token\RequestToken;
use GuzzleHttp\Event\AbstractEvent;
use Eva\EvaOAuth\OAuth2\Client;
use Eva\EvaOAuth\OAuth1\Consumer;

class BeforeAuthorize extends AbstractEvent
{
    /**
     * @var Client|Consumer
     */
    protected $adapter;

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var RequestToken
     */
    protected $requestToken;

    /**
     * @return Consumer|Client
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getRequestToken()
    {
        return $this->requestToken;
    }

    /**
     * @param string $uri
     * @param Consumer|Client $adapter
     * @param RequestToken $requestToken
     */
    public function __construct($uri, $adapter = null, RequestToken $requestToken = null)
    {
        $this->adapter = $adapter;
        $this->uri = $uri;
        $this->requestToken = $requestToken;
    }
}
