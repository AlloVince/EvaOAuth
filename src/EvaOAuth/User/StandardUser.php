<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaOAuth\User;

/**
 * Class StandardUser
 * @package Eva\EvaOAuth\User
 */
class StandardUser implements UserInterface
{
    /**
     * @var string
     */
    public $version;

    /**
     * @var string
     */
    public $provider;

    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $avatar;

    /**
     * @var string
     */
    public $email;

    /**
     * @var array
     */
    public $extra = [];

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return array
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $res = [];
        foreach ($this as $key => $value) {
            if (true === is_scalar($value) || true === is_array($value)) {
                $res[$key] = $value;
            }
        }
        return $res;
    }

    /**
     * @param array $info
     */
    public function __construct(array $info = [])
    {
        if ($info) {
            foreach ($info as $key => $value) {
                $this->$key = $value;
            }
        }
    }
}
