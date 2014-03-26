<?php
    
namespace EvaOAuth\Adapter\OAuth2;

use EvaOAuth\Adapter\OAuth2\AbstractAdapter;
use EvaOAuth\Service\Token\Access as AccessToken;
use ZendOAuth\OAuth as ZendOAuth;
use EvaOAuth\Exception;


class Douban extends AbstractAdapter
{
    protected $websiteName = 'Douban';

    protected $websiteProfileUrl = 'http://douban.com/people/%s/';

    protected $authorizeUrl = "https://www.douban.com/service/auth2/auth";

    protected $accessTokenUrl = "https://www.douban.com/service/auth2/token";

    protected $httpClientOptions = array(
        'callback' => 'getAccessTokenClient'
    );


    public function getAccessTokenClient($client)
    {
        $accessToken = $client->getToken();
        if(!$accessToken){
            throw new Exception\InvalidArgumentException(sprintf('No access token found'));
        }

        $accessTokenString = $accessToken->getParam('access_token');
        $client->setHeaders(array("Authorization: Bearer $accessTokenString"));
        return $client;
    }

    public function accessTokenToArray(AccessToken $accessToken)
    {
        $token = parent::accessTokenToArray($accessToken);
        $token['remoteUserId'] = $accessToken->getParam('douban_user_id');
        $token['remoteUserName'] = $this->getRemoteUserName();
        return $token;
    }

    public function getRemoteUserName()
    {
        $data = $this->getRawProfile();
        return isset($data['uid']) ? $data['uid'] : null;
    }

    public function getImageUrl()
    {
        $data = $this->getRawProfile();
        return isset($data['avatar']) ? $data['avatar'] : null;
    }


    public function getRawProfile()
    {
        if($this->rawProfile || false === $this->rawProfile) {
            return $this->rawProfile;
        }

        $client = $this->getHttpClient();
        $client->setUri('https://api.douban.com/v2/user/~me');
        $response = $client->send();
        if($response->getStatusCode() >= 300) {
            return $this->rawProfile = false;
        }
        return $this->rawProfile = $this->parseJsonResponse($response);
    }
}
