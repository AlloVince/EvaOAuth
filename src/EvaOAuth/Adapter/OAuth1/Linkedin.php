<?php
    
namespace EvaOAuth\Adapter\OAuth1;

use EvaOAuth\Adapter\OAuth1\AbstractAdapter;
use ZendOAuth\Token\Access as AccessToken;


class Linkedin extends AbstractAdapter
{
    protected $websiteName = 'LinkedIn';

    protected $websiteProfileUrl = '';

    protected $requestTokenUrl = "https://api.linkedin.com/uas/oauth/requestToken";

    protected $authorizeUrl = "https://api.linkedin.com/uas/oauth/authenticate";

    protected $accessTokenUrl = "https://api.linkedin.com/uas/oauth/accessToken";

    public function accessTokenToArray(AccessToken $accessToken)
    {
        $token = parent::accessTokenToArray($accessToken);
        $expiredTime = $accessToken->getParam('oauth_authorization_expires_in');
        $token['expireTime'] = gmdate('Y-m-d H:i:s', time() + $expiredTime);
        return $token;
    }
}
