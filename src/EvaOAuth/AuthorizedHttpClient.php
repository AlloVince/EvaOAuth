<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth;

use Eva\EvaOAuth\Token\AccessTokenInterface;
use GuzzleHttp\Client;
use Eva\EvaOAuth\OAuth2\Token\AccessTokenInterface as OAuth2AccessTokenInterface;
use GuzzleHttp\Event\BeforeEvent;

/**
 * Class AuthorizedHttpClient
 * @package Eva\EvaOAuth
 */
class AuthorizedHttpClient
{

    /**
     * @var Client
     */
    protected $httpClient;

    /**
     * @return Client
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * @param $method
     * @param $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        return call_user_func_array(array($this->httpClient, $method), $args);
    }

    /**
     * @param $method
     * @param $args
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        return call_user_func('GuzzleHttp\Client::' . $method, $args);
    }

    /**
     * @param AccessTokenInterface $token
     * @param array $options
     */
    public function __construct(AccessTokenInterface $token, array $options = [])
    {
        $this->httpClient = $httpClient = new Client($options);

        if ($token instanceof OAuth2AccessTokenInterface) {
            $httpClient->getEmitter()->on(
                'before',
                function (BeforeEvent $event) use ($token) {
                    /** @var OAuth2AccessTokenInterface $token */
                    $event->getRequest()->setHeader(
                        'Authorization',
                        $token->getTokenType() . ' ' . $token->getTokenValue()
                    );
                }
            );
        } else {
            //TODO: OAuth1 token handle
        }
    }
}
