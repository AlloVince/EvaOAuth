<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaOAuth\OAuth2;

/**
 * Interface ResourceServerInterface
 * @package Eva\EvaOAuth\OAuth2
 */
interface ResourceServerInterface
{
    /**
     * HTTP GET method
     */
    const METHOD_GET = 'GET';

    /**
     * HTTP POST method
     */
    const METHOD_POST = 'POST';

    /**
     * URL query Format
     */
    const FORMAT_QUERY = 'query';

    /**
     * JSON Format
     */
    const FORMAT_JSON = 'json';

    /**
     * JSONP Format
     */
    const FORMAT_JSONP = 'jsonp';

    /**
     * Access Token格式，可能是JSON或JSONP或Query
     * @return string
     */
    public function getAccessTokenFormat();

    /**
     * Access Token请求方法，一般是POST
     * @return string
     */
    public function getAccessTokenMethod();

    /**
     * Access Token Url
     * @return string
     */
    public function getAccessTokenUrl();

    /**
     * Server返回Access Token与自定义Access Token的映射关系
     * @return array
     */
    //public function getAccessTokenFields();
}
