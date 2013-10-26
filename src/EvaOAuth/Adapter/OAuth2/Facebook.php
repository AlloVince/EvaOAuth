<?php
    
namespace EvaOAuth\Adapter\OAuth2;

use EvaOAuth\Adapter\OAuth2\AbstractAdapter;
use EvaOAuth\Service\Token\Access as AccessToken;

class Facebook extends AbstractAdapter
{
    protected $websiteName = 'Facebook';
    protected $websiteProfileUrl = 'http://www.facebook.com/%s/';

    protected $accessTokenFormat = 'pair';

    protected $authorizeUrl = "https://www.facebook.com/dialog/oauth";
    protected $accessTokenUrl = "https://graph.facebook.com/oauth/access_token";

    public function accessTokenToArray(AccessToken $accessToken)
    {
        $token = parent::accessTokenToArray($accessToken);
        $expiredTime = $accessToken->getParam('expires');
        if($expiredTime) {
            $token['expireTime'] =  gmdate('Y-m-d H:i:s', time() + $expiredTime);
        }
        if(!isset($token['remoteUserId']) || !$token['remoteUserId']){
            $token['remoteUserId'] = $this->getRemoteUserId();
        }
        return $token;
    }

    public function refreshAccessToken()
    {
        $accessToken = $this->getAccessToken();
        $client = $accessToken->getHttpClient();
    }

    public function getRemoteUserId()
    {
        $client = $this->getHttpClient();
        $client->setUri('https://graph.facebook.com/me');
        $response = $client->send();
        $data = $this->parseJsonResponse($response);
        return isset($data['id']) ? $data['id'] : null;
    }

}
