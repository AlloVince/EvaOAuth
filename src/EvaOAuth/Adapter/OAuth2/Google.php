<?php
    
namespace EvaOAuth\Adapter\OAuth2;

use EvaOAuth\Adapter\OAuth2\AbstractAdapter;
use ZendOAuth\OAuth;
use EvaOAuth\Service\Token\Access as AccessToken;

class Google extends AbstractAdapter
{
    protected $authorizeUrl = "https://accounts.google.com/o/oauth2/auth";
    protected $accessTokenUrl = "https://accounts.google.com/o/oauth2/token";

    protected $defaultOptions = array(
        'requestScheme' => OAuth::REQUEST_SCHEME_POSTBODY,
        'scope' => 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email',
    );

    protected $httpClientOptions = array(
        'callback' => 'getAccessTokenClient',
    );

    public function getAccessTokenClient($client)
    {
        $accessToken = $client->getToken();
        if(!$accessToken){
            throw new Exception\InvalidArgumentException(sprintf('No access token found'));
        }

        $accessTokenString = $accessToken->getParam('access_token');
        $client->setHeaders(array("Authorization: OAuth $accessTokenString"));
        return $client;
    }

    public function accessTokenToArray(AccessToken $accessToken)
    {
        $token = parent::accessTokenToArray($accessToken);
        if(!isset($token['remoteUserId']) || !$token['remoteUserId']){
            $token['remoteUserId'] = $this->getRemoteUserId();
            $token['remoteEmail'] = $this->getEmail();
            $token['remoteNickName'] = $this->getRemoteNickName();
            $token['remoteImageUrl'] = $this->getImageUrl();
            $token['remoteExtra'] = $this->getRawProfileString();
        }
        return $token;
    }

    public function getRemoteUserId()
    {
        $data = $this->getRawProfile();
        return isset($data['id']) ? $data['id'] : null;
    }

    public function getEmail()
    {
        $data = $this->getRawProfile();
        return isset($data['email']) ? $data['email'] : null;
    }

    public function getRemoteNickName()
    {
        $data = $this->getRawProfile();
        return isset($data['name']) ? $data['name'] : null;
    }

    public function getImageUrl()
    {
        $data = $this->getRawProfile();
        return isset($data['picture']) ? $data['picture'] : null;
    }

    public function getRawProfile()
    {
        if($this->rawProfile) {
            return $this->rawProfile;
        }
        $client = $this->getHttpClient();
        $client->setUri('https://www.googleapis.com/oauth2/v2/userinfo');
        $response = $client->send();
        if($response->getStatusCode() >= 300) {
            return;
        }
        return $this->rawProfile = $this->parseJsonResponse($response);
    }
}
