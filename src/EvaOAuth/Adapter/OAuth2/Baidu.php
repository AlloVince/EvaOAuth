<?php
    
namespace EvaOAuth\Adapter\OAuth2;

use EvaOAuth\Adapter\OAuth2\AbstractAdapter;
use EvaOAuth\Service\Token\Access as AccessToken;
use ZendOAuth\OAuth;

class Baidu extends AbstractAdapter 
{
    protected $authorizeUrl = "https://openapi.baidu.com/oauth/2.0/authorize";
    protected $accessTokenUrl = "https://openapi.baidu.com/oauth/2.0/token";

    protected $defaultOptions = array(
        'requestMethod' => OAuth::GET,
    );

    public function accessTokenToArray(AccessToken $accessToken)
    {
        $token = parent::accessTokenToArray($accessToken);
        if(!isset($token['remoteUserId']) || !$token['remoteUserId']){
            $token['remoteUserId'] = $this->getRemoteUserId();
        }
        return $token;
    }

    public function getRemoteUserId()
    {
        $client = $this->getHttpClient();
        $client->setUri('https://openapi.baidu.com/rest/2.0/passport/users/getLoggedInUser');
        $response = $client->send();
        $data = $this->parseJsonResponse($response);
        return isset($data['uid']) ? $data['uid'] : null;
    }
}
