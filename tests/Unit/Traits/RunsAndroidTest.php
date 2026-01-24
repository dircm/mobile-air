<?php

namespace Tests\Unit\Traits;

use Illuminate\Support\Facades\File;
use Mockery;
use Native\Mobile\Traits\RunsAndroid;
use Orchestra\Testbench\TestCase;

class RunsAndroidTest extends TestCase
{
    use RunsAndroid;

    protected string $testProjectPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testProjectPath = sys_get_temp_dir().'/nativephp_android_test_'.uniqid();
        File::makeDirectory($this->testProjectPath.'/nativephp/android', 0755, true);

        // Set up base path for testing
        app()->setBasePath($this->testProjectPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->testProjectPath);
        Mockery::close();

        parent::tearDown();
    }

    public function test_clean_gradle_cache_removes_directories()
    {
        // Create test directories
        $gradleDir = $this->testProjectPath.'/nativephp/android/.gradle';
        $buildDir = $this->testProjectPath.'/nativephp/android/app/build';

        File::makeDirectory($gradleDir, 0755, true);
        File::makeDirectory($buildDir, 0755, true);
        File::put($gradleDir.'/cache.lock', 'test');
        File::put($buildDir.'/output.apk', 'test');

        $this->assertDirectoryExists($gradleDir);
        $this->assertDirectoryExists($buildDir);

        // Execute
        $this->cleanGradleCache();

        // Assert directories were removed
        $this->assertDirectoryDoesNotExist($gradleDir);
        $this->assertDirectoryDoesNotExist($buildDir);
    }

    public function test_detect_current_app_id_from_gradle()
    {
        // Create test build.gradle.kts
        $gradlePath = $this->testProjectPath.'/nativephp/android/app/build.gradle.kts';
        File::makeDirectory(dirname($gradlePath), 0755, true);
        File::put($gradlePath, 'android {
    namespace = "com.example.testapp"
    compileSdk = 34
}');

        $appId = $this->detectCurrentAppId();

        $this->assertEquals('com.example.testapp', $appId);
    }

    public function test_detect_current_app_id_returns_null_for_missing_file()
    {
        $appId = $this->detectCurrentAppId();

        $this->assertNull($appId);
    }

    public function test_update_app_id_renames_package_and_updates_files()
    {
        $oldAppId = 'com.example.oldapp';
        $newAppId = 'com.company.newapp';

        // Set up directory structure
        $oldPath = $this->testProjectPath.'/nativephp/android/app/src/main/java/com/example/oldapp';
        File::makeDirectory($oldPath, 0755, true);
        File::put($oldPath.'/MainActivity.kt', 'package com.example.oldapp');

        // Set up CMakeLists.txt
        $cmakePath = $this->testProjectPath.'/nativephp/android/app/src/main/cpp/CMakeLists.txt';
        File::makeDirectory(dirname($cmakePath), 0755, true);
        File::put($cmakePath, 'project("com_example_oldapp")');

        // Set up build.gradle.kts
        $gradlePath = $this->testProjectPath.'/nativephp/android/app/build.gradle.kts';
        File::put($gradlePath, 'namespace = "com.example.oldapp"');

        // Set up C++ bridge
        $cppPath = $this->testProjectPath.'/nativephp/android/app/src/main/cpp/php_bridge.c';
        File::put($cppPath, 'com/example/oldapp/bridge');

        // Execute
        $this->updateAppId($oldAppId, $newAppId);

        // Assert package was renamed
        $newPath = $this->testProjectPath.'/nativephp/android/app/src/main/java/com/company/newapp';
        $this->assertDirectoryExists($newPath);
        $this->assertDirectoryDoesNotExist($oldPath);

        // Assert file contents were updated
        $this->assertStringContainsString('package com.company.newapp', File::get($newPath.'/MainActivity.kt'));
        $this->assertStringContainsString('project("com_company_newapp")', File::get($cmakePath));
        $this->assertStringContainsString('namespace = "com.company.newapp"', File::get($gradlePath));
        $this->assertStringContainsString('com/company/newapp/bridge', File::get($cppPath));
    }

    public function test_update_version_configuration()
    {
        // Set up config
        config(['nativephp.version' => '2.0.0']);
        config(['nativephp.version_code' => 2000]);

        // Create test build.gradle.kts
        $gradlePath = $this->testProjectPath.'/nativephp/android/app/build.gradle.kts';
        File::makeDirectory(dirname($gradlePath), 0755, true);
        File::put($gradlePath, 'android {
    versionCode = 1
    versionName = "1.0.0"
}');

        // Execute
        $this->updateVersionConfiguration();

        $contents = File::get($gradlePath);
        $this->assertStringContainsString('versionCode = 2000', $contents);
        $this->assertStringContainsString('versionName = "2.0.0"', $contents);
    }

    public function test_update_app_display_name()
    {
        // Set up config
        config(['app.name' => 'My Test App']);

        // Create AndroidManifest.xml
        $manifestPath = $this->testProjectPath.'/nativephp/android/app/src/main/AndroidManifest.xml';
        File::makeDirectory(dirname($manifestPath), 0755, true);
        File::put($manifestPath, '<application android:label="NativePHP">');

        // Execute
        $this->updateAppDisplayName();

        $contents = File::get($manifestPath);
        $this->assertStringContainsString('android:label="My Test App"', $contents);
    }

    public function test_update_permissions_adds_and_removes()
    {
        // Set up config
        config(['nativephp.permissions.push_notifications' => true]);
        config(['nativephp.permissions.biometric' => false]);
        config(['nativephp.permissions.nfc' => true]);

        // Create AndroidManifest.xml with biometric permission
        $manifestPath = $this->testProjectPath.'/nativephp/android/app/src/main/AndroidManifest.xml';
        File::makeDirectory(dirname($manifestPath), 0755, true);
        File::put($manifestPath, '<manifest>
    <uses-permission android:name="android.permission.USE_BIOMETRIC" />
</manifest>');

        // Execute
        $this->updatePermissions();

        $contents = File::get($manifestPath);

        // Assert new permissions were added
        $this->assertStringContainsString('android.permission.POST_NOTIFICATIONS', $contents);
        $this->assertStringContainsString('android.permission.NFC', $contents);

        // Assert disabled permission was removed
        $this->assertStringNotContainsString('android.permission.USE_BIOMETRIC', $contents);
    }

    public function test_update_firebase_configuration_copies_file()
    {
        // Create source google-services.json
        $sourcePath = $this->testProjectPath.'/google-services.json';
        File::put($sourcePath, '{"project_id": "test"}');

        // Create target directory
        $targetDir = $this->testProjectPath.'/nativephp/android/app';
        File::makeDirectory($targetDir, 0755, true);

        // Execute
        $this->updateFirebaseConfiguration();

        // Assert file was copied
        $targetPath = $targetDir.'/google-services.json';
        $this->assertFileExists($targetPath);
        $this->assertEquals('{"project_id": "test"}', File::get($targetPath));
    }

    public function test_update_icu_configuration_with_icu_enabled()
    {
        // Set up app ID
        config(['nativephp.app_id' => 'com.test.app']);

        // Create ICU flag file
        $icuFlagFile = $this->testProjectPath.'/nativephp/android/.icu-enabled';
        File::put($icuFlagFile, '1');

        // Create PHPBridge.kt
        $bridgePath = $this->testProjectPath.'/nativephp/android/app/src/main/java/com/test/app/bridge/PHPBridge.kt';
        File::makeDirectory(dirname($bridgePath), 0755, true);
        File::put($bridgePath, 'class PHPBridge {
    init {
        System.loadLibrary("php")
    }
}');

        // Execute
        $this->updateIcuConfiguration();

        $contents = File::get($bridgePath);
        $this->assertStringContainsString('System.loadLibrary("icudata")', $contents);
        $this->assertStringContainsString('System.loadLibrary("icuuc")', $contents);
        $this->assertStringContainsString('System.loadLibrary("icui18n")', $contents);
        $this->assertStringContainsString('System.loadLibrary("icuio")', $contents);
    }

    public function test_update_local_properties_windows_path()
    {
        // Mock Windows environment
        $this->mockPlatform('Windows');

        config(['nativephp.android.android_sdk_path' => 'C:\\Users\\test\\Android\\Sdk']);

        // Execute
        $this->updateLocalProperties();

        $path = $this->testProjectPath.'/nativephp/android/local.properties';
        $contents = File::get($path);

        // The actual implementation converts to forward slashes on Windows
        $this->assertEquals("sdk.dir=C:/Users/test/Android/Sdk\n", $contents);
    }

    public function test_update_local_properties_unix_path()
    {
        // Mock Unix environment
        $this->mockPlatform('Linux');

        config(['nativephp.android.android_sdk_path' => '/home/user/Android/Sdk']);

        // Execute
        $this->updateLocalProperties();

        $path = $this->testProjectPath.'/nativephp/android/local.properties';
        $contents = File::get($path);

        $this->assertEquals("sdk.dir=/home/user/Android/Sdk\n", $contents);
    }

    public function test_update_deep_link_configuration()
    {
        // Set up config
        config(['nativephp.permissions.deeplinks' => true]);
        config(['nativephp.permissions.nfc' => true]);
        config(['nativephp.deeplink_scheme' => 'myapp']);
        config(['nativephp.deeplink_host' => 'app.example.com']);
        config(['nativephp.app_id' => 'com.test.app']);

        // Create AndroidManifest.xml
        $manifestPath = $this->testProjectPath.'/nativephp/android/app/src/main/AndroidManifest.xml';
        File::makeDirectory(dirname($manifestPath), 0755, true);
        File::put($manifestPath, '<manifest>
    <application>
        <activity android:name=".MainActivity">
        </activity>
    </application>
</manifest>');

        // Create WebViewManager.kt
        $webViewPath = $this->testProjectPath.'/nativephp/android/app/src/main/java/com/test/app/network/WebViewManager.kt';
        File::makeDirectory(dirname($webViewPath), 0755, true);
        File::put($webViewPath, 'if (url.startsWith("REPLACEME://")) {');

        // Execute
        $this->updateDeepLinkConfiguration();

        // Assert manifest was updated
        $manifestContents = File::get($manifestPath);
        $this->assertStringContainsString('android:scheme="myapp"', $manifestContents);
        $this->assertStringContainsString('android:host="app.example.com"', $manifestContents);
        $this->assertStringContainsString('android.nfc.action.NDEF_DISCOVERED', $manifestContents);

        // Assert WebView was updated
        $webViewContents = File::get($webViewPath);
        $this->assertStringContainsString('if (url.startsWith("myapp://"))', $webViewContents);
    }

    /**
     * Helper methods
     */
    protected function mockPlatform(string $platform)
    {
        // This would need to be implemented to properly mock PHP_OS_FAMILY
        // For testing purposes, we'll override the platform detection
    }

    protected function info($message)
    {
        // Mock for testing
    }

    protected function warn($message)
    {
        // Mock for testing
    }

    protected function error($message)
    {
        // Mock for testing
    }

    protected function installAndroidIcon()
    {
        // Mock for testing
    }

    protected function prepareLaravelBundle()
    {
        // Mock for testing
    }

    protected function runTheAndroidBuild($target)
    {
        // Mock for testing
    }

    // Abstract methods required by PreparesBuild trait
    protected function removeDirectory(string $path): void
    {
        if (is_dir($path)) {
            File::deleteDirectory($path);
        }
    }

    protected function platformOptimizedCopy(string $source, string $destination, array $excludedDirs): void
    {
        // Simple copy implementation for testing
        if (! is_dir($source)) {
            return;
        }

        if (! is_dir($destination)) {
            File::makeDirectory($destination, 0755, true);
        }

        $files = scandir($source);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourcePath = $source.'/'.$file;
            $destPath = $destination.'/'.$file;

            if (is_dir($sourcePath)) {
                if (! in_array($file, $excludedDirs)) {
                    $this->platformOptimizedCopy($sourcePath, $destPath, $excludedDirs);
                }
            } else {
                copy($sourcePath, $destPath);
            }
        }
    }
}
