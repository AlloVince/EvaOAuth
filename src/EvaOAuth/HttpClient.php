<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth;

use Eva\EvaOAuth\Events\EventsManager;
use GuzzleHttp\Client;

/**
 * Class HttpClient
 * @package Eva\EvaOAuth
 */
class HttpClient extends Client
{
    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $options = array_merge($options, [
            'emitter' => EventsManager::getEmitter()
        ]);
        parent::__construct($options);
    }
}
