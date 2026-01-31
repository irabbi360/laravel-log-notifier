<?php

namespace Irabbi360\LaravelLogNotifier\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array watch()
 * @method static \Illuminate\Pagination\LengthAwarePaginator getErrors(array $filters = [], int $perPage = 20)
 * @method static \Irabbi360\LaravelLogNotifier\Models\LogError|null getError(int $id)
 * @method static array getStatistics(int $days = 7)
 * @method static bool resolve(int $id, ?int $userId = null, ?string $note = null)
 * @method static bool unresolve(int $id)
 * @method static bool delete(int $id)
 * @method static int clearOldErrors()
 * @method static int clearAll()
 * @method static void resetPosition()
 * @method static \Irabbi360\LaravelLogNotifier\Services\LogWatcher watcher()
 * @method static \Irabbi360\LaravelLogNotifier\Services\ErrorRepository repository()
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
