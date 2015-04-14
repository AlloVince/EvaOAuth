<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth;

use Eva\EvaOAuth\OAuth1\Signature\Hmac;
use Eva\EvaOAuth\Token\AccessTokenInterface;
use Eva\EvaOAuth\Utils\Text;
use GuzzleHttp\Client;
use Eva\EvaOAuth\OAuth2\Token\AccessTokenInterface as OAuth2AccessTokenInterface;
use Eva\EvaOAuth\OAuth1\Token\AccessTokenInterface as OAuth1AccessTokenInterface;
use GuzzleHttp\Event\BeforeEvent;
use Guzzle\Http\Message\Request;
use GuzzleHttp\Url;


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
            $httpClient->getEmitter()->on(
                'before',
                function (BeforeEvent $event) use ($token) {
                    /** @var Request $request */
                    $request = $event->getRequest();
                    /** @var OAuth1AccessTokenInterface $token */

                    $httpMethod = strtoupper($request->getMethod());
                    $url = Url::fromString($request->getUrl());

                    $parameters = [
                        'oauth_consumer_key' => $token->consumer_key,
                        'oauth_signature_method' => 'HMAC-SHA1',
                        'oauth_timestamp' => (string) time(),
                        'oauth_nonce' => strtolower(Text::getRandomString(32)),
                        'oauth_token' => $token->getTokenValue(),
                        'oauth_version' => '1.0',
                    ];

                    $baseString = Text::getBaseString($httpMethod, $url, $parameters);
                    $signature = (string) new Hmac(
                        $token->consumer_secret,
                        $baseString,
                        $token->getTokenSecret()
                    );
                    $parameters['oauth_signature'] = $signature;
                    $event->getRequest()->setHeader(
                        'Authorization',
                        Text::getHeaderString($parameters)
                    );
                }
            );
        }
    }
}
