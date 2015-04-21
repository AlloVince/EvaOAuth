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
use GuzzleHttp\Subscriber\Log\Formatter;
use GuzzleHttp\Subscriber\Log\LogSubscriber;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Eva\EvaOAuth\OAuth1\Providers\AbstractProvider as OAuth1Provider;
use Eva\EvaOAuth\OAuth2\Providers\AbstractProvider as OAuth2Provider;

/**
 * Class Service
 * @package Eva\EvaOAuth
 */
class Service
{
    /**
     * OAuth 1 flag
     */
    const OAUTH_VERSION_1 = 'OAuth1';

    /**
     * OAuth 2 flag
     */
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
     * @var OAuth1Provider|OAuth2Provider
     */
    protected $provider;

    /**
     * @var array
     */
    protected static $providers = [
        'twitter' => 'Eva\EvaOAuth\OAuth1\Providers\Twitter',
        'facebook' => 'Eva\EvaOAuth\OAuth2\Providers\Facebook',
        'douban' => 'Eva\EvaOAuth\OAuth2\Providers\Douban',
        'tencent' => 'Eva\EvaOAuth\OAuth2\Providers\Tencent',
        'weibo' => 'Eva\EvaOAuth\OAuth2\Providers\Weibo',
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

    /**
     * @return array
     */
    public static function getProviders()
    {
        return self::$providers;
    }

    /**
     * @return OAuth1Provider|OAuth2Provider
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @return Consumer|Client
     */
    public function getAdapter()
    {
        if ($this->version === self::OAUTH_VERSION_2) {
            return $this->client;
        }
        return $this->consumer;
    }

    /**
     * @return \GuzzleHttp\Client
     */
    public function getHttpClient()
    {
        $adapter = $this->getAdapter();
        return $adapter::getHttpClient();
    }

    /**
     * @return string
     */
    public function getAuthorizeUri()
    {
        $adapter = $this->getAdapter();
        if ($this->version === self::OAUTH_VERSION_2) {
            return $adapter->getAuthorizeUri($this->provider);
        }
        $requestToken = $adapter->getRequestToken($this->provider);
        return $adapter->getAuthorizeUri($this->provider, $requestToken);
    }

    /**
     * Redirect to authorize url
     */
    public function requestAuthorize()
    {
        $this->getAdapter()->requestAuthorize($this->provider);
    }

    /**
     * @return Token\AccessTokenInterface
     */
    public function getAccessToken()
    {
        return $this->getAdapter()->getAccessToken($this->provider);
    }

    /**
     * To compatible with old version
     * @return array
     */
    public function getTokenAndUser()
    {
        $adapter = $this->getAdapter();

        $isOAuth2 = $this->version === self::OAUTH_VERSION_2;
        $token = $adapter->getAccessToken($this->provider);
        $user = $this->provider->getUser($token);

        return [
            'adapterKey' => $this->provider->getProviderName(),
            'token' => $token->getTokenValue(),
            'version' => $token->getTokenVersion(),
            'scope' => $isOAuth2 ? $token->getScope() : null,
            'refreshToken' => $isOAuth2 ? $token->getRefreshToken() : null,
            'expireTime' => $isOAuth2 ? gmdate('Y-m-d H:i:s', $token->getExpireTimestamp()) : null,
            'remoteUserId' => $user->getId(),
            'remoteUserName' => $user->getName(),
            'remoteEmail' => $user->getEmail(),
            'remoteImageUrl' => $user->getAvatar(),
            'remoteExtra' => json_encode($user->getExtra()),
        ];
    }

    /**
     * @param array $options
     * @param $version
     * @return array
     */
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

    /**
     * @param string $logPath
     * @return $this
     */
    public function setLogPath($logPath)
    {
        $log = new Logger('EvaOAuth');
        $log->pushHandler(new StreamHandler($logPath, Logger::DEBUG));
        $adapter = $this->getAdapter();
        $adapter::getHttpClient()->getEmitter()->attach(new LogSubscriber($log, Formatter::DEBUG));
        return $this;
    }

    /**
     * Enable debug mode
     * Guzzle will print all request and response on screen
     * @return $this
     */
    public function debug()
    {
        $adapter = $this->getAdapter();
        $adapter::getHttpClient()->getEmitter()->attach(new LogSubscriber(null, Formatter::DEBUG));
        return $this;
    }

    /**
     * @param string $providerName
     * @param array $options
     */
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
