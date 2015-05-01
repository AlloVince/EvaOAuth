<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\Events;

use GuzzleHttp\Event\SubscriberInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Event\RequestEvents;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Event\ErrorEvent;
use Psr\Log\LogLevel;

/**
 * Class LogSubscriber
 * @package Eva\EvaOAuth\Events
 */
class LogSubscriber implements SubscriberInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Formatter
     */
    protected $formatter;

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
            //Guzzle default events
            'complete' => ['onComplete', RequestEvents::VERIFY_RESPONSE - 10],
            'error' => ['onError', RequestEvents::EARLY],
            'beforeAuthorize' => ['beforeAuthorize'],
            //'beforeGetAccessToken' => ['beforeGetAccessToken'],
        ];
    }

    public function __construct($logger = null, $formatter = null)
    {
        if ($logger instanceof LoggerInterface) {
            $this->logger = $logger;
        } else {
            $this->logger = new Logger('EvaOAuth');
            $this->logger->pushHandler(new StreamHandler($logger, Logger::DEBUG));
        }

        $this->formatter = $formatter instanceof Formatter
            ? $formatter
            : new Formatter($formatter);
    }

    public function beforeAuthorize(BeforeAuthorize $beforeAuthorizeEvent)
    {
        $this->logger->log(LogLevel::INFO, $beforeAuthorizeEvent->getUri());
    }

    public function beforeGetRequestToken(BeforeGetRequestToken $beforeGetRequestTokenEvent)
    {
    }

    public function beforeGetAccessToken(BeforeGetAccessToken $beforeGetAccessTokenEvent)
    {
    }

    public function onComplete(CompleteEvent $event)
    {
        $this->logger->log(
            LogLevel::INFO,
            $this->formatter->format(
                $event->getRequest(),
                $event->getResponse()
            ),
            [
                'request' => $event->getRequest(),
                'response' => $event->getResponse()
            ]
        );
    }

    public function onError(ErrorEvent $event)
    {
        $ex = $event->getException();
        $this->logger->log(
            LogLevel::CRITICAL,
            $this->formatter->format(
                $event->getRequest(),
                $event->getResponse(),
                $ex
            ),
            [
                'request' => $event->getRequest(),
                'response' => $event->getResponse(),
                'exception' => $ex
            ]
        );
    }
}
