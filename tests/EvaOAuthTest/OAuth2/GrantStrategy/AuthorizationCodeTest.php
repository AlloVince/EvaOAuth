<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuthTest\OAuth2\GrantStrategy;

use Eva\EvaOAuth\OAuth2\GrantStrategy\AuthorizationCode;
use Eva\EvaOAuth\OAuth2\Providers\Douban;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;

class AuthorizationCodeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    protected $httpClient;

    /**
     * @var AuthorizationCode
     */
    protected $grant;

    public function setUp()
    {
        $this->httpClient = new Client();
        $this->grant = new AuthorizationCode($this->httpClient, [
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'redirect_uri' => 'test_redirect_uri',
            'scope' => 'test_scope',
        ]);
    }

    public function testAuthorizeUrl()
    {
        $authServer = new Douban();
        $url = $this->grant->getAuthorizeUrl($authServer);
        $urlParts = parse_url($url);
        parse_str($urlParts['query'], $query);

        $this->assertEquals('www.douban.com', $urlParts['host']);
        $this->assertEquals('/service/auth2/auth', $urlParts['path']);
        $this->assertEquals('test_client_id', $query['client_id']);
        $this->assertEquals('test_redirect_uri', $query['redirect_uri']);
        $this->assertEquals('test_scope', $query['scope']);
        $this->assertEquals('code', $query['response_type']);
        $this->assertEquals(10, strlen($query['state']));
    }

    /**
     * @expectedException Eva\EvaOAuth\Exception\InvalidArgumentException
     */
    public function testAccessTokenWithNoCode()
    {
        $this->grant->getAccessToken(new Douban());
    }

    /**
     * @expectedException Eva\EvaOAuth\Exception\RequestException
     */
    public function testGetAccessTokenFailed()
    {
        $this->httpClient->getEmitter()->attach(
            new Mock([
                new Response(400, ['Content-Type' => 'javascript'], Stream::factory('{"error":"some_error"}')),
            ])
        );
        $this->grant->getAccessToken(new Douban(), [
            'code' => 'test_code'
        ]);
    }

    public function testGetAccessToken()
    {
        $this->httpClient->getEmitter()->attach(
            new Mock([
                new Response(200, ['Content-Type' => 'javascript'], Stream::factory('{"access_token":"test_access_token"}')),
            ])
        );
        $token = $this->grant->getAccessToken(new Douban(), [
            'code' => 'test_code',
            'state' => 'test_state',
        ]);
        $this->assertEquals('test_access_token', $token->getTokenValue());
    }

}
