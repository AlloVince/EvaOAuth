<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\Event;

use GuzzleHttp\Event\AbstractEvent;
use Eva\EvaOAuth\OAuth2\Client;
use Eva\EvaOAuth\OAuth1\Consumer;
use Eva\EvaOAuth\OAuth1\Providers\AbstractProvider as OAuth1Provider;
use Eva\EvaOAuth\OAuth2\Providers\AbstractProvider as OAuth2Provider;

class BeforeGetAccessToken extends AbstractEvent
{
    /**
     * @var Client|Consumer
     */
    protected $adapter;

    /**
     * @var OAuth1Provider|OAuth2Provider
     */
    protected $provider;

    /**
     * @return Consumer|Client
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * @return OAuth1Provider|OAuth2Provider
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param $adapter
     * @param $provider
     */
    public function __construct($adapter, $provider)
    {
        $this->adapter = $adapter;
        $this->provider = $provider;
    }
}
