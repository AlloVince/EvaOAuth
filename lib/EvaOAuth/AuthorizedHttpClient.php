<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth;

use Eva\EvaOAuth\Token\AccessTokenInterface;
use GuzzleHttp\Client;

class AuthorizedHttpClient
{

    /**
     * @var Client
     */
    protected $httpClient;

    public function __call($method, $args)
    {
        return call_user_func($this->httpClient, $args);
    }

    public static function __callStatic($method, $args)
    {
        return call_user_func('GuzzleHttp\Client::' . $method, $args);
    }

    public function __construct(AccessTokenInterface $token)
    {
        $this->httpClient = new Client();
    }
}
