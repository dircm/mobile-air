<?php

namespace Tests\Unit\Templates;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MainActivityValidationTest extends TestCase
{
    public function test_main_activity_uses_dynamic_resource_resolution()
    {
        $mainActivityPath = __DIR__.'/../../../resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/MainActivity.kt';

        $this->assertFileExists($mainActivityPath, 'MainActivity.kt template should exist');

        $activityContent = File::get($mainActivityPath);

        // Ensure MainActivity doesn't use compile-time R.drawable.splash references
        // This would cause compilation failures when splash drawable doesn't exist
        $this->assertStringNotContainsString(
            'R.drawable.splash',
            $activityContent,
            'MainActivity should not contain compile-time R.drawable.splash references as they cause compilation errors when splash resource does not exist'
        );

        // Verify it uses dynamic resource resolution instead
        $this->assertStringContainsString(
            'getIdentifier("splash", "drawable", packageName)',
            $activityContent,
            'MainActivity should use dynamic resource resolution to avoid compile-time dependencies'
        );
    }

    public function test_main_activity_has_proper_fallback_logic()
    {
        $mainActivityPath = __DIR__.'/../../../resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/MainActivity.kt';
        $activityContent = File::get($mainActivityPath);

        // Verify proper fallback logic exists
        $this->assertStringContainsString('splashResourceId != 0', $activityContent);
        $this->assertStringContainsString('splashImageView.visibility = View.VISIBLE', $activityContent);
        $this->assertStringContainsString('splashTextView.visibility = View.GONE', $activityContent);
        $this->assertStringContainsString('splashImageView.visibility = View.GONE', $activityContent);
        $this->assertStringContainsString('splashTextView.visibility = View.VISIBLE', $activityContent);
    }

    public function test_main_activity_has_proper_logging()
    {
        $mainActivityPath = __DIR__.'/../../../resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/MainActivity.kt';
        $activityContent = File::get($mainActivityPath);

        // Verify proper logging for debugging
        $this->assertStringContainsString('Log.d("SplashScreen"', $activityContent);
        $this->assertStringContainsString('Using custom splash image', $activityContent);
        $this->assertStringContainsString('Using default splash text', $activityContent);
    }

    public function test_main_activity_imports_are_correct()
    {
        $mainActivityPath = __DIR__.'/../../../resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/MainActivity.kt';
        $activityContent = File::get($mainActivityPath);

        // Verify required imports for splash functionality
        $this->assertStringContainsString('import androidx.core.content.ContextCompat', $activityContent);
        $this->assertStringContainsString('import com.nativephp.mobile.R', $activityContent);
        $this->assertStringContainsString('import android.util.Log', $activityContent);
        $this->assertStringContainsString('import android.view.View', $activityContent);
    }

    public function test_main_activity_kotlin_syntax_is_valid()
    {
        $mainActivityPath = __DIR__.'/../../../resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/MainActivity.kt';
        $activityContent = File::get($mainActivityPath);

        // Basic syntax validation
        $this->assertStringContainsString('class MainActivity', $activityContent);
        $this->assertStringContainsString('private fun setupSplashScreen()', $activityContent);

        // Check that file ends properly (should end with a closing brace)
        $this->assertStringEndsWith('}', trim($activityContent));

        // Verify proper class structure
        $this->assertStringContainsString(': AppCompatActivity(), WebViewProvider', $activityContent);
    }

    public function test_main_activity_handles_error_cases()
    {
        $mainActivityPath = __DIR__.'/../../../resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/MainActivity.kt';
        $activityContent = File::get($mainActivityPath);

        // Verify error handling exists
        $this->assertStringContainsString('try {', $activityContent);
        $this->assertStringContainsString('} catch', $activityContent);
        $this->assertStringContainsString('Log.w("SplashScreen"', $activityContent);
    }
}
