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

                // Debug: Log that we're trying to register handler
                error_log('[Log Notifier] Attempting to register Monolog handler');

                if ($logger && method_exists($logger, 'getLogger')) {
                    $monologLogger = $logger->getLogger();
                    $app = $this->app;

                    error_log('[Log Notifier] Got Monolog logger instance');

                    // Create handler
                    $handlerObj = new class($app) extends \Monolog\Handler\AbstractProcessingHandler {
                        private $app;

                        public function __construct($app)
                        {
                            $this->app = $app;
                            parent::__construct();
                            // Set handler to capture DEBUG level and above (all logs)
                            $this->setLevel(\Monolog\Level::Debug);
                        }

                        protected function write(\Monolog\LogRecord $record): void
                        {
                            // Debug: Always log handler invocation
                            error_log('[Log Notifier Handler] Fired for level: ' . $record->getLevelName() . ', message: ' . substr($record->getMessage(), 0, 50));

                            if (! config('log-notifier.enabled', true)) {
                                error_log('[Log Notifier Handler] Log Notifier disabled');
                                return;
                            }

                            $levels = config('log-notifier.levels', ['error', 'critical', 'alert', 'emergency']);
                            $logLevel = strtolower($record->getLevelName());

                            error_log('[Log Notifier Handler] Log level: ' . $logLevel . ', monitored levels: ' . json_encode($levels));

                            if (! in_array($logLevel, $levels)) {
                                error_log('[Log Notifier Handler] Level ' . $logLevel . ' not monitored');
                                return;
                            }

                            try {
                                error_log('[Log Notifier Handler] Storing error in repository');
                                $repository = $this->app->make(\Irabbi360\LaravelLogNotifier\Services\ErrorRepository::class);
                                $result = $repository->store([
                                    'level' => $logLevel,
                                    'message' => $record->getMessage(),
                                    'trace' => null,
                                    'file' => 'Log',
                                    'line' => 0,
                                    'context' => $record->getContext() ?? [],
                                ]);
                                error_log('[Log Notifier Handler] Stored successfully: ' . ($result ? 'true' : 'false'));
                            } catch (\Exception $e) {
                                error_log('[Log Notifier Handler] Error storing: ' . $e->getMessage());
                            }
                        }
                    };

                    $monologLogger->pushHandler($handlerObj);
                    error_log('[Log Notifier] Monolog handler registered successfully');
                } else {
                    error_log('[Log Notifier] Logger does not have getLogger method');
                }
            } catch (\Exception $e) {
                error_log('[Log Notifier] Exception registering Monolog handler: ' . $e->getMessage());
            }
        }
    }
}
