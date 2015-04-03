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

/**
 * Interface GrantStrategyInterface
 * @package Eva\EvaOAuth\OAuth2\GrantStrategy
 */
interface GrantStrategyInterface
{
    public function getAuthorizeUrl(AuthorizationServerInterface $authServer);

    public function authorize(AuthorizationServerInterface $authServer);

    public function getAccessToken(ResourceServerInterface $resourceServer);

    public function __construct(Client $httpClient, $options);
}
