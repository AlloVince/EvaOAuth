<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaOAuth\OAuth2\GrantStrategy;

use Eva\EvaOAuth\OAuth2\AuthorizationServerInterface;
use Eva\EvaOAuth\OAuth2\ResourceServerInterface;
use GuzzleHttp\Client;
use Eva\EvaOAuth\AccessTokenInterface;

/**
 * Interface GrantStrategyInterface
 * @package Eva\EvaOAuth\OAuth2\GrantStrategy
 */
interface GrantStrategyInterface
{
    /**
     * Generate OAuth authorize URL with query
     *
     * @param AuthorizationServerInterface $authServer
     * @return string
     */
    public function getAuthorizeUrl(AuthorizationServerInterface $authServer);

    /**
     * Redirect to authorize url (Maybe do nothing under some grant types)
     *
     * @param AuthorizationServerInterface $authServer
     * @return void
     */
    public function authorize(AuthorizationServerInterface $authServer);

    /**
     * @param ResourceServerInterface $resourceServer
     * @return AccessTokenInterface
     */
    public function getAccessToken(ResourceServerInterface $resourceServer);

    /**
     * @param Client $httpClient
     * @param array $options
     */
    public function __construct(Client $httpClient, array $options);
}
