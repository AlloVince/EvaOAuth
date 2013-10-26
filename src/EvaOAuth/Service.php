<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Authentication
 */

namespace EvaOAuth;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * @category   Zend
 * @package    Oauth
 */
class Service
{
    const VERSION_OAuth1 = 'OAuth1';
    const VERSION_OAUTH2 = 'OAuth2';

    /**
     * Persistent storage handler
     *
     * @var Storage\StorageInterface
     */
    protected $storage = null;

    /**
     * Authentication adapter
     *
     * @var Adapter\AdapterInterface
     */
    protected $adapter = null;

    protected $oauthVersion = 'OAuth2';

    protected $options;


    /**
     * Constructor
     *
     */
     public static function factory(array $options, ServiceLocatorInterface $serviceLocator)
     {
         $defaultOptions = array(
             'adapter' => '',
             'storage' => 'Session',
             'version' => self::VERSION_OAUTH2,
             'callback' => '',
         );
         $options = array_merge($defaultOptions, $options);

         $callback = $options['callback'];
         $version = $options['version'];
         $storage = $options['storage'];
         $adapter = $options['adapter'];

         if(!$callback){
             throw new Exception\InvalidArgumentException(sprintf(
                 'No oauth callback url found'
             ));
         }

         $oauth = new static();
         $oauth->setOauthVersion($version);
         $oauth->setServiceLocator($serviceLocator);

         $adapter = strtolower($options['adapter']);
         $version = strtolower($version);


         $config = $serviceLocator->get('Config');
         $options = array(
             'enable' => true,
             'consumer_key' => '',
             'consumer_secret' => '',
         );
         if(isset($config['oauth'][$version][$adapter])){
             $options = array_merge($options, $config['oauth'][$version][$adapter]);
         }

         if(!$options['enable']){
             throw new Exception\RuntimeException(sprintf(
                 'Oauth service %s not enabled by config', get_class($oauth)
             ));
         }

         $options['consumerKey'] = $options['consumer_key'];
         $options['consumerSecret'] = $options['consumer_secret'];
         $options['callbackUrl'] = $callback;
         unset($options['consumer_key']);
         unset($options['consumer_secret']);
         $oauth->setOptions($options);

         $adapter = $oauth->initAdapter($adapter, $version);
         $storage = $oauth->initStorage($storage);

         return $oauth;
     }

     public function initByAccessToken(array $accessTokenArray = array(), array $options = array())
     {
         if(!$accessTokenArray) {
            $accessTokenArray = $this->getStorage()->getAccessToken();
         }

         if(!$accessTokenArray){
             throw new Exception\InvalidArgumentException(sprintf(
                 'No access token input when init token service'
             )); 
         }

         $defaultAccessToken = array(
             'adapterKey' => '',
             'version' => '',
             'token' => '',
             'tokenSecret' => '',
             'remoteUserId' => '',
             'remoteUserName' => '',
             'user_id' => '',
         );
         $accessTokenArray = array_merge($defaultAccessToken, $accessTokenArray);
         $version = strtolower($accessTokenArray['version']);
         $adapter = $accessTokenArray['adapterKey'];

         if($serviceLocator = $this->getServiceLocator()){
             $config = $serviceLocator->get('Config');
             $defaultOptions = array(
                 'enable' => true,
                 'consumer_key' => '',
                 'consumer_secret' => '',
             );
             if(isset($config['oauth'][$version][$adapter])){
                 $options = array_merge($defaultOptions, $config['oauth'][$version][$adapter], $options);
             }
             if(!$options['enable']){
                 throw new Exception\RuntimeException(sprintf(
                     'Oauth service %s not enabled by config', get_class($this)
                 ));
             }
             $options['consumerKey'] = $options['consumer_key'];
             $options['consumerSecret'] = $options['consumer_secret'];
             if(!isset($options['callbackUrl']) || !$options['callbackUrl']) {
                 $options['callbackUrl'] = 'http://www.example.com/';
             }
             unset($options['consumer_key']);
             unset($options['consumer_secret']);
             $this->setOptions($options);
         }

         $this->setOauthVersion($version);
         $adapter = $this->initAdapter($adapter, $version);
         $accessToken = $adapter->arrayToAccessToken($accessTokenArray);
         $adapter->setAccessToken($accessToken);
         return $this;
     }

     /**
     * @var ServiceLocatorInterface
     */
     protected $serviceLocator;

     /**
     * Set the service locator.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return AbstractHelper
     */
     public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
     {
         $this->serviceLocator = $serviceLocator;
         return $this;
     }

     /**
     * Get the service locator.
     *
     * @return \Zend\ServiceManager\ServiceLocatorInterface
     */
     public function getServiceLocator()
     {
         return $this->serviceLocator;
     }

     public function getOptions()
     {
         return $this->options;
     }

     public function setOptions($options)
     {
         $this->options = $options;
         return $this;
     }

     public function getOauthVersion()
     {
         return $this->oauthVersion;
     }

     public function setOauthVersion($version)
     {
         if(!$version == self::VERSION_OAUTH2 && !$version == self::VERSION_OAuth1) {
             throw new Exception\InvalidArgumentException(sprintf(
                 'Undefined oauth version. Oauth version only allow : %s or %s'
                 , self::VERSION_OAUTH2, self::VERSION_OAuth1
             )); 
         }
         $this->oauthVersion = $version;
         return $this;
     }

     public function initAdapter($adapterName, $oauthVersion)
     {
         $options = $this->getOptions();

         $adapterClass = 'EvaOAuth\Adapter\\' . ucfirst(strtolower($oauthVersion)) . '\\' . ucfirst(strtolower($adapterName));

         if(false === class_exists($adapterClass)){
             throw new Exception\InvalidArgumentException(sprintf('Undefined oauth adapter %s by oauth version %s', $adapterName, $oauthVersion));
         }

         return $this->adapter = new $adapterClass($options);
     }

     public function initStorage($storageName)
     {
         $storageClass = 'Oauth\Storage\\' . ucfirst(strtolower($storageName));
         if(false === class_exists($storageClass)){
             throw new Exception\InvalidArgumentException(sprintf('Undefined oauth storage %s', $storageName));
         }
         return $this->storage = new $storageClass();
     }

     /**
     * Returns the authentication adapter
     *
     * The adapter does not have a default if the storage adapter has not been set.
     *
     * @return Adapter\AdapterInterface|null
     */
     public function getAdapter()
     {
         return $this->adapter;
     }

     /**
     * Sets the authentication adapter
     *
     * @param  Adapter\AdapterInterface $adapter
     * @return AuthenticationService Provides a fluent interface
     */
     public function setAdapter(Adapter\AdapterInterface $adapter)
     {
         $this->adapter = $adapter;
         return $this;
     }

     /**
     * Returns the persistent storage handler
     *
     * Session storage is used by default unless a different storage adapter has been set.
     *
     * @return Storage\StorageInterface
     */
     public function getStorage()
     {
         if (null === $this->storage) {
             $this->setStorage(new Storage\Session());
         }

         return $this->storage;
     }

     /**
     * Sets the persistent storage handler
     *
     * @param  Storage\StorageInterface $storage
     * @return AuthenticationService Provides a fluent interface
     */
     public function setStorage(Storage\StorageInterface $storage)
     {
         $this->storage = $storage;
         return $this;
     }

 }
