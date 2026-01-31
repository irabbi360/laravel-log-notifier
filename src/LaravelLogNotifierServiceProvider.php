<?php

namespace Irabbi360\LaravelLogNotifier;

use Illuminate\Log\Events\LogWritten;
use Irabbi360\LaravelLogNotifier\Commands\ClearErrorsCommand;
use Irabbi360\LaravelLogNotifier\Commands\WatchLogsCommand;
use Irabbi360\LaravelLogNotifier\Listeners\ProcessNewLogEntry;
use Irabbi360\LaravelLogNotifier\Services\ErrorParser;
use Irabbi360\LaravelLogNotifier\Services\ErrorRepository;
use Irabbi360\LaravelLogNotifier\Services\LogWatcher;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelLogNotifierServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('log-notifier')
            ->hasConfigFile()
            ->hasViews()
            ->hasRoute('web')
            ->hasMigrations([
                'create_log_notifier_errors_table',
            ])
            ->hasCommands([
                WatchLogsCommand::class,
                ClearErrorsCommand::class,
            ])
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->copyAndRegisterServiceProviderInApp()
                    ->askToStarRepoOnGitHub('irabbi360/laravel-log-notifier');
            });
    }

    public function packageRegistered(): void
    {
        // Register services as singletons
        $this->app->singleton(ErrorParser::class, function ($app) {
            return new ErrorParser;
        });

        $this->app->singleton(ErrorRepository::class, function ($app) {
            return new ErrorRepository;
        });

        $this->app->singleton(LogWatcher::class, function ($app) {
            return new LogWatcher(
                $app->make(ErrorParser::class),
                $app->make(ErrorRepository::class)
            );
        });

        // Register the main facade
        $this->app->singleton('log-notifier', function ($app) {
            return new LaravelLogNotifier(
                $app->make(LogWatcher::class),
                $app->make(ErrorRepository::class)
            );
        });
    }

    public function packageBooted(): void
    {
        // Register facade alias for easy access
        if (! class_exists('LogNotifier')) {
            class_alias(
                \Irabbi360\LaravelLogNotifier\Facades\LaravelLogNotifier::class,
                'LogNotifier'
            );
        }

        // Register event listener for LogWritten event
        if (config('log-notifier.enabled', true) && config('log-notifier.use_event_listener', true)) {
            $this->app['events']->listen(
                LogWritten::class,
                function (LogWritten $event) {
                    $listener = $this->app->make(ProcessNewLogEntry::class);
                    $listener->handle($event);
                }
            );
        }

        // Add Monolog handler for guaranteed capture of all logs
        if (config('log-notifier.enabled', true)) {
            try {
                $logger = $this->app->make('log');
                
                if ($logger && method_exists($logger, 'getLogger')) {
                    $monologLogger = $logger->getLogger();
                    $app = $this->app;
                    
                    // Create handler - direct instantiation to avoid variable scope issues
                    $handler = new \Monolog\Handler\AbstractProcessingHandler();
                    
                    // Override write method
                    $handlerObj = new class($app) extends \Monolog\Handler\AbstractProcessingHandler {
                        private $app;
                        
                        public function __construct($app)
                        {
                            $this->app = $app;
                            parent::__construct();
                        }
                        
                        protected function write(\Monolog\LogRecord $record): void
                        {
                            if (! config('log-notifier.enabled', true)) {
                                return;
                            }

                            // Get configured levels to monitor
                            $levels = config('log-notifier.levels', ['error', 'critical', 'alert', 'emergency']);
                            $logLevel = strtolower($record->getLevelName());
                            
                            // Check if this level should be monitored
                            if (! in_array($logLevel, $levels)) {
                                return;
                            }

                            try {
                                // Store directly
                                $repository = $this->app->make(\Irabbi360\LaravelLogNotifier\Services\ErrorRepository::class);
                                $repository->store([
                                    'level' => $logLevel,
                                    'message' => $record->getMessage(),
                                    'trace' => null,
                                    'file' => 'Log',
                                    'line' => 0,
                                    'context' => $record->getContext() ?? [],
                                ]);
                            } catch (\Exception $e) {
                                // Silent fail
                            }
                        }
                    };
                    
                    $monologLogger->pushHandler($handlerObj);
                }
            } catch (\Exception $e) {
                // Silent fail if Monolog not available
            }
        }
    }
}
