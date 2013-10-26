<?php
    
namespace EvaOAuth\Adapter\OAuth1;

use EvaOAuth\Adapter\OAuth1\AbstractAdapter;
use ZendOAuth\Token\Access as AccessToken;


class Sohu extends AbstractAdapter
{
    protected $websiteName = 'Sohu';

    protected $websiteProfileUrl = '';

    protected $requestTokenUrl = "http://api.t.sohu.com/oauth/request_token";

    protected $authorizeUrl = "http://api.t.sohu.com/oauth/authorize";

    protected $accessTokenUrl = "http://api.t.sohu.com/oauth/access_token";

    public function accessTokenToArray(AccessToken $accessToken)
    {
        $token = parent::accessTokenToArray($accessToken);
        return $token;
    }
}
