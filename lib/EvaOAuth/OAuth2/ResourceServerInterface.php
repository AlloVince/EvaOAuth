<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaOAuth\OAuth2;

interface ResourceServerInterface
{
    const METHOD_GET = 'GET';

    const METHOD_POST = 'POST';

    const FORMAT_HTML = 'html';

    const FORMAT_XML = 'xml';

    const FORMAT_JSON = 'json';

    const FORMAT_JSONP = 'jsonp';

    /**
     * Access Token格式，可能是JSON或XML
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
