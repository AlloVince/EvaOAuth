<?php

namespace Eva\EvaOAuthTest\OAuth2\Token;

use Eva\EvaOAuth\OAuth2\Providers\Douban;
use Eva\EvaOAuth\OAuth2\Token\AccessToken;
use Eva\EvaOAuth\Token\AccessTokenInterface;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class AccessTokenTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
    }

    public function testFactory()
    {
        $token = AccessToken::factory(
            new Response(200, [], Stream::factory('{"access_token":"test_token_value","expires_in":3600,"refresh_token":"test_refresh_token"}')),
            new Douban()
        );
        $this->assertEquals(AccessTokenInterface::VERSION_OAUTH2, $token->getTokenVersion());
        $this->assertEquals('test_token_value', $token->getTokenValue());
        $this->assertEquals('test_refresh_token', $token->getRefreshToken());
        $this->assertTrue(is_numeric($token->getExpireTimestamp()));
        $this->assertInstanceOf('GuzzleHttp\Message\Response', $token->getResponse());
    }

    /**
     * @expectedException Eva\EvaOAuth\Exception\InvalidArgumentException
     */
    public function testConstruct()
    {
        new AccessToken('');
    }
}
