<?php

namespace Irabbi360\LaravelLogNotifier\Commands;

use Illuminate\Console\Command;
use Irabbi360\LaravelLogNotifier\Services\LogWatcher;

class WatchLogsCommand extends Command
{
    protected $signature = 'log-notifier:watch 
                            {--once : Run once instead of continuously}
                            {--interval= : Override check interval in seconds}';

    protected $description = 'Watch Laravel logs for errors and send notifications';

    public function handle(LogWatcher $watcher): int
    {
        if (!config('log-notifier.enabled', true)) {
            $this->error('Log Notifier is disabled. Enable it in config/log-notifier.php');
            return self::FAILURE;
        }

        $interval = $this->option('interval') ?? config('log-notifier.check_interval', 10);
        $runOnce = $this->option('once');

        $this->info('🔍 Log Notifier watching for errors...');
        $this->line('   Log file: ' . config('log-notifier.log_path'));
        $this->line('   Levels: ' . implode(', ', config('log-notifier.levels', [])));
        $this->line('   Interval: ' . $interval . ' seconds');
        $this->newLine();

        if ($runOnce) {
            return $this->runOnce($watcher);
        }

        return $this->runContinuously($watcher, (int) $interval);
    }

    protected function runOnce(LogWatcher $watcher): int
    {
        $errors = $watcher->watch();

        if (empty($errors)) {
            $this->info('No new errors found.');
        } else {
            $this->warn('Found ' . count($errors) . ' new error(s):');
            foreach ($errors as $error) {
                $this->line("  [{$error->level}] {$error->excerpt}");
            }
        }

        return self::SUCCESS;
    }

    protected function runContinuously(LogWatcher $watcher, int $interval): int
    {
        $this->info('Press Ctrl+C to stop watching.');
        $this->newLine();

        while (true) {
            $errors = $watcher->watch();

            if (!empty($errors)) {
                $timestamp = now()->format('Y-m-d H:i:s');
                $this->warn("[{$timestamp}] Found " . count($errors) . ' new error(s)');
                
                foreach ($errors as $error) {
                    $this->line("  🚨 [{$error->level}] {$error->excerpt}");
                }
            }

            sleep($interval);
        }

        return self::SUCCESS;
    }
}
