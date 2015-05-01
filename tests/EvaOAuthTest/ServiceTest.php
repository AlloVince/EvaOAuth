<?php

namespace Eva\EvaOAuthTest;

use Eva\EvaOAuth\Service;
use Eva\EvaOAuth\OAuth1\Consumer;
use Eva\EvaOAuth\OAuth2\Client;

class ServiceTest extends \PHPUnit_Framework_TestCase
{

    protected $options;

    public function setUp()
    {
        $this->options = [
            'key' => 'test_key',
            'secret' => 'test_secret',
            'callback' => 'test_callback',
        ];
    }

    /**
     * @expectedException Eva\EvaOAuth\Exception\BadMethodCallException
     */
    public function testNotExistProvider()
    {
        new Service('foo_service', []);
    }

    public function testConstruct()
    {
        $service = new Service('douban', $this->options);
        $this->assertInstanceOf('Eva\EvaOAuth\OAuth2\Client', $service->getAdapter());

        $service = new Service('twitter', $this->options);
        $this->assertInstanceOf('Eva\EvaOAuth\OAuth1\Consumer', $service->getAdapter());
    }

    /**
     * @expectedException Eva\EvaOAuth\Exception\InvalidArgumentException
     */
    public function testProviderInterface()
    {
        \Mockery::namedMock('FooProvider');
        Service::registerProvider('foo', 'FooProvider');
        $service = new Service('foo', $this->options);
    }

    public function testRegisterProvider()
    {
        \Mockery::namedMock('FooOAuth2Provider', 'Eva\EvaOAuth\OAuth2\Providers\AbstractProvider');
        \Mockery::namedMock('BarOAuth1Provider', 'Eva\EvaOAuth\OAuth1\Providers\AbstractProvider');
        Service::registerProviders([
            'foo' => 'FooOAuth2Provider',
            'bar' => 'BarOAuth2Provider',
        ]);
        $this->assertArrayHasKey('foo', Service::getProviders());
        $this->assertArrayHasKey('bar', Service::getProviders());

        $service = new Service('foo', $this->options);
        $this->assertInstanceOf('FooOAuth2Provider', $service->getProvider());
    }

    public function testDebug()
    {
        $service = new Service('foo', $this->options);
        $service->debug('test');
        $this->assertTrue(is_array($service->getHttpClient()->getEmitter()->listeners()));
    }

}
