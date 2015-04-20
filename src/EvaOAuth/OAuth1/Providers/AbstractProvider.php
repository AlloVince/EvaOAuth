<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\OAuth1\Providers;

use Eva\EvaOAuth\Exception\BadMethodCallException;
use Eva\EvaOAuth\OAuth1\ServiceProviderInterface;
use Eva\EvaOAuth\OAuth1\Token\AccessToken;

/**
 * Class AbstractProvider
 * @package Eva\EvaOAuth\OAuth1\Providers
 */
class AbstractProvider implements ServiceProviderInterface
{
    /**
     * @var string
     */
    protected $providerName;

    /**
     * @var string
     */
    protected $requestTokenFormat = ServiceProviderInterface::FORMAT_QUERY;

    /**
     * @var string
     */
    protected $requestTokenUrl;

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
    protected $accessTokenMethod = ServiceProviderInterface::METHOD_POST;

    /**
     * @var string
     */
    protected $accessTokenFormat = ServiceProviderInterface::FORMAT_QUERY;

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
    public function getRequestTokenUrl()
    {
        return $this->requestTokenUrl;
    }

    /**
     * @return string
     */
    public function getRequestTokenFormat()
    {
        return $this->requestTokenFormat;
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
     * @throws BadMethodCallException
     */
    public function getUser(AccessToken $token)
    {
        throw new BadMethodCallException(sprintf("Not supported feature"));
    }
}
