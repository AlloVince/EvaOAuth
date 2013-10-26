<?php
    
namespace EvaOAuth\Adapter\OAuth2;

use EvaOAuth\Adapter\OAuth2\AbstractAdapter;
use EvaOAuth\Service\Token\Access as AccessToken;

class Twitter extends AbstractAdapter
{
    //protected $accessTokenFormat = 'pair';

    protected $authorizeUrl = "https://oauth.twitter.com/2/authorize";
    protected $accessTokenUrl = "https://oauth.twitter.com/2/access_token";

}
