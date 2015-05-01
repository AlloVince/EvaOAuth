<?php
//Note: Source code of this file is from https://github.com/guzzle/log-subscriber
namespace Eva\EvaOAuthTest\Events;

use Eva\EvaOAuth\Events\Formatter;
use Eva\EvaOAuth\Events\LogSubscriber;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;

class LogSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testLogsAfterSending()
    {
        $resource = fopen('php://temp', 'r+');
        $logger = new LogSubscriber($resource, Formatter::CLF);
        $client = new Client();
        $client->getEmitter()->attach($logger);
        $client->getEmitter()->attach(new Mock([new Response(200)]));
        $client->get('http://httbin.org/get');
        rewind($resource);
        $this->assertNotFalse(strpos(stream_get_contents($resource), '"GET /get " 200'));
        fclose($resource);
    }

    public function testLogsAfterError()
    {
        $resource = fopen('php://temp', 'r+');
        $logger = new LogSubscriber($resource, Formatter::CLF);
        $client = new Client();
        $client->getEmitter()->attach($logger);
        $client->getEmitter()->attach(new Mock([new Response(500)]));
        try {
            $client->get('http://httbin.org/get');
        } catch (\Exception $e) {
        }
        rewind($resource);
        $this->assertNotFalse(strpos(stream_get_contents($resource), 'CRITICAL'));
        fclose($resource);
    }
}
