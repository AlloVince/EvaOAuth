<?php
    
namespace EvaOAuth\Adapter\OAuth2;

use EvaOAuth\Adapter\OAuth2\AbstractAdapter;
use EvaOAuth\Service\Token\Access as AccessToken;

class Foursquare extends AbstractAdapter
{
    protected $websiteName = 'Foursquare';

    protected $authorizeUrl = "https://foursquare.com/oauth2/authorize";
    protected $accessTokenUrl = "https://foursquare.com/oauth2/access_token";

    public function accessTokenToArray(AccessToken $accessToken)
    {
        $token = parent::accessTokenToArray($accessToken);
        $expiredTime = $accessToken->getParam('expires');
        if(!isset($token['remoteUserId']) || !$token['remoteUserId']){
            $token['remoteUserId'] = $this->getRemoteUserId($token['token']);
        }
        return $token;
    }

    public function getRemoteUserId($accessToken)
    {
        $client = $this->getHttpClient();
        $client->setUri('https://api.foursquare.com/v2/users/self');
        //Foursquare use EvaOAuth_token instead of oauth_token
        $client->setParameterGet(array(
            'oauth_token' => $accessToken
        ));
        $response = $client->send();
        $data = $this->parseJsonResponse($response);
        return isset($data['response']['user']['id']) ? $data['response']['user']['id'] : null;
    }
}
