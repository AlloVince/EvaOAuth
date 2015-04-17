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
use GuzzleHttp\Message\Response;

/**
 * Class Twitter
 * @package Eva\EvaOAuth\OAuth1\Providers
 */
class Twitter extends AbstractProvider
{
    /**
     * @var string
     */
    protected $requestTokenUrl = 'https://api.twitter.com/oauth/request_token';

    /**
     * @var string
     */
    protected $authorizeUrl = 'https://api.twitter.com/oauth/authorize';

    /**
     * @var string
     */
    protected $accessTokenUrl = 'https://api.twitter.com/oauth/access_token';

    /**
     * @param AccessToken $token
     * @return StandardUser
     */
    public function getUser(AccessToken $token)
    {
        /** @var \GuzzleHttp\Client $httpClient */
        $httpClient = new AuthorizedHttpClient($token);
        /** @var Response $response */
        $response = $httpClient->get('https://api.twitter.com/1.1/account/verify_credentials.json');
        $rawUser = $response->json();

        $user = new StandardUser([
            'version' => AccessTokenInterface::VERSION_OAUTH1,
            'provider' => 'Twitter',
            'id' => $rawUser['id'],
            'name' => $rawUser['name'],
            'avatar' => $rawUser['profile_image_url'],
            'extra' => $rawUser,
        ]);
        return $user;
    }
}
