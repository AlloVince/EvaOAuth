<?php

namespace Eva\EvaOAuthTest;

use Eva\EvaOAuth\AuthorizedHttpClient;
use Eva\EvaOAuth\OAuth2\Token\AccessToken;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;

class AuthorizedHttpClientTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->mock = new Mock([
            new Response(200, ['Content-Type' => 'javascript'], Stream::factory('{"status":"success"}')),
        ]);
    }

    public function testHeader()
    {
        /** @var Client $client */
        $client = new AuthorizedHttpClient(new AccessToken('foo'));
        $request = $client->createRequest('GET', 'http://baidu.com');
        $client->getEmitter()->attach($this->mock);
        $client->send($request);
        $this->assertEquals('Bearer foo', $request->getHeader('Authorization'));
    }
}
