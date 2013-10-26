<?php

namespace EvaOAuth\Adapter\OAuth2;

use EvaOAuth\Service\Token\Access as AccessToken;


/**
 * @category   Zend
 * @package    Zend_Authentication
 * @subpackage Adapter
 */
interface AdapterInterface
{
    public function setOptions(array $params);
    public function getConsumer();
    public function accessTokenToArray(AccessToken $accessToken);
}
