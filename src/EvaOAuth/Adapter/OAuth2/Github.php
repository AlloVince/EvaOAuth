<?php
    
namespace EvaOAuth\Adapter\OAuth2;

use EvaOAuth\Adapter\OAuth2\AbstractAdapter;
use ZendOAuth\OAuth;
use EvaOAuth\Service\Token\Access as AccessToken;

class Github extends AbstractAdapter
{
    protected $accessTokenFormat = 'pair';

    protected $authorizeUrl = "https://github.com/login/oauth/authorize";
    protected $accessTokenUrl = "https://github.com/login/oauth/access_token";

    protected $defaultOptions = array(
        'requestScheme' => OAuth::REQUEST_SCHEME_POSTBODY,
        'scope' => 'user',
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
        $client->setUri('https://api.github.com/user');
        $response = $client->send();
        $data = $this->parseJsonResponse($response);
        return isset($data['id']) ? $data['id'] : null;
    }
}
