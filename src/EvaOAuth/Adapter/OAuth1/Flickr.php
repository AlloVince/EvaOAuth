<?php
    
namespace EvaOAuth\Adapter\OAuth1;

use EvaOAuth\Adapter\OAuth1\AbstractAdapter;
use ZendOAuth\OAuth;
use ZendOAuth\Token\Access as AccessToken;

class Flickr extends AbstractAdapter
{
    protected $requestTokenUrl = "http://www.flickr.com/services/oauth/request_token";

    protected $authorizeUrl = "http://www.flickr.com/services/oauth/authorize";

    protected $accessTokenUrl = "http://www.flickr.com/services/oauth/access_token";

    protected $defaultOptions = array(
        //Flickr required GET method
        'requestMethod' => OAuth::GET,
    );

    public function accessTokenToArray(AccessToken $accessToken)
    {
        $token = parent::accessTokenToArray($accessToken);
        $token['remoteUserId'] = $accessToken->getParam('user_nsid');
        $token['remoteUserName'] = $accessToken->getParam('username');
        return $token;
    }
}
