<?php

namespace Native\Mobile\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Native\Mobile\Plugins\Plugin;
use Native\Mobile\Plugins\PluginRegistry;

use function Laravel\Prompts\confirm;

class PluginRegisterCommand extends Command
{
    protected $signature = 'native:plugin:register
                            {plugin : The plugin package name (e.g., vendor/plugin-name)}
                            {--remove : Remove the plugin instead of adding it}
                            {--force : Skip conflict warnings}';

    protected $description = 'Register a NativePHP plugin in NativeServiceProvider';

    public function __construct(
        protected Filesystem $files,
        protected PluginRegistry $registry
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $packageName = $this->argument('plugin');
        $remove = $this->option('remove');

        $providerPath = app_path('Providers/NativeServiceProvider.php');

        if (! $this->files->exists($providerPath)) {
            $this->components->error('NativeServiceProvider not found.');
            $this->components->info('Run: php artisan vendor:publish --tag=nativephp-plugins-provider');

            return self::FAILURE;
        }

        // Look up the service provider class from the package
        $serviceProvider = $this->getServiceProviderForPackage($packageName);

        if (! $serviceProvider) {
            $this->components->error("Could not find service provider for package '{$packageName}'.");
            $this->components->info('Make sure the package is installed and has a service provider defined in composer.json.');

            return self::FAILURE;
        }

        $content = $this->files->get($providerPath);

        if ($remove) {
            return $this->removePlugin($serviceProvider, $packageName, $content, $providerPath);
        }

        return $this->addPlugin($serviceProvider, $packageName, $content, $providerPath);
    }

    /**
     * Get the service provider class for a package from installed.json.
     */
    protected function getServiceProviderForPackage(string $packageName): ?string
    {
        $installedPath = base_path('vendor/composer/installed.json');

        if (! $this->files->exists($installedPath)) {
            return null;
        }

        $installed = json_decode($this->files->get($installedPath), true);
        $packages = $installed['packages'] ?? $installed;

        foreach ($packages as $package) {
            if (($package['name'] ?? '') === $packageName) {
                return $package['extra']['laravel']['providers'][0] ?? null;
            }
        }

        return null;
    }

    protected function addPlugin(string $serviceProvider, string $packageName, string $content, string $providerPath): int
    {
        // Check if plugin already registered
        if ($this->pluginExists($serviceProvider, $content)) {
            $this->components->info("Plugin '{$packageName}' is already registered.");

            return self::SUCCESS;
        }

        // Check for conflicts with existing plugins
        $newPlugin = $this->loadPlugin($packageName);
        if ($newPlugin) {
            $conflicts = $this->detectConflicts($newPlugin);
            if (! empty($conflicts)) {
                $this->components->warn('Potential conflicts detected:');
                foreach ($conflicts as $conflict) {
                    $this->line("  - {$conflict}");
                }
                $this->newLine();

                if (! $this->option('force')) {
                    if (! confirm('Continue anyway?', false)) {
                        $this->components->info('Registration cancelled.');

                        return self::FAILURE;
                    }
                }
            }
        }

        // Find the plugins() method and add the plugin
        $pattern = '/(public\s+function\s+plugins\s*\(\s*\)\s*:\s*array\s*\{\s*return\s*\[)([^\]]*?)(\s*\];)/s';

        if (! preg_match($pattern, $content)) {
            $this->components->error('Could not find plugins() method in NativeServiceProvider.');
            $this->components->info("Please manually add \\{$serviceProvider}::class to the plugins() array.");

            return self::FAILURE;
        }

        $content = preg_replace_callback($pattern, function ($matches) use ($serviceProvider) {
            $before = $matches[1];
            $existing = $matches[2];
            $after = $matches[3];

            // Determine proper formatting
            $trimmedExisting = trim($existing);

            // Check for old or new placeholder comment
            $isPlaceholder = empty($trimmedExisting)
                || str_contains($trimmedExisting, '// \'vendor/example-plugin\'')
                || str_contains($trimmedExisting, '// \\Vendor\\ExamplePlugin');

            if ($isPlaceholder) {
                // Empty or only has placeholder comment - replace with plugin
                $newContent = "\n            \\{$serviceProvider}::class,\n        ";
            } else {
                // Has existing plugins - append
                $newContent = rtrim($existing, " \t\n")."\n            \\{$serviceProvider}::class,\n        ";
            }

            return $before.$newContent.$after;
        }, $content);

        $this->files->put($providerPath, $content);

        $this->components->success("Plugin '{$packageName}' has been registered.");
        $this->line("  Added: \\{$serviceProvider}::class");

        return self::SUCCESS;
    }

    protected function removePlugin(string $serviceProvider, string $packageName, string $content, string $providerPath): int
    {
        if (! $this->pluginExists($serviceProvider, $content)) {
            $this->components->info("Plugin '{$packageName}' is not registered.");

            return self::SUCCESS;
        }

        // Remove the plugin line (handles both ::class format)
        $escapedProvider = preg_quote($serviceProvider, '/');
        $pattern = "/\s*\\\\?{$escapedProvider}::class,?\n?/";

        $content = preg_replace($pattern, "\n", $content);

        // Clean up any double newlines in the array
        $content = preg_replace('/(\[\s*)\n\n+/', "$1\n", $content);

        $this->files->put($providerPath, $content);

        $this->components->success("Plugin '{$packageName}' has been removed.");

        return self::SUCCESS;
    }

    protected function pluginExists(string $serviceProvider, string $content): bool
    {
        $escapedProvider = preg_quote($serviceProvider, '#');

        // Match ServiceProvider::class format
        return (bool) preg_match('#\\\\?'.$escapedProvider.'::class#', $content);
    }

    /**
     * Load a plugin by package name from all installed plugins.
     */
    protected function loadPlugin(string $packageName): ?Plugin
    {
        return $this->registry->allInstalled()
            ->first(fn (Plugin $p) => $p->name === $packageName);
    }

    /**
     * Detect conflicts between the new plugin and existing registered plugins.
     *
     * @return array<string> Conflict messages
     */
    protected function detectConflicts(Plugin $newPlugin): array
    {
        $conflicts = [];
        $existingPlugins = $this->registry->all();

        // Check namespace collision
        foreach ($existingPlugins as $existing) {
            if ($existing->getNamespace() === $newPlugin->getNamespace()) {
                $conflicts[] = "Namespace '{$newPlugin->getNamespace()}' conflicts with '{$existing->name}'";
            }
        }

        // Check bridge function name collision
        $existingFunctions = $this->registry->bridgeFunctions();
        $existingNames = array_column($existingFunctions, 'name');

        foreach ($newPlugin->getBridgeFunctions() as $func) {
            if (in_array($func['name'], $existingNames, true)) {
                $conflicts[] = "Bridge function '{$func['name']}' is already registered by another plugin";
            }
        }

        return $conflicts;
    }
}
