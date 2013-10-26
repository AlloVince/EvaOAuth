<?php
    
namespace EvaOAuth\Adapter\OAuth2;

use EvaOAuth\Adapter\OAuth2\AbstractAdapter;
use EvaOAuth\Service\Token\Access as AccessToken;

class Netease extends AbstractAdapter
{
    protected $authorizeUrl = "https://api.t.163.com/oauth2/authorize";
    protected $accessTokenUrl = "https://api.t.163.com/oauth2/access_token";


    public function accessTokenToArray(AccessToken $accessToken)
    {
        $token = parent::accessTokenToArray($accessToken);
        $token['remoteUserId'] = $accessToken->getParam('uid');
        return $token;
    }
}
