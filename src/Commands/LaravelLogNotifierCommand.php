<?php

namespace Irabbi360\LaravelLogNotifier\Commands;

use Illuminate\Console\Command;

class LaravelLogNotifierCommand extends Command
{
    public $signature = 'laravel-log-notifier';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
