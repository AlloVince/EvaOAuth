<?php
    
namespace EvaOAuth\Adapter\OAuth1;

use EvaOAuth\Adapter\OAuth1\AbstractAdapter;
use ZendOAuth\Token\Access as AccessToken;


class Yahoo extends AbstractAdapter
{
    protected $websiteName = 'Yahoo';

    protected $websiteProfileUrl = '';

    protected $requestTokenUrl = "https://api.login.yahoo.com/oauth/v2/get_request_token";

    protected $authorizeUrl = "https://api.login.yahoo.com/oauth/v2/request_auth";

    protected $accessTokenUrl = "https://api.login.yahoo.com/oauth/v2/get_token";

    public function accessTokenToArray(AccessToken $accessToken)
    {
        $token = parent::accessTokenToArray($accessToken);
        $expiredTime = $accessToken->getParam('oauth_authorization_expires_in');
        $token['expireTime'] = gmdate('Y-m-d H:i:s', time() + $expiredTime);
        $token['remoteUserId'] = $accessToken->getParam('xoauth_yahoo_guid');
        return $token;
    }
}
