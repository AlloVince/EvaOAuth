<?php
    
namespace EvaOAuth\Adapter\OAuth1;

use EvaOAuth\Adapter\OAuth1\AbstractAdapter;
use ZendOAuth\Token\Access as AccessToken;


class Twitter extends AbstractAdapter
{
    protected $websiteName = 'Twitter';

    protected $requestTokenUrl = "https://api.twitter.com/oauth/request_token";

    protected $authorizeUrl = "https://api.twitter.com/oauth/authorize";

    protected $accessTokenUrl = "https://api.twitter.com/oauth/access_token";

    public function accessTokenToArray(AccessToken $accessToken)
    {
        $token = parent::accessTokenToArray($accessToken);
        $token['remoteUserId'] = $accessToken->getParam('user_id');
        $token['remoteUserName'] = $accessToken->getParam('screen_name');
        return $token;
    }
}
