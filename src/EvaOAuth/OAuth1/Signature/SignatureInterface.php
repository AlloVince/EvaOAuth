<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\OAuth1\Signature;

/**
 * Interface SignatureInterface
 * @package Eva\EvaOAuth\OAuth1\Signature
 */
interface SignatureInterface
{
    /**
     * Refer http://oauth.net/core/1.0a/#anchor15
     */
    const METHOD_HMAC_SHA1 = 'HMAC-SHA1';

    /**
     * Refer http://oauth.net/core/1.0a/#anchor18
     */
    const METHOD_RSA_SHA1 = 'RSA-SHA1';

    /**
     * Refer http://oauth.net/core/1.0a/#anchor21
     */
    const METHOD_PLAINTEXT = 'PLAINTEXT';
}
