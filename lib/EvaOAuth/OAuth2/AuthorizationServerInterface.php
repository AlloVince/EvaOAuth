<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\OAuth2;

/**
 * Interface AuthorizationServerInterface
 * @package Eva\EvaOAuth\OAuth2
 */
interface AuthorizationServerInterface
{
    /**
     * @return string
     */
    public function getAuthorizeUrl();
}
