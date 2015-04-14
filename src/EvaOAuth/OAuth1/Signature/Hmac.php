<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\OAuth1\Signature;

class Hmac implements SignatureInterface
{
    protected $secert;

    protected $input;

    protected $tokenSecret;


    public static function verify($str)
    {

    }

    public function __toString()
    {
        return base64_encode(hash_hmac('sha1', $this->input, $this->secret . '&' . $this->tokenSecret, true));
    }

    public function __construct($secret, $input, $tokenSecret = '')
    {
        $this->secret = (string) $secret;
        $this->input = (string) $input;
        $this->tokenSecret = (string) $tokenSecret;
    }

}