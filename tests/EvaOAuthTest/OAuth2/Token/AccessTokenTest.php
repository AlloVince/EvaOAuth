<?php

namespace Eva\EvaOAuthTest\OAuth2\Token;


use Eva\EvaOAuth\OAuth2\Token\AccessToken;

class AccessTokenTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
    }

    /**
     * @expectedException Eva\EvaOAuth\Exception\InvalidArgumentException
     */
    public function testConstruct()
    {
        new AccessToken('');
    }
}
