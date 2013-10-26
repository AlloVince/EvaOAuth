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

use Zend\Http\Response as HTTPResponse;
use EvaOAuth\Exception;
use EvaOAuth\Service\Http\Utility as HTTPUtility;

/**
 * @category   Zend
 * @package    Zend_OAuth
 */
abstract class AbstractToken extends \ZendOAuth\Token\AbstractToken
{
    /**@+
     * Token constants
     */
    const TOKEN_PARAM_KEY                = 'code';
    const TOKEN_SECRET_PARAM_KEY         = 'state';
    const TOKEN_PARAM_CALLBACK_CONFIRMED = 'oauth_callback_confirmed';
    /**@-*/

    protected $tokenFormat = 'json';

    public function setTokenFormat($tokenFormat)
    {
        $this->tokenFormart = $tokenFormat;
        return $this;
    }

    public function getTokenFormat()
    {
        return $this->tokenFormart;
    }

    public function __construct(
        HTTPResponse $response = null,
        HTTPUtility $utility = null,
        $format = null
    ) {
        if($format !== null){
            $this->setTokenFormat($format);
        }

        if ($response !== null) {
            $this->_response = $response;
            $params = $this->_parseParameters($response);
            if (count($params) > 0) {
                $this->setParams($params);
            }
        }
        if ($utility !== null) {
            $this->_httpUtility = $utility;
        } else {
            $this->_httpUtility = new HTTPUtility;
        }

    }

    /**
     * Sets the value for a parameter (e.g. token secret or other) and run
     * a simple filter to remove any trailing newlines.
     *
     * @param  string $key
     * @param  string $value
     * @return \ZendOAuth\Token\AbstractToken
     */
    public function setParam($key, $value)
    {
        if(is_string($value)){
            $this->_params[$key] = trim($value, "\n");
        } else {
            $this->_params[$key] = $value;
        }
        return $this;
    }

    protected function _parseParameters(HTTPResponse $response)
    {
        $params = array();
        $body   = $response->getBody();
        if (empty($body)) {
            return;
        }

        $tokenFormat = $this->getTokenFormat();

        switch($tokenFormat){
            case 'json':
            $params = \Zend\Json\Json::decode($body);
            break;
            case 'jsonp':
            break;
            case 'pair':
            $parts = explode('&', $body);
            foreach ($parts as $kvpair) {
                $pair = explode('=', $kvpair);
                $params[rawurldecode($pair[0])] = rawurldecode($pair[1]);
            }
            break;
            default:
            throw new Exception\InvalidArgumentException(sprintf(
                'Unable to handle access token response by undefined format %',
                $tokenFormat
            ));
        }

        return (array) $params;
    }
}
