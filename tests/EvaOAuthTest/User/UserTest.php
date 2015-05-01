<?php

namespace Eva\EvaOAuthTest\OAuth2;

use Eva\EvaOAuth\User\StandardUser;

class UserTest extends \PHPUnit_Framework_TestCase
{
    public function testUserInit()
    {
        $userArray = [
            'version' => 'test_version',
            'provider' => 'test_provider',
            'id' => 'test_id',
            'name' => 'test_name',
            'avatar' => 'test_avatar',
            'email' => 'test_email',
            'extra' => [
                'test_extra'
            ],
        ];
        $user = new StandardUser($userArray);

        $this->assertEquals('test_version', $user->getVersion());
        $this->assertEquals('test_provider', $user->getProvider());
        $this->assertEquals('test_id', $user->getId());
        $this->assertEquals('test_name', $user->getName());
        $this->assertEquals('test_avatar', $user->getAvatar());
        $this->assertEquals('test_email', $user->getEmail());
        $this->assertEquals(['test_extra'], $user->getExtra());
        $this->assertEquals($userArray, $user->toArray());

    }
}
