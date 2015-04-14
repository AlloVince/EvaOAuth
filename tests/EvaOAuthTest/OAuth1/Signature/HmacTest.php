<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuthTest\OAuth1\Signature;

use Eva\EvaOAuth\OAuth1\Signature\Hmac;
use Eva\EvaOAuth\Utils\ResponseParser;
use Eva\EvaOAuth\Utils\Text;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class HmacTest extends \PHPUnit_Framework_TestCase
{

    public function testBaseString()
    {
        $this->assertEquals('1Gv6XVo5dKoJ5IyyZxusyQDxk1U=', (string) new Hmac(
            '8Ap6YGs9BchvEFAOn6iw43jsjMKE48y3SDfacPyFTuI',
            Text::getBaseString('post', 'https://api.twitter.com/oauth/request_token', [
                'oauth_consumer_key' => 'X6vZ7YDHiod0hUyTQj0Gw',
                'oauth_signature_method' => 'HMAC-SHA1',
                'oauth_timestamp' => '1428979350',
                'oauth_nonce' => 'ddb73c89364451560652f53bcd8f14f7',
                'oauth_version' => '1.0',
            ])
        ));
    }
}
