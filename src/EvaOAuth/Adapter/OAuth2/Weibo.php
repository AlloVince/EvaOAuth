<?php
    
namespace EvaOAuth\Adapter\OAuth2;

use EvaOAuth\Adapter\OAuth2\AbstractAdapter;
use EvaOAuth\Service\Token\Access as AccessToken;


class Weibo extends AbstractAdapter
{
    protected $websiteName = 'Weibo';
    protected $websiteProfileUrl = 'http://weibo.com/%s';

    protected $authorizeUrl = "https://api.weibo.com/oauth2/authorize";
    protected $accessTokenUrl = "https://api.weibo.com/oauth2/access_token";

    public function accessTokenToArray(AccessToken $accessToken)
    {
        $token = parent::accessTokenToArray($accessToken);
        $token['remoteUserId'] = $accessToken->getParam('uid');
        $token['remoteUserName'] = $this->getRemoteUserName();
        $token['remoteExtra'] = $this->getRawProfileString();
        $token['remoteNickName'] = $this->getRemoteNickName();
        $token['remoteImageUrl'] = $this->getImageUrl();
        return $token;
    }

    public function getRemoteUserName()
    {
        $data = $this->getRawProfile();
        if(!isset($data['name'])) {
            return null;
        }
        return $data['name'];
    }

    public function getRemoteNickName()
    {
        $data = $this->getRawProfile();
        if(!isset($data['screen_name'])) {
            return null;
        }
        return $data['screen_name'];
    }

    public function getImageUrl()
    {
        $data = $this->getRawProfile();
        if(!isset($data['avatar_large'])) {
            return null;
        }
        return $data['avatar_large'];
    }

    public function getRawProfile()
    {
        if($this->rawProfile) {
            return $this->rawProfile;
        }
        $client = $this->getHttpClient();
        $client->setUri('https://api.weibo.com/2/users/show.json');
        $client->setParameterGet(array(
            'uid' => $this->getAccessToken()->uid
        ));
        $response = $client->send();
        if($response->getStatusCode() >= 300) {
            return;
        }
        return $this->rawProfile = $this->parseJsonResponse($response);
    }
}
