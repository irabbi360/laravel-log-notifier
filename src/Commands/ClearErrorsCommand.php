<?php

namespace Irabbi360\LaravelLogNotifier\Commands;

use Illuminate\Console\Command;
use Irabbi360\LaravelLogNotifier\Services\ErrorRepository;

class ClearErrorsCommand extends Command
{
    protected $signature = 'log-notifier:clear 
                            {--all : Clear all errors regardless of retention policy}
                            {--resolved : Clear only resolved errors}
                            {--force : Skip confirmation}';

    protected $description = 'Clear stored error logs';

    public function handle(ErrorRepository $repository): int
    {
        $clearAll = $this->option('all');
        $resolvedOnly = $this->option('resolved');
        $force = $this->option('force');

        if ($clearAll) {
            if (!$force && !$this->confirm('Are you sure you want to delete ALL error logs?')) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }

            $deleted = $repository->clearAll();
            $this->info("✅ Cleared all error logs.");
            return self::SUCCESS;
        }

        if ($resolvedOnly) {
            $deleted = \Irabbi360\LaravelLogNotifier\Models\LogError::resolved()->delete();
            $this->info("✅ Cleared {$deleted} resolved error(s).");
            return self::SUCCESS;
        }

        // Default: clear based on retention policy
        $retentionDays = config('log-notifier.retention_days', 30);
        
        if ($retentionDays <= 0) {
            $this->warn('Retention policy is disabled (set to 0). Use --all to clear all errors.');
            return self::FAILURE;
        }

        $deleted = $repository->clearOldErrors();
        $this->info("✅ Cleared {$deleted} error(s) older than {$retentionDays} days.");

        return self::SUCCESS;
    }
}
