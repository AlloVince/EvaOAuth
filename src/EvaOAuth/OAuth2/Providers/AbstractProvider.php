<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaOAuth\OAuth2\Providers;

use Eva\EvaOAuth\Exception\BadMethodCallException;
use Eva\EvaOAuth\OAuth2\AuthorizationServerInterface;
use Eva\EvaOAuth\OAuth2\ResourceServerInterface;
use Eva\EvaOAuth\OAuth2\Token\AccessToken;

/**
 * Class AbstractProvider
 * @package Eva\EvaOAuth\OAuth2\Providers
 */
abstract class AbstractProvider implements AuthorizationServerInterface, ResourceServerInterface
{
    /**
     * @var string
     */
    protected $providerName;

    /**
     * @var string
     */
    protected $authorizeUrl;

    /**
     * @var string
     */
    protected $accessTokenUrl;

    /**
     * @var string
     */
    protected $accessTokenMethod = ResourceServerInterface::METHOD_POST;

    /**
     * @var string
     */
    protected $accessTokenFormat = ResourceServerInterface::FORMAT_JSON;

    /**
     * @return string
     */
    public function getProviderName()
    {
        if ($this->providerName) {
            return $this->providerName;
        }
        return $this->providerName = array_pop(explode('\\', __CLASS__));
    }

    /**
     * @return string
     */
    public function getAuthorizeUrl()
    {
        return $this->authorizeUrl;
    }

    /**
     * @return string
     */
    public function getAccessTokenUrl()
    {
        return $this->accessTokenUrl;
    }

    /**
     * @return string
     */
    public function getAccessTokenMethod()
    {
        return $this->accessTokenMethod;
    }

    /**
     * @return string
     */
    public function getAccessTokenFormat()
    {
        return $this->accessTokenFormat;
    }

    /**
     * @param AccessToken $token
     * @return \Eva\EvaOAuth\User\UserInterface
     */
    public function getUser(AccessToken $token)
    {
        throw new BadMethodCallException(sprintf("Not supported feature"));
    }
}
