<?php

namespace Irabbi360\LaravelLogNotifier;

class LaravelLogNotifier
{
    /**
     * Render the global notification component.
     */
    public function notification(): string
    {
        return view('log-notifier::notification')->render();
    }
}
