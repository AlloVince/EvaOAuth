<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\OAuth1\Token;

/**
 * Class RequestToken
 * @package Eva\EvaOAuth\OAuth1\Token
 */
class RequestToken implements RequestTokenInterface
{
    /**
     * @var string
     */
    protected $tokenValue;

    /**
     * @var string
     */
    protected $tokenSecret;

    /**
     * @return string
     */
    public function getTokenValue()
    {
        return $this->tokenValue;
    }

    /**
     * @return array
     */
    public function getTokenSecret()
    {
        return $this->tokenSecret;
    }

    /**
     * @param $tokenValue
     * @param $tokenSecret
     */
    public function __construct($tokenValue, $tokenSecret)
    {
        $this->tokenValue = (string)$tokenValue;
        $this->tokenSecret = (string)$tokenSecret;
    }
}
