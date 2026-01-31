<?php

namespace Irabbi360\LaravelLogNotifier\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void handles(\Laravel\Framework\Foundation\Configuration\Exceptions $exceptions)
 */
class LogWatcher extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'log-watcher';
    }
}
