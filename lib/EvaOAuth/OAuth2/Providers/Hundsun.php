<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaOAuth\OAuth2\Providers;

use Eva\EvaOAuth\OAuth2\AuthorizationServerInterface;
use Eva\EvaOAuth\OAuth2\ResourceServerInterface;

class Hundsun implements AuthorizationServerInterface, ResourceServerInterface
{

    protected $authorizeUrl = 'https://open.hs.net/oauth2/oauth2/authorize';

    protected $accessTokenUrl = 'https://open.hs.net/oauth2/oauth2/token';

    protected $accessTokenMethod = ResourceServerInterface::METHOD_POST;

    public function getAuthorizeUrl()
    {
        return $this->authorizeUrl;
    }

    public function getAccessTokenUrl()
    {
        return $this->accessTokenUrl;
    }

    public function getAccessTokenMethod()
    {
        return $this->accessTokenMethod;
    }
}
