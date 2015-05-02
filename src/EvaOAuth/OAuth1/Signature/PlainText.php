<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\OAuth1\Signature;

/**
 * Class PlainText
 * @package Eva\EvaOAuth\OAuth1\Signature
 */
class PlainText implements SignatureInterface
{
    /**
     * @var string
     */
    protected $secert;

    /**
     * @var string
     */
    protected $input;

    /**
     * @var string
     */
    protected $tokenSecret;


    public static function verify($str)
    {
        //TODO: verify logic
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->secret . '&' . $this->tokenSecret;
    }

    /**
     * @param string $input
     * @param string $secret
     * @param string $tokenSecret
     */
    public function __construct($input, $secret, $tokenSecret = null)
    {
        $this->secret = (string) rawurlencode($secret);
        $this->tokenSecret = $tokenSecret ? (string) rawurlencode($tokenSecret) : '';
    }
}
