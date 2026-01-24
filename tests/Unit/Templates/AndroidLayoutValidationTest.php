<?php

namespace Tests\Unit\Templates;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AndroidLayoutValidationTest extends TestCase
{
    public function test_activity_main_layout_does_not_reference_missing_drawables()
    {
        // Use the actual package path, not the testbench vendor path
        $layoutPath = __DIR__.'/../../../resources/androidstudio/app/src/main/res/layout/activity_main.xml';

        $this->assertFileExists($layoutPath, 'activity_main.xml template should exist');

        $layoutContent = File::get($layoutPath);

        // Ensure layout doesn't contain static references to splash drawable
        // This would cause Android resource linking to fail when splash doesn't exist
        $this->assertStringNotContainsString(
            'android:src="@drawable/splash"',
            $layoutContent,
            'Layout should not contain static reference to @drawable/splash as it may not exist'
        );

        // Verify ImageView exists but without static src
        $this->assertStringContainsString('android:id="@+id/splashImage"', $layoutContent);
        $this->assertStringContainsString('android:id="@+id/splashText"', $layoutContent);
    }

    public function test_activity_main_layout_has_proper_fallback_structure()
    {
        $layoutPath = __DIR__.'/../../../resources/androidstudio/app/src/main/res/layout/activity_main.xml';
        $layoutContent = File::get($layoutPath);

        // Verify proper fallback structure exists
        $this->assertStringContainsString('<ImageView', $layoutContent);
        $this->assertStringContainsString('<TextView', $layoutContent);
        $this->assertStringContainsString('android:visibility="gone"', $layoutContent, 'ImageView should be hidden by default');
        $this->assertStringContainsString('android:visibility="visible"', $layoutContent, 'TextView should be visible by default');
    }

    public function test_layout_xml_is_valid_xml()
    {
        $layoutPath = __DIR__.'/../../../resources/androidstudio/app/src/main/res/layout/activity_main.xml';
        $layoutContent = File::get($layoutPath);

        // Attempt to parse as XML to ensure it's valid
        $xml = simplexml_load_string($layoutContent);
        $this->assertNotFalse($xml, 'Layout XML should be valid XML');

        // Verify root element
        $this->assertEquals('FrameLayout', $xml->getName());
    }

    public function test_layout_only_references_safe_android_resources()
    {
        $layoutPath = __DIR__.'/../../../resources/androidstudio/app/src/main/res/layout/activity_main.xml';
        $layoutContent = File::get($layoutPath);

        // Find all drawable references
        preg_match_all('/@drawable\/([a-zA-Z0-9_]+)/', $layoutContent, $matches);

        // Only allow safe, guaranteed Android system drawables
        $allowedDrawables = []; // No custom drawables should be referenced statically

        // Assert that we found this many drawable references (for test visibility)
        $this->assertLessThanOrEqual(0, count($matches[1]), 'Layout should not reference custom drawables statically');

        foreach ($matches[1] as $drawable) {
            $this->assertContains(
                $drawable,
                $allowedDrawables,
                "Drawable '{$drawable}' should not be referenced statically in layout as it may not exist"
            );
        }
    }

    public function test_layout_uses_safe_android_colors_only()
    {
        $layoutPath = __DIR__.'/../../../resources/androidstudio/app/src/main/res/layout/activity_main.xml';
        $layoutContent = File::get($layoutPath);

        // Find all color references
        preg_match_all('/@android:color\/([a-zA-Z0-9_]+)/', $layoutContent, $matches);

        // Android system colors that are guaranteed to exist
        $safeAndroidColors = [
            'background_dark',
            'background_light',
            'white',
            'black',
            'transparent',
        ];

        foreach ($matches[1] as $color) {
            $this->assertContains(
                $color,
                $safeAndroidColors,
                "Color '{$color}' should be a safe Android system color"
            );
        }
    }
}
