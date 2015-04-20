<?php

namespace Eva\EvaOAuthTest;

use Eva\EvaOAuth\AuthorizedHttpClient;
use Eva\EvaOAuth\OAuth2\Token\AccessToken as OAuth2AccessToken;
use Eva\EvaOAuth\OAuth1\Token\AccessToken as OAuth1AccessToken;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;

class AuthorizedHttpClientTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
    }

    public function testOAuth2Header()
    {
        /** @var Client $client */
        $client = new AuthorizedHttpClient(new OAuth2AccessToken('foo'));
        $request = $client->createRequest('GET', 'http://baidu.com');
        $client->getEmitter()->attach(
            new Mock([
                new Response(200, [], Stream::factory('some response')),
            ])
        );
        $client->send($request);
        $this->assertEquals('Bearer foo', $request->getHeader('Authorization'));
    }

    public function testOAuth1Header()
    {
        /** @var Client $client */
        $client = new AuthorizedHttpClient(new OAuth1AccessToken([
            'consumer_key' => 'test_consumer_key',
            'consumer_secret' => 'test_consumer_secret',
            'token_value' => 'test_token_value',
            'token_secret' => 'test_token_secret',
        ]));
        $request = $client->createRequest('GET', 'http://baidu.com');
        $client->getEmitter()->attach(
            new Mock([
                new Response(200, [], Stream::factory('some response')),
            ])
        );
        $client->send($request);
        $this->assertStringStartsWith('OAuth', $request->getHeader('Authorization'));
    }
}

