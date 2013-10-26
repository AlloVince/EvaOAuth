<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_OAuth
 */

namespace EvaOAuth\Service;

use ZendOAuth\Consumer as ZendConsumer;
use ZendOAuth\Exception;


/**
 * @category   Zend
 * @package    Zend_OAuth
 */
class Consumer extends ZendConsumer
{
    public function getRequestToken(
        array $customServiceParameters = null,
        $httpMethod = null,
        \ZendOAuth\Http\RequestToken $request = null
    ) {
        if ($request === null) {
            $request = new \EvaOAuth\Service\Http\RequestToken($this, $customServiceParameters);
        } elseif($customServiceParameters !== null) {
            $request->setParameters($customServiceParameters);
        }
        if ($httpMethod !== null) {
            $request->setMethod($httpMethod);
        } else {
            $request->setMethod($this->getRequestMethod());
        }

        $this->_requestToken = $request->execute();
        return $this->_requestToken;
    }

    public function getRedirectUrl(
        array $customServiceParameters = null,
        \ZendOAuth\Token\Request $token = null,
        \ZendOAuth\Http\UserAuthorization $redirect = null
    ) {
        $requestToken = $this->getRequestToken();
        return $this->getAuthorizeUrl() . '?' . http_build_query($requestToken->toArray());
    }


    public function getAccessToken(
        $queryData,
        \ZendOAuth\Token\Request $token,
        $httpMethod = null,
        \ZendOAuth\Http\AccessToken $request = null
    ) {
        $authorizedToken = new Token\AuthorizedRequest($queryData);
        if (!$authorizedToken->isValid()) {
            throw new Exception\InvalidArgumentException(
                'Response from Service Provider is not a valid authorized request token');
        }
        if ($request === null) {
            $request = new Http\AccessToken($this);
        }

        if($authorizedToken->getParam('code')){
            $request->setParameters(array(
                'code' => $authorizedToken->getParam('code')
            ));
        }

        if ($httpMethod !== null) {
            $request->setMethod($httpMethod);
        } else {
            $request->setMethod($this->getRequestMethod());
        }
        $this->_requestToken = $token;
        $this->_accessToken = $request->execute();
        return $this->_accessToken;
    }

    public function __construct($options = null)
    {
        $this->_config = new Config\OAuth2Config;
        if ($options !== null) {
            if ($options instanceof Traversable) {
                $options = ArrayUtils::iteratorToArray($options);
            }
            $this->_config->setOptions($options);
        }
    }
}
