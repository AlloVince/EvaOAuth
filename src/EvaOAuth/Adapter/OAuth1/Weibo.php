<?php
    
namespace EvaOAuth\Adapter\OAuth1;

use EvaOAuth\Adapter\OAuth1\AbstractAdapter;

class Weibo extends AbstractAdapter
{
    protected $requestTokenUrl = "https://api.t.sina.com.cn/oauth/request_token";

    protected $authorizeUrl = "https://api.t.sina.com.cn/oauth/authorize";

    protected $accessTokenUrl = "https://api.t.sina.com.cn/oauth/access_token";
}
