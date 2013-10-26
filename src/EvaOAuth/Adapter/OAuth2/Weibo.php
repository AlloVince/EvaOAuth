<?php
    
namespace EvaOAuth\Adapter\OAuth2;

use EvaOAuth\Adapter\OAuth2\AbstractAdapter;
use EvaOAuth\Service\Token\Access as AccessToken;


class Weibo extends AbstractAdapter
{
    protected $websiteName = 'Weibo';
    protected $websiteProfileUrl = 'http://weibo.com/%s';

    protected $authorizeUrl = "https://api.weibo.com/oauth2/authorize";
    protected $accessTokenUrl = "https://api.weibo.com/oauth2/access_token";

    public function accessTokenToArray(AccessToken $accessToken)
    {
        $token = parent::accessTokenToArray($accessToken);
        $token['remoteUserId'] = $accessToken->getParam('uid');
        return $token;
    }
}
