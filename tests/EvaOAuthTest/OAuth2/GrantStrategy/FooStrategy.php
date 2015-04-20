<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuthTest\OAuth2\GrantStrategy;

use Eva\EvaOAuth\AccessTokenInterface;
use Eva\EvaOAuth\OAuth2\AuthorizationServerInterface;
use Eva\EvaOAuth\OAuth2\GrantStrategy\GrantStrategyInterface;
use Eva\EvaOAuth\OAuth2\ResourceServerInterface;
use GuzzleHttp\Client;

class FooStrategy implements GrantStrategyInterface
{

    /**
     * Generate OAuth authorize URL with query
     *
     * @param AuthorizationServerInterface $authServer
     * @return string
     */
    public function getAuthorizeUrl(AuthorizationServerInterface $authServer)
    {
        // TODO: Implement getAuthorizeUri() method.
    }

    /**
     * Redirect to authorize url (Maybe do nothing under some grant types)
     *
     * @param AuthorizationServerInterface $authServer
     * @return void
     */
    public function requestAuthorize(AuthorizationServerInterface $authServer)
    {
        // TODO: Implement requestAuthorize() method.
    }

    /**
     * @param ResourceServerInterface $resourceServer
     * @param array $urlQuery
     * @return AccessTokenInterface
     */
    public function getAccessToken(ResourceServerInterface $resourceServer, array $urlQuery = array())
    {
        // TODO: Implement getAccessToken() method.
    }

    /**
     * @param Client $httpClient
     * @param array $options
     */
    public function __construct(Client $httpClient, array $options)
    {
        // TODO: Implement __construct() method.
    }
}