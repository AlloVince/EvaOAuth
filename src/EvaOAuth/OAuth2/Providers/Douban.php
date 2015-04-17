<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaOAuth\OAuth2\Providers;

use Eva\EvaOAuth\AuthorizedHttpClient;
use Eva\EvaOAuth\OAuth2\Token\AccessToken;
use Eva\EvaOAuth\Token\AccessTokenInterface;
use Eva\EvaOAuth\User\StandardUser;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Client;

/**
 * Class Douban
 * @package Eva\EvaOAuth\OAuth2\Providers
 */
class Douban extends AbstractProvider
{
    /**
     * @var string
     */
    protected $authorizeUrl = 'https://www.douban.com/service/auth2/auth';

    /**
     * @var string
     */
    protected $accessTokenUrl = 'https://www.douban.com/service/auth2/token';

    /**
     * @param AccessToken $token
     * @return StandardUser
     */
    public function getUser(AccessToken $token)
    {
        /** @var Client $httpClient */
        $httpClient = new AuthorizedHttpClient($token);
        /** @var Response $response */
        $response = $httpClient->get('https://api.douban.com/v2/user/~me');
        $rawUser = $response->json();

        $user = new StandardUser([
            'version' => AccessTokenInterface::VERSION_OAUTH2,
            'provider' => 'Douban',
            'id' => $rawUser['id'],
            'name' => $rawUser['uid'],
            'avatar' => $rawUser['avatar'],
            'extra' => $rawUser,
        ]);
        return $user;
    }
}
