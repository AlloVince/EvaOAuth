<?php
    
namespace EvaOAuth\Adapter\OAuth2;

use EvaOAuth\Adapter\OAuth2\AbstractAdapter;
use EvaOAuth\Service\Token\Access as AccessToken;
use ZendOAuth\OAuth;

class Hundsun extends AbstractAdapter
{
    protected $authorizeUrl = "https://open.hs.net/oauth2/oauth2/authorize";
    protected $accessTokenUrl = "https://open.hs.net/oauth2/oauth2/token";

    protected $defaultOptions = array(
        'requestMethod' => OAuth::POST,
    );

    public function accessTokenToArray(AccessToken $accessToken)
    {
        $token = parent::accessTokenToArray($accessToken);
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
