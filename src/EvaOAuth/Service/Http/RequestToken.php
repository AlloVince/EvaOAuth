<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_OAuth
 */

namespace EvaOAuth\Service\Http;

use Zend\Http;
use ZendOAuth\OAuth;
use ZendOAuth\Token;
use ZendOAuth\Http as HTTPClient;

/**
 * @category   Zend
 * @package    Zend_OAuth
 */
class RequestToken extends \ZendOAuth\Http\RequestToken
{
    /**
     * Initiate a HTTP request to retrieve a Request Token.
     *
     * @return Token\Request
     */
    public function execute()
    {
        $params = array(
            'response_type' => $this->_consumer->getResponseType(),
			'client_id' => $this->_consumer->getConsumerKey(),
            'redirect_uri' => $this->_consumer->getCallbackUrl(),
			'state' => md5(uniqid(rand(), true)),
		);

        if($scope = $this->_consumer->getScope()){
            $params['scope'] = $scope;
        }

        $token = new \EvaOAuth\Service\Token\Request();
        $token->setParams($params);
        //$token->setToken($this->_consumer->getConsumerKey());
        //$token->setTokenSecret($this->_consumer->getConsumerSecret());
        return $token;
    }

}
