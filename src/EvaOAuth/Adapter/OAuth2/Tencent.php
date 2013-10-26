<?php
    
namespace EvaOAuth\Adapter\OAuth2;

use EvaOAuth\Adapter\OAuth2\AbstractAdapter;
use EvaOAuth\Service\Token\Access as AccessToken;

class Tencent extends AbstractAdapter
{
    protected $accessTokenFormat = 'pair';
    protected $authorizeUrl = "https://graph.qq.com/oauth2.0/authorize";
    protected $accessTokenUrl = "https://graph.qq.com/oauth2.0/token";


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
        $client->setUri('https://graph.qq.com/oauth2.0/me');
        $response = $client->send();

        $data = $this->parseJsonpResponse($response);
        return isset($data['client_id']) ? $data['client_id'] : null;
    }
}
