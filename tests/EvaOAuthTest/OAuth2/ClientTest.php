<?php

namespace Eva\EvaOAuthTest\OAuth2;

use Eva\EvaOAuth\OAuth2\Client;

class ClientTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Client
     */
    protected $client;

    public function setUp()
    {
        $this->client = new Client(array(
            'client_id' => 'foo',
            'client_secret' => 'bar',
            'redirect_uri' => 'test',
        ));
    }

    /**
     * @expectedException Eva\EvaOAuth\Exception\InvalidArgumentException
     */
    public function testConstruct()
    {
        new Client(array());
    }

    public function testClientTimeout()
    {
        $this->assertInstanceOf('GuzzleHttp\Client', Client::getHttpClient());
    }

    public function testDefaultGrantStrategy()
    {
        $this->assertEquals(Client::GRANT_AUTHORIZATION_CODE, $this->client->getGrantStrategyName());
        $this->assertInstanceOf('Eva\EvaOAuth\OAuth2\GrantStrategy\AuthorizationCode', $this->client->getGrantStrategy());
        $this->assertEquals(4, count(Client::getGrantStrategyMapping()));
    }

    /**
     * @expectedException Eva\EvaOAuth\Exception\InvalidArgumentException
     */
    public function testChangeGrant()
    {
        $this->client->changeGrantStrategy('foo');
    }

    /**
     * @expectedException Eva\EvaOAuth\Exception\InvalidArgumentException
     */
    public function testRegisterGrant()
    {
        $this->client->registerGrantStrategy('foo', 'bar');
    }

    public function testRegisterAndChangeGrant()
    {
        $this->client->registerGrantStrategy('foo', 'Eva\EvaOAuthTest\OAuth2\GrantStrategy\FooStrategy');
        $this->client->changeGrantStrategy('foo');
    }
}
