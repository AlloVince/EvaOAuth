<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_OAuth
 */

namespace EvaOAuth\Service\Token;

use EvaOAuth\Service\Client;
use ZendOAuth\Exception;
use ZendOAuth\Config\ConfigInterface as Config;
use Zend\Uri;

/**
 * @category   Zend
 * @package    Zend_OAuth
 */
class Access extends AbstractToken
{
    const TOKEN_PARAM_KEY  = 'access_token';
    const EXPIRED_KEY = 'expires_in';
    const REFRESH_TOKEN_KEY = 'refresh_token';

    /**
     * Sets the value for a Token.
     *
     * @param  string $token
     * @return \ZendOAuth\Token\AbstractToken
     */
    public function setToken($token)
    {
        $this->setParam(self::TOKEN_PARAM_KEY, $token);
        return $this;
    }

    /**
     * Gets the value for a Token.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->getParam(self::TOKEN_PARAM_KEY);
    }

    public function getExpiredTime()
    {
        $expiredTime = $this->getParam(self::EXPIRED_KEY);
        if($expiredTime && is_numeric($expiredTime)){
            return gmdate('Y-m-d H:i:s', time() + $expiredTime);
        }
    }

    public function getRefreshToken()
    {
        return $this->getParam(self::REFRESH_TOKEN_KEY);
    }

    /**
     * Cast to HTTP header
     *
     * @param  string $url
     * @param  Config $config
     * @param  null|array $customParams
     * @param  null|string $realm
     * @return string
     * @throws Exception\InvalidArgumentException
     */
    public function toHeader(
        $url, Config $config, array $customParams = null, $realm = null
    ) {
        $uri = Uri\UriFactory::factory($url);
        if (!$uri->isValid()
            || !in_array($uri->getScheme(), array('http', 'https'))
        ) {
            throw new Exception\InvalidArgumentException(
                '\'' . $url . '\' is not a valid URI'
            );
        }
        $params = $this->_httpUtility->assembleParams($url, $config, $customParams);
        return $this->_httpUtility->toAuthorizationHeader($params, $realm);
    }

    /**
     * Cast to HTTP query string
     *
     * @param  mixed $url
     * @param  ZendOAuth\Config $config
     * @param  null|array $params
     * @return string
     * @throws Exception\InvalidArgumentException
     */
    public function toQueryString($url, Config $config, array $params = null)
    {
        $uri = Uri\UriFactory::factory($url);
        if (!$uri->isValid()
            || !in_array($uri->getScheme(), array('http', 'https'))
        ) {
            throw new Exception\InvalidArgumentException(
                '\'' . $url . '\' is not a valid URI'
            );
        }
        $params = $this->_httpUtility->assembleParams($url, $config, $params);
        return $this->_httpUtility->toEncodedQueryString($params);
    }

    /**
     * Get OAuth client
     *
     * @param  array $oauthOptions
     * @param  null|string $uri
     * @param  null|array|\Traversable $config
     * @param  bool $excludeCustomParamsFromHeader
     * @return Client
     */
    public function getHttpClient(array $oauthOptions = array(), $uri = null, $config = null, $excludeCustomParamsFromHeader = true)
    {
        $client = new Client($oauthOptions, $uri, $config, $excludeCustomParamsFromHeader);
        $client->setToken($this);
        $client->setOptions(array(
            'sslverifypeer' => false
        ));
        return $client;
    }
}
