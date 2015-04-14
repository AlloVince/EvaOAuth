<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\OAuth1\Providers;

use Eva\EvaOAuth\AuthorizedHttpClient;
use Eva\EvaOAuth\OAuth1\Token\AccessToken;
use Eva\EvaOAuth\Token\AccessTokenInterface;
use Eva\EvaOAuth\User\StandardUser;
use GuzzleHttp\Subscriber\Log\Formatter;
use GuzzleHttp\Subscriber\Log\LogSubscriber;

class Twitter extends AbstractProvider
{
    protected $requestTokenUrl = 'https://api.twitter.com/oauth/request_token';

    protected $authorizeUrl = 'https://api.twitter.com/oauth/authorize';

    protected $accessTokenUrl = 'https://api.twitter.com/oauth/access_token';

    /**
     * @param AccessToken $token
     * @return StandardUser
     */
    public function getUser(AccessToken $token)
    {
        $httpClient = new AuthorizedHttpClient($token);
        $httpClient->getEmitter()->attach(new LogSubscriber(null, Formatter::DEBUG));
        /** @var Response $response */
        $response = $httpClient->get('https://api.twitter.com/1.1/account/verify_credentials.json');
        $rawUser = $response->json();

        $user = new StandardUser([
            'version' => AccessTokenInterface::VERSION_OAUTH1,
            'provider' => 'Twitter',
            /*
            'id' => $rawUser['id'],
            'name' => $rawUser['uid'],
            'avatar' => $rawUser['avatar'],
            'extra' => $rawUser,
            */
        ]);
        return $user;
    }
}
