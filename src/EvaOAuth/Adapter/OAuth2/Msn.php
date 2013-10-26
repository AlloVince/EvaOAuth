<?php
    
namespace EvaOAuth\Adapter\OAuth2;

use EvaOAuth\Adapter\OAuth2\AbstractAdapter;
use ZendOAuth\OAuth;
use EvaOAuth\Service\Token\Access as AccessToken;

class Msn extends AbstractAdapter
{
    protected $authorizeUrl = "https://oauth.live.com/authorize";
    protected $accessTokenUrl = "https://oauth.live.com/token";

    protected $defaultOptions = array(
        'requestScheme' => OAuth::REQUEST_SCHEME_POSTBODY,
        'scope' => 'wl.signin wl.basic',
    );


   public function accessTokenToArray(AccessToken $accessToken)
    {
        $token = parent::accessTokenToArray($accessToken);
        if(!isset($token['remoteUserId']) || !$token['remoteUserId']){
            $token['remoteUserId'] = $this->getRemoteUserId();
        }
        return $token;
    }

    public function getRemoteUserId()
    {
        $client = $this->getHttpClient();
        $client->setUri('https://apis.live.net/v5.0/me');
        $response = $client->send();
        //Fix Msn response problem
        $body = $response->getContent();
        $transferEncoding = $response->getHeaders()->get('Transfer-Encoding');
        if (!empty($transferEncoding)) {
            if (strtolower($transferEncoding->getFieldValue()) == 'chunked') {
                $body = $this->decodeChunkedBody($body);
            }
        }
        if (!function_exists('gzuncompress')) {
            throw new Exception\RuntimeException(
                'zlib extension is required in order to decode "deflate" encoding'
            );
        }
        $responseText = gzinflate($body);
        $data = \Zend\Json\Json::decode($responseText, \Zend\Json\Json::TYPE_ARRAY);
        return isset($data['id']) ? $data['id'] : null;
    }

    /**
     * Decode a "chunked" transfer-encoded body and return the decoded text
     *
     * @param  string $body
     * @return string
     * @throws Exception\RuntimeException
     */
    protected function decodeChunkedBody($body)
    {
        $decBody = '';

        while (trim($body)) {
            if (! preg_match("/^([\da-fA-F]+)[^\r\n]*\r\n/sm", $body, $m)) {
                throw new Exception\RuntimeException(
                    "Error parsing body - doesn't seem to be a chunked message"
                );
            }

            $length   = hexdec(trim($m[1]));
            $cut      = strlen($m[0]);
            $decBody .= substr($body, $cut, $length);
            $body     = substr($body, $cut + $length + 2);
        }

        return $decBody;
    }
}
