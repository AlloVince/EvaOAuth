<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuthTest\OAuth1\Signature;

use Eva\EvaOAuth\OAuth1\Signature\Hmac;
use Eva\EvaOAuth\OAuth1\Signature\PlainText;
use Eva\EvaOAuth\Utils\ResponseParser;
use Eva\EvaOAuth\Utils\Text;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class PlainTextTest extends \PHPUnit_Framework_TestCase
{

    public function testBaseSignature()
    {
        $this->assertEquals('secret&', (string) new PlainText(
            'input',
            'secret'
        ));

        $this->assertEquals('secret&token_secret', (string) new PlainText(
            'input',
            'secret',
            'token_secret'
        ));
    }
}
