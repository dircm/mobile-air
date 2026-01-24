<?php

namespace Native\Mobile\Commands;

use Illuminate\Console\Command;
use Native\Mobile\Traits\OpensAndroidProject;
use Native\Mobile\Traits\OpensIosProject;

use function Laravel\Prompts\select;

class OpenProjectCommand extends Command
{
    use OpensAndroidProject, OpensIosProject;

    protected $signature = 'native:open {os? : ios|android}';

    protected $description = 'Open the Android Studio or Xcode project';

    public function handle(): void
    {
        $os = $this->argument('os');

        if (! $os) {
            // Check which platform folders exist
            $iosExists = is_dir(base_path('nativephp/ios'));
            $androidExists = is_dir(base_path('nativephp/android'));

            if ($iosExists && ! $androidExists) {
                $this->info('🍎 Only iOS project found, opening automatically...');
                $os = 'ios';
            } elseif ($androidExists && ! $iosExists) {
                $this->info('🤖 Only Android project found, opening automatically...');
                $os = 'android';
            } elseif ($iosExists && $androidExists) {
                $os = select(
                    label: '🧭 Which platform would you like to open?',
                    options: [
                        'android' => '🤖 Android',
                        'ios' => '🍎 iOS',
                    ]
                );
            } else {
                $this->error('❌ No platform projects found. Run `php artisan native:install` first.');

                return;
            }
        }

        match ($os) {
            'android' => $this->openAndroidProject(),
            'ios' => $this->openIosProject(),
            default => throw new \Exception('Invalid OS type.')
        };
    }
}
