<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\Token;

use GuzzleHttp\Message\Response;

/**
 * Class TokenTrait
 * @package Eva\EvaOAuth\Token
 */
trait TokenTrait
{
    /**
     * @var Response
     */
    protected $response;

    /**
     * @var string
     */
    protected $tokenValue;

    /**
     * @param Response $response
     * @return $this
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return string
     */
    public function getTokenVersion()
    {
        return $this->tokenVersion;
    }

    /**
     * @return string
     */
    public function getTokenValue()
    {
        return $this->tokenValue;
    }

    /**
     * @return string
     */
    public function getTokenType()
    {
        return $this->tokenType;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $res = [];
        foreach ($this as $key => $value) {
            if (true === is_scalar($value) || true === is_array($value)) {
                $res[$key] = $value;
            }
        }
        return $res;
    }
}
