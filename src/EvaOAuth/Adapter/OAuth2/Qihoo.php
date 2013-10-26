<?php
    
namespace EvaOAuth\Adapter\OAuth2;

use EvaOAuth\Adapter\OAuth2\AbstractAdapter;

class Qihoo extends AbstractAdapter
{
    protected $authorizeUrl = "https://openapi.360.cn/oauth2/authorize";
    protected $accessTokenUrl = "https://openapi.360.cn/oauth2/access_token";
}
