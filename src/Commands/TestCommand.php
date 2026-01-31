<?php

namespace Irabbi360\LaravelLogNotifier\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestCommand extends Command
{
    protected $signature = 'log-notifier:test {--level=error : Log level to test}';

    protected $description = 'Test Log Notifier by generating a test error';

    public function handle(): int
    {
        $this->info('🧪 Testing Log Notifier...');
        $this->newLine();

        $level = $this->option('level') ?? 'error';
        $configuredLevels = config('log-notifier.levels', ['error', 'critical', 'alert', 'emergency']);

        // Check if enabled
        if (! config('log-notifier.enabled', true)) {
            $this->error('❌ Log Notifier is DISABLED. Enable it in config/log-notifier.php or set LOG_NOTIFIER_ENABLED=true');
            return self::FAILURE;
        }

        $this->info('✅ Log Notifier is enabled');
        $this->line('   Monitored levels: '.implode(', ', $configuredLevels));
        $this->line('   Test level: '.$level);

        if (! in_array($level, $configuredLevels)) {
            $this->warn('⚠️  Level "'.$level.'" is NOT monitored. Changing to "error"');
            $level = 'error';
        }

        $this->newLine();
        $this->info('📝 Generating test log...');

        // Generate test log
        $testMessage = 'Log Notifier Test Error - '.now()->toDateTimeString();
        
        switch ($level) {
            case 'emergency':
                Log::emergency($testMessage);
                break;
            case 'alert':
                Log::alert($testMessage);
                break;
            case 'critical':
                Log::critical($testMessage);
                break;
            case 'error':
            default:
                Log::error($testMessage);
                break;
        }

        $this->newLine();
        $this->info('✅ Test log generated!');
        $this->newLine();

        // Check database
        try {
            $errorCount = \DB::table('log_notifier_errors')->count();
            $this->info('📊 Total errors in database: '.$errorCount);
            
            $recentError = \DB::table('log_notifier_errors')
                ->latest('id')
                ->first();

            if ($recentError) {
                $this->info('📌 Latest error:');
                $this->line('   Level: '.$recentError->level);
                $this->line('   Message: '.$recentError->message);
                $this->line('   Created: '.$recentError->created_at);
            }
        } catch (\Exception $e) {
            $this->error('❌ Database error: '.$e->getMessage());
            $this->warn('   Make sure you ran: php artisan migrate');
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('🎉 Test complete! Check:');
        $this->line('   1. Dashboard: '.config('log-notifier.dashboard_route', '/log-notifier'));
        $this->line('   2. Browser console for SSE stream connection');
        $this->line('   3. Browser DevTools → Network → log-notifier/api/stream (should be 200)');

        return self::SUCCESS;
    }
}
