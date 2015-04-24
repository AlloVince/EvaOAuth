<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaOAuth\Events;

use GuzzleHttp\Event\Emitter;

/**
 * Class EventsManager
 * @package Eva\EvaOAuth\Events
 */
class EventsManager
{
    /**
     * @var Emitter
     */
    protected static $emitter;

    /**
     * @return Emitter
     */
    public static function getEmitter()
    {
        return self::$emitter ?: self::$emitter = new Emitter();
    }
}
