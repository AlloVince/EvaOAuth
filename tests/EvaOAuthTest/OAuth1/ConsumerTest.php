<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuthTest\OAuth1;

use Eva\EvaOAuth\OAuth1\Consumer;
use Eva\EvaOAuth\OAuth1\Providers\Twitter;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;

class ConsumerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Consumer
     */
    protected $consumer;

    public function setUp()
    {
        $this->consumer = new Consumer([
            'consumer_key' => 'test_consumer_key',
            'consumer_secret' => 'test_consumer_secret',
            'callback' => 'http://test_callback/'
        ]);
    }

    /**
     * @expectedException Eva\EvaOAuth\Exception\InvalidArgumentException
     */
    public function testConstruct()
    {
        new Consumer(array());
    }

    public function testGetRequestToken()
    {
        $consumer = $this->consumer;
        $httpClient = $consumer::getHttpClient();
        $httpClient->getEmitter()->attach(
            new Mock([
                new Response(200, [], Stream::factory('oauth_token=test_request_token&oauth_token_secret=test_request_token_secret&oauth_callback_confirmed=true')),
            ])
        );
        $requestToken = $this->consumer->getRequestToken(new Twitter());
        $this->assertEquals('test_request_token', $requestToken->getTokenValue());
        $this->assertEquals('test_request_token_secret', $requestToken->getTokenSecret());
    }

    /**
     * @expectedException Eva\EvaOAuth\Exception\RequestException
     */
    public function testGetRequestTokenFailed()
    {
        $consumer = $this->consumer;
        $httpClient = $consumer::getHttpClient();
        $httpClient->getEmitter()->attach(
            new Mock([
                new Response(400, [], Stream::factory('error happened')),
            ])
        );
        $requestToken = $this->consumer->getRequestToken(new Twitter());
    }

    /*
    public function testGetAccessToken()
    {
        $consumer = $this->consumer;
        $httpClient = $consumer::getHttpClient();
        $httpClient->getEmitter()->attach(
            new Mock([
                new Response(200, [], Stream::factory('oauth_token=test_access_token&oauth_token_secret=test_token_secret')),
            ])
        );
        $accessToken = $consumer->getAccessToken(new Twitter(), [
            'oauth_token' => 'test_request_token',
            'oauth_verifier' => 'test_request_token_verifier',
        ]);
        $this->assertEquals('test_access_token', $accessToken->getTokenValue());
        $this->assertEquals('test_token_secret', $accessToken->getTokenSecret());
    }
    */
}
