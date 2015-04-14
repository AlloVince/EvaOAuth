<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\OAuth1\Signature;

interface SignatureInterface
{
    const METHOD_HMAC_SHA1 = 'HMAC-SHA1';

    const METHOD_RSA_SHA1 = 'RSA-SHA1';

    const METHOD_PLAINTEXT = 'PLAINTEXT';

    //public function __construct($secret, $string);
}
