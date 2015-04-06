<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaOAuth\Token;

use GuzzleHttp\Message\Response;

/**
 * Interface AccessTokenInterface
 * @package Eva\EvaOAuth\OAuth2\Token
 */
interface AccessTokenInterface
{
    /**
     * Token version for OAuth1
     */
    const VERSION_OAUTH1 = 'OAuth1';

    /**
     * Token version for OAuth2
     */
    const VERSION_OAUTH2 = 'OAuth2';

    /**
     * @return string
     */
    public function getTokenVersion();

    /**
     * @return string
     */
    public function getTokenValue();

    /**
     * @return Response
     */
    public function getResponse();
}
