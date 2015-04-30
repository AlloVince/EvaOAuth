<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth;

use Eva\EvaOAuth\Events\EventsManager;
use Eva\EvaOAuth\OAuth1\Signature\Hmac;
use Eva\EvaOAuth\Token\AccessTokenInterface;
use Eva\EvaOAuth\Utils\Text;
use Eva\EvaOAuth\OAuth2\Token\AccessTokenInterface as OAuth2AccessTokenInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Url;

/**
 * Class AuthorizedHttpClient
 * @package Eva\EvaOAuth
 */
class AuthorizedHttpClient extends Client
{
    /**
     * @param AccessTokenInterface $token
     * @param array $options
     */
    public function __construct(AccessTokenInterface $token, array $options = [])
    {
        $options = array_merge($options, [
            'emitter' => EventsManager::getEmitter()
        ]);
        parent::__construct($options);

        if ($token instanceof OAuth2AccessTokenInterface) {
            $this->getEmitter()->on(
                'before',
                function (BeforeEvent $event) use ($token) {
                    /** @var \Eva\EvaOAuth\OAuth2\Token\AccessToken $token */
                    $event->getRequest()->setHeader(
                        'Authorization',
                        $token->getTokenType() . ' ' . $token->getTokenValue()
                    );
                }
            );
        } else {
            $this->getEmitter()->on(
                'before',
                function (BeforeEvent $event) use ($token) {
                    /** @var Request $request */
                    $request = $event->getRequest();
                    /** @var \Eva\EvaOAuth\OAuth1\Token\AccessToken $token */

                    $httpMethod = strtoupper($request->getMethod());
                    $url = Url::fromString($request->getUrl());
                    $parameters = [
                        'oauth_consumer_key' => $token->getConsumerKey(),
                        'oauth_signature_method' => 'HMAC-SHA1',
                        'oauth_timestamp' => (string) time(),
                        'oauth_nonce' => strtolower(Text::generateRandomString(32)),
                        'oauth_token' => $token->getTokenValue(),
                        'oauth_version' => '1.0',
                    ];

                    $signature = (string) new Hmac(
                        $token->getConsumerSecret(),
                        Text::buildBaseString($httpMethod, $url, $parameters),
                        $token->getTokenSecret()
                    );
                    $parameters['oauth_signature'] = $signature;
                    $event->getRequest()->setHeader(
                        'Authorization',
                        Text::buildHeaderString($parameters)
                    );
                }
            );
        }
    }
}
