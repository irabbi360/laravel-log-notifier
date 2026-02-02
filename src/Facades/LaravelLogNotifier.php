<?php

namespace Irabbi360\LaravelLogNotifier\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string notification()
 *
 * @see \Irabbi360\LaravelLogNotifier\LaravelLogNotifier
 */
class LaravelLogNotifier extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'log-notifier';
    }
}
