<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\User;

use Eva\EvaOAuth\OAuth2\Token\AccessTokenInterface;

/**
 * Interface UserInterface
 * @package Eva\EvaOAuth\User
 */
interface UserInterface
{

    public function getUserId();

    public function getUserName();

    public function getAvatar();

    public function getProfile();

    public function __construct(AccessTokenInterface $accessToken);
}
