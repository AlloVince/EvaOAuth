<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\Event;

use GuzzleHttp\Event\SubscriberInterface;
use GuzzleHttp\Subscriber\Log\SimpleLogger;

class LoggerSubscriber implements SubscriberInterface
{

    protected $logger;

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The returned array keys MUST map to an event name. Each array value
     * MUST be an array in which the first element is the name of a function
     * on the EventSubscriber OR an array of arrays in the aforementioned
     * format. The second element in the array is optional, and if specified,
     * designates the event priority.
     *
     * For example, the following are all valid:
     *
     *  - ['eventName' => ['methodName']]
     *  - ['eventName' => ['methodName', $priority]]
     *  - ['eventName' => [['methodName'], ['otherMethod']]
     *  - ['eventName' => [['methodName'], ['otherMethod', $priority]]
     *  - ['eventName' => [['methodName', $priority], ['otherMethod', $priority]]
     *
     * @return array
     */
    public function getEvents()
    {
        return [
            //'beforeGetRequestToken' => ['beforeGetRequestToken'],
            //'afterGetRequestToken' => ['afterGetRequestToken'],
            'beforeAuthorize' => ['beforeAuthorize'],
            //'beforeGetAccessToken' => ['beforeGetAccessToken'],
            //'afterGetAccessToken' => ['afterGetAccessToken'],
            //'beforeRequestProtectedResource' => ['afterGetAccessToken'],
        ];
    }

    public function __construct($logger = null, $formatter = null)
    {
        $this->logger = $logger instanceof LoggerInterface
            ? $logger
            : new SimpleLogger($logger);

        $this->formatter = $formatter instanceof Formatter
            ? $formatter
            : new Formatter($formatter);
    }

    public function beforeAuthorize(BeforeAuthorize $beforeAuthorizeEvent)
    {
    }

    public function beforeGetAccessToken(BeforeGetAccessToken $beforeGetAccessTokenEvent)
    {
    }
}
