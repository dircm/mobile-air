<?php

namespace Native\Mobile\Commands;

use Illuminate\Console\Command;
use Native\Mobile\Traits\ManagesViteDevServer;
use Native\Mobile\Traits\ManagesWatchman;
use Native\Mobile\Traits\RunsIos;
use Native\Mobile\Traits\WatchesAndroid;
use Native\Mobile\Traits\WatchesIos;

use function Laravel\Prompts\select;

class WatchCommand extends Command
{
    use ManagesViteDevServer, ManagesWatchman, RunsIos, WatchesAndroid, WatchesIos;

    protected $signature = 'native:watch
        {platform? : ios|android}
        {target? : The device/simulator UDID to watch}';

    protected $description = 'Watch for file changes and sync to running mobile app';

    public function handle(): int
    {
        if (! $this->checkWatchmanDependencies()) {
            return self::FAILURE;
        }

        $platform = $this->argument('platform');

        if (! $platform) {
            $platform = select(
                label: 'Select platform to watch',
                options: [
                    'ios' => 'iOS',
                    'android' => 'Android',
                ]
            );
        }

        $targetUdid = $this->argument('target');

        if ($platform === 'ios') {
            $this->startIosHotReload($targetUdid);
        } elseif ($platform === 'android') {
            $this->startAndroidHotReload();
        } else {
            $this->error('Invalid platform. Use: ios or android');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
