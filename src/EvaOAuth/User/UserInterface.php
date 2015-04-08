<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\User;

/**
 * Interface UserInterface
 * @package Eva\EvaOAuth\User
 */
interface UserInterface
{
    /**
     * @return string
     */
    public function getVersion();

    /**
     * @return string
     */
    public function getProvider();

    /**
     * @return string
     */
    public function getId();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getAvatar();

    /**
     * @return string
     */
    public function getEmail();

    /**
     * @return array
     */
    public function getExtra();
}
