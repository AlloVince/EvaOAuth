<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_OAuth
 */

namespace EvaOAuth\Service\Config;

use Traversable;
use ZendOAuth\OAuth;
use ZendOAuth\Exception;
use ZendOAuth\Config as OAuthConfig;
use Zend\Stdlib\ArrayUtils;
use Zend\Uri;
use ZendOAuth\Token\TokenInterface;

/**
 * @category   Zend
 * @package    Zend_OAuth
 */
class OAuth2Config extends \ZendOAuth\Config\StandardConfig
{
    const ACCESS_TOKEN_FORMAT_JSON = 'json';
    const ACCESS_TOKEN_FORMAT_JSONP = 'jsonp';
    const ACCESS_TOKEN_FORMAT_XML = 'xml';
    const ACCESS_TOKEN_FORMAT_PAIR = 'pair';

    protected $responseType = 'code';

    protected $scope;

    protected $accessTokenFormat = 'json';

    public function getScope()
    {
        return $this->scope;
    }

    public function setScope($scope)
    {
        $this->scope = $scope;
        return $this;
    }

    public function getResponseType()
    {
        return $this->responseType;
    }

    public function setResponseType($responseType)
    {
        $this->responseType = $responseType;
        return $this;
    }

    public function getAccessTokenFormat()
    {
        return $this->accessTokenFormat;
    }

    public function setAccessTokenFormat($accessToken)
    {
        if($accessToken != self::ACCESS_TOKEN_FORMAT_JSON
            && $accessToken != self::ACCESS_TOKEN_FORMAT_JSONP
            && $accessToken != self::ACCESS_TOKEN_FORMAT_XML
            && $accessToken != self::ACCESS_TOKEN_FORMAT_PAIR
        ){
            throw new Exception\InvalidArgumentException(sprintf(
                'Undefined access token format %s input, accept format are json|jsonp|xml',
                $accessToken
            ));
        }

        $this->accessTokenFormat = $accessToken;
        return $this;
    }

    /**
     * Three request schemes are defined by OAuth, of which passing
     * all OAuth parameters by Header is preferred. The other two are
     * POST Body and Query String.
     *
     * @var string
     */
    protected $_requestScheme = OAuth::REQUEST_SCHEME_POSTBODY;

    /**
     * Preferred request Method - one of GET or POST - which Zend_OAuth
     * will enforce as standard throughout the library. Generally a default
     * of POST works fine unless a Provider specifically requires otherwise.
     *
     * @var string
     */
    protected $_requestMethod = OAuth::POST;

    /**
     * OAuth Version; This defaults to 1.0 - Must not be changed!
     *
     * @var string
     */
    protected $_version = '2.0';

    /**
     * Parse option array and setup options using their
     * relevant mutators.
     *
     * @param  array $options
     * @return StandardConfig
     */
    public function setOptions(array $options)
    {
        parent::setOptions($options);
        foreach ($options as $key => $value) {
            switch ($key) {
                //New in oauth2.0
                case 'code':
                    $this->setCode($value);
                    break;
                case 'scope':
                    $this->setScope($value);
                    break;
                case 'responseType':
                    $this->setResponseType($value);
                    break;
                case 'accessTokenFormat':
                    $this->setAccessTokenFormat($value);
                    break;
                //New in oauth2.0 end
            }
        }
        return $this;
    }

}
