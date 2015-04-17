<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\User;

use Eva\EvaOAuth\Token\AccessTokenInterface;

/**
 * Interface UserProviderInterface
 * @package Eva\EvaOAuth\User
 */
interface UserProviderInterface
{
    /**
     * @param AccessTokenInterface $token
     * @return UserInterface
     */
    public function getUser(AccessTokenInterface $token);
}
