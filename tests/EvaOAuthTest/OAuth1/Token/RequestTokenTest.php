<?php

namespace Eva\EvaOAuthTest\OAuth1\Token;

use Eva\EvaOAuth\OAuth1\Token\RequestToken;

class RequestTokenTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
    }

    /**
     * @expectedException Eva\EvaOAuth\Exception\InvalidArgumentException
     */
    public function testConstruct()
    {
        new RequestToken('', '');
    }
}
