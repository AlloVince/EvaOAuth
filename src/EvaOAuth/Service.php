<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaOAuth;

use Eva\EvaOAuth\Exception\BadMethodCallException;
use Eva\EvaOAuth\Exception\InvalidArgumentException;
use Eva\EvaOAuth\OAuth1\Consumer;
use Eva\EvaOAuth\OAuth2\Client;

class Service
{
    const OAUTH_VERSION_1 = 'OAuth1';

    const OAUTH_VERSION_2 = 'OAuth2';

    /**
     * @var Consumer
     */
    protected $consumer;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var
     */
    protected $provider;

    /**
     * @var array
     */
    protected static $providers = [
        'twitter' => 'Eva\EvaOAuth\OAuth1\Providers\Twitter',
        'douban' => 'Eva\EvaOAuth\OAuth2\Providers\Douban',
        'tencent' => 'Eva\EvaOAuth\OAuth2\Providers\Tencent',
        'hundsun' => 'Eva\EvaOAuth\OAuth2\Providers\Hundsun',
    ];

    /**
     * @param string $name
     * @param string $class
     */
    public static function registerProvider($name, $class)
    {
        self::$providers[$name] = $class;
    }

    /**
     * @param array $classes
     */
    public static function registerProviders(array $classes)
    {
        foreach ($classes as $name => $class) {
            self::$providers[$name] = $class;
        }
    }


    public function requestAuthorize()
    {
        if ($this->version === self::OAUTH_VERSION_2) {
            return $this->client->requestAuthorize($this->provider);
        }
        return $this->consumer->requestAuthorize($this->provider);
    }

    public function getAccessToken()
    {
        if ($this->version === self::OAUTH_VERSION_2) {
            return $this->client->getAccessToken($this->provider);
        }
        return $this->consumer->getAccessToken($this->provider);
    }

    public function convertOptions(array $options, $version)
    {
        if ($version === self::OAUTH_VERSION_2) {
            return array_merge([
                'client_id' => $options['key'],
                'client_secret' => $options['secret'],
                'redirect_uri' => $options['callback'],
            ], $options);
        }
        return array_merge([
            'consumer_key' => $options['key'],
            'consumer_secret' => $options['secret'],
            'callback' => $options['callback'],
        ], $options);
    }

    public function __construct($providerName, array $options)
    {
        $providerName = strtolower($providerName);
        if (false === isset(self::$providers[$providerName])) {
            throw new BadMethodCallException(sprintf('Provider %s not found', $providerName));
        }

        $options = array_merge([
            'key' => '',
            'secret' => '',
            'callback' => '',
        ], $options);

        $providerClass = self::$providers[$providerName];
        $implements = class_implements($providerClass);
        if (true === in_array('Eva\EvaOAuth\OAuth2\ResourceServerInterface', $implements)) {
            $options = $this->convertOptions($options, self::OAUTH_VERSION_2);
            $this->client = new Client($options);
            $this->version = self::OAUTH_VERSION_2;
            $this->provider = new $providerClass();
        } elseif (true === in_array('Eva\EvaOAuth\OAuth1\ServiceProviderInterface', $implements)) {
            $options = $this->convertOptions($options, self::OAUTH_VERSION_1);
            $this->consumer = new Consumer($options);
            $this->version = self::OAUTH_VERSION_1;
            $this->provider = new $providerClass();
        } else {
            throw new InvalidArgumentException(sprintf("Class %s is not correct oauth adapter", $providerClass));
        }
    }
}
