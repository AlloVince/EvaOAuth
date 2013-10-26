<?php
    
namespace EvaOAuth\Adapter\OAuth1;

use EvaOAuth\Adapter\OAuth1\AbstractAdapter;
use ZendOAuth\Token\Access as AccessToken;


class Dropbox extends AbstractAdapter
{
    protected $websiteName = 'Dropbox';

    protected $websiteProfileUrl = '';

    protected $requestTokenUrl = "https://api.dropbox.com/1/oauth/request_token";

    protected $authorizeUrl = "https://www.dropbox.com/1/oauth/authorize";

    protected $accessTokenUrl = "https://api.dropbox.com/1/oauth/access_token";

    public function accessTokenToArray(AccessToken $accessToken)
    {
        $token = parent::accessTokenToArray($accessToken);
        $token['remoteUserId'] = $accessToken->getParam('uid');
        return $token;
    }
}
