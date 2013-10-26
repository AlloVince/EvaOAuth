<?php
    
namespace EvaOAuth\Adapter\OAuth1;

use EvaOAuth\Adapter\OAuth1\AbstractAdapter;
use ZendOAuth\Token\Access as AccessToken;


class Tianya extends AbstractAdapter
{
    protected $websiteName = 'Tianya';

    protected $websiteProfileUrl = '';

    protected $requestTokenUrl = "http://open.tianya.cn/oauth/request_token.php";

    protected $authorizeUrl = "http://open.tianya.cn/oauth/authorize.php";

    protected $accessTokenUrl = "http://open.tianya.cn/oauth/access_token.php";

    public function accessTokenToArray(AccessToken $accessToken)
    {
        $token = parent::accessTokenToArray($accessToken);
        return $token;
    }
}
