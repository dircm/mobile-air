<?php

namespace Native\Mobile\Commands;

use Illuminate\Console\Command;
use Native\Mobile\Traits\LaunchesAndroidEmulator;

class LaunchEmulatorCommand extends Command
{
    use LaunchesAndroidEmulator;

    protected $signature = 'native:emulator {os}';

    protected $description = 'List and launch an emulator';

    public function handle(): void
    {
        match ($this->argument('os')) {
            'android' => $this->startAndroid(),
            'ios' => $this->startAndroid(),
            default => throw new \Exception('Invalid OS type.')
        };
    }
}
