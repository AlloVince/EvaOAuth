<?php
    
namespace EvaOAuth\Adapter\OAuth2;

use EvaOAuth\Adapter\OAuth2\AbstractAdapter;
use EvaOAuth\Service\Token\Access as AccessToken;

class Disqus extends AbstractAdapter
{
    protected $authorizeUrl = "https://disqus.com/api/oauth/2.0/authorize/";
    protected $accessTokenUrl = "https://disqus.com/api/oauth/2.0/access_token/";

    public function accessTokenToArray(AccessToken $accessToken)
    {
        $token = parent::accessTokenToArray($accessToken);
        $token['remoteUserId'] = $token['user_id'];
        $token['remoteUserName'] = $user['username'];
        return $token;
    }
}
