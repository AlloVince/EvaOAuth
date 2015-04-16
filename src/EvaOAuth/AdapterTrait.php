<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaOAuth;

use Eva\EvaOAuth\Storage\StorageInterface;
use GuzzleHttp\Client;

trait AdapterTrait
{
    /**
     * @var \GuzzleHttp\Client
     */
    protected static $httpClient;

    /**
     * @var array
     */
    protected static $httpClientDefaultOptions = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public static function setHttpClientDefaultOptions(array $options)
    {
        self::$httpClientDefaultOptions = $options;
    }


    /**
     * @return \GuzzleHttp\Client
     */
    public static function getHttpClient()
    {
        if (self::$httpClient) {
            return self::$httpClient;
        }

        return self::$httpClient = new Client(self::$httpClientDefaultOptions);
    }

    /**
     * @param Client $httpClient
     */
    public static function setHttpClient(Client $httpClient)
    {
        self::$httpClient = $httpClient;
    }

    public static function getStorage()
    {

    }

    public static function setStorage(StorageInterface $storage)
    {

    }
}
