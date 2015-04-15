<?php

namespace Eva\EvaOAuthTest\OAuth1\Token;

use Eva\EvaOAuth\OAuth1\Providers\Twitter;
use Eva\EvaOAuth\OAuth1\Token\AccessToken;
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
            new Response(200, [], Stream::factory('oauth_token=test_token_value&oauth_token_secret=test_token_secret')),
            new Twitter(),
            [
                'consumer_key' => 'test_consumer_key',
                'consumer_secret' => 'test_consumer_secret',
            ]
        );
        $this->assertEquals(AccessTokenInterface::VERSION_OAUTH1, $token->getTokenVersion());
        $this->assertEquals('test_consumer_key', $token->getConsumerKey());
        $this->assertEquals('test_consumer_secret', $token->getConsumerSecret());
        $this->assertEquals('test_token_value', $token->getTokenValue());
        $this->assertEquals('test_token_secret', $token->getTokenSecret());
        $this->assertInstanceOf('GuzzleHttp\Message\Response', $token->getResponse());
    }

    /**
     * @expectedException Eva\EvaOAuth\Exception\InvalidArgumentException
     */
    public function testConstruct()
    {
        new AccessToken([]);
    }
}
