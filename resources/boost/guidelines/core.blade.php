## NativePHP Mobile

- NativePHP Mobile is a Laravel package that enables developers to build native iOS and Android applications using PHP and native UI components.
- NativePHP Mobile runs a full PHP runtime directly on the device with SQLite — no web server required.
- NativePHP Mobile supports **two frontend approaches**: Livewire/Blade (PHP) or JavaScript frameworks (Vue, React, Inertia, etc.).
- NativePHP Mobile documentation is hosted at `https://nativephp.com/docs/mobile/2/**`
- **Before implementing any features using NativePHP Mobile, use the `web-search` tool to get the latest docs for that specific feature. The docs listing is available in <available-docs>**

### Identifying the Development Environment

**IMPORTANT:** Before running commands or giving platform-specific advice, determine:

1. **Operating System** (check with system info or ask):
   - **macOS**: Can build and run for **both iOS and Android**
   - **Windows/Linux**: Can **only build for Android** — iOS requires macOS with Xcode
   - When on Windows/Linux, never suggest `php artisan native:run ios`, `native:install ios`, or `native:open ios`
   - **Note:** WSL (Windows Subsystem for Linux) is NOT supported — must run directly on Windows

2. **Frontend Stack** (examine the codebase):
   - **Livewire/Blade**: Look for `.blade.php` files with `wire:` directives, Livewire components in `app/Livewire/`
   - **JavaScript (Vue/React/Inertia)**: Look for `.vue`, `.jsx`, `.tsx` files, `resources/js/` with framework code, `inertiajs` in `package.json`

### Required Environment Variables

**CRITICAL:** Before running `php artisan native:install`, ensure these are set in `.env`:

```dotenv
NATIVEPHP_APP_ID=com.yourcompany.yourapp
NATIVEPHP_APP_VERSION="DEBUG"
NATIVEPHP_APP_VERSION_CODE="1"
```

- `NATIVEPHP_APP_ID`: Reverse-domain app identifier (e.g., `com.acme.myapp`) — **required**
- `NATIVEPHP_APP_VERSION`: Use `"DEBUG"` for development, semantic version (e.g., `"1.0.0"`) for production
- `NATIVEPHP_APP_VERSION_CODE`: Integer build number for Play Store (increment with each release)

**Optional but recommended for iOS:**
```dotenv
NATIVEPHP_DEVELOPMENT_TEAM=XXXXXXXXXX
```
Find your Team ID in your [Apple Developer account](https://developer.apple.com/account) under 'Membership details'.

### PHP Usage (Livewire/Blade Projects)

Use PHP Facades in the `Native\Mobile\Facades` namespace:
- `Camera::getPhoto()`, `Dialog::toast()`, `Biometrics::prompt()`, etc.
- All Facades: `Camera`, `Dialog`, `Biometrics`, `Network`, `SecureStorage`, `File`, `Share`, `Haptics`, `System`, `Device`
- Note: `Browser`, `Scanner`, `Microphone`, `Geolocation`, and `PushNotifications` are available as separate NativePHP plugins (nativephp/browser, nativephp/scanner, nativephp/microphone, nativephp/geolocation, nativephp/mobile-firebase).
- Listen for events with `#[OnNative(EventClass::class)]` attribute on Livewire component methods
- Use EDGE components in Blade templates for native UI (`native:bottom-nav`, `native:top-bar`, `native:side-nav`)

### JavaScript Usage (Vue/React/Inertia Projects)

Import from the NativePHP JavaScript bridge library:
```javascript
import { camera, dialog, scanner, biometric, on, Events } from '#nativephp';
// or individual imports
import { getPhoto, alert, scanQR } from '#nativephp';
```

The JS API mirrors the PHP Facades with fluent builders:
- `await camera.getPhoto()` / `await dialog.alert('Title', 'Message')` / `await scanner.scan()`
- `await biometric.prompt().id('auth-check')` — fluent builder pattern
- `await scanner.scan().prompt('Scan ticket').formats(['qr', 'ean13'])`

Listen for events with `on()` and `off()`:
```javascript
import { on, off, Events } from '#nativephp';
on(Events.Camera.PhotoTaken, (payload) => { /* handle photo */ });
// Cleanup in unmount: off(Events.Camera.PhotoTaken, handler);
```

### Event Handling (Both Stacks)

Asynchronous operations dispatch events to both JavaScript and PHP simultaneously.

**Livewire/Blade:**
```php
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Camera\PhotoTaken;

#[OnNative(PhotoTaken::class)]
public function handlePhoto(string $path) { /* ... */ }
```

**JavaScript (Vue/React):**
```javascript
import { on, Events } from '#nativephp';
on(Events.Camera.PhotoTaken, ({ path }) => { /* ... */ });
```

Custom events can extend built-in events and be passed via `->event(CustomEvent::class)` (PHP) or `.event('App\\Events\\Custom')` (JS).

### EDGE Components (Native UI)

- EDGE (Element Definition and Generation Engine) renders Blade components as truly native UI elements.
- Components use `native:` prefix: `native:bottom-nav`, `native:top-bar`, `native:side-nav`.
- Child items require unique `id` attributes for lifecycle management.
- Add `nativephp-safe-area` class to body for proper handling of notches and navigation areas.
- **Note:** EDGE components are defined in Blade templates and work with both Livewire and Inertia apps (the layout is still Blade).

<available-docs>
## Getting Started
- [https://nativephp.com/docs/mobile/2/getting-started/introduction] Use these docs for comprehensive introduction to NativePHP Mobile, overview of how PHP runs natively on device, the embedded runtime architecture, and core philosophy behind the package
- [https://nativephp.com/docs/mobile/2/getting-started/quick-start] Use these docs for rapid setup guide to get your first mobile app running in minutes
- [https://nativephp.com/docs/mobile/2/getting-started/environment-setup] Use these docs for setting up your development environment including Xcode, Android Studio, simulators, and required dependencies
- [https://nativephp.com/docs/mobile/2/getting-started/installation] Use these docs for step-by-step installation via Composer, running `php artisan native:install`, platform-specific setup, and ICU support options
- [https://nativephp.com/docs/mobile/2/getting-started/configuration] Use these docs for detailed configuration guide including NATIVEPHP_APP_ID, NATIVEPHP_APP_VERSION, permissions setup, and config/nativephp.php options
- [https://nativephp.com/docs/mobile/2/getting-started/development] Use these docs for development workflow including `php artisan native:run`, `native:watch` for hot reload, `native:tail` for logs, and debugging techniques
- [https://nativephp.com/docs/mobile/2/getting-started/deployment] Use these docs for packaging and deploying apps to App Store and Play Store using `php artisan native:package`
- [https://nativephp.com/docs/mobile/2/getting-started/versioning] Use these docs for version management, semantic versioning, and `php artisan native:release` command
- [https://nativephp.com/docs/mobile/2/getting-started/changelog] Use these docs for version history and release notes
- [https://nativephp.com/docs/mobile/2/getting-started/roadmap] Use these docs for upcoming features and planned improvements
- [https://nativephp.com/docs/mobile/2/getting-started/support-policy] Use these docs for support policy and compatibility information

## The Basics
- [https://nativephp.com/docs/mobile/2/the-basics/overview] Use these docs for understanding how NativePHP Mobile works, the bridge between PHP and native code, and the overall architecture
- [https://nativephp.com/docs/mobile/2/the-basics/events] Use these docs for the complete event system guide including async vs sync operations, event handling in Livewire with `#[OnNative()]`, JavaScript event handling with `Native.on()`, custom events, and the dual dispatch pattern
- [https://nativephp.com/docs/mobile/2/the-basics/native-functions] Use these docs for understanding the `nativephp_call()` function, the bridge function registry, and how to extend native functionality
- [https://nativephp.com/docs/mobile/2/the-basics/native-components] Use these docs for overview of native UI components and how they integrate with your app
- [https://nativephp.com/docs/mobile/2/the-basics/web-view] Use these docs for understanding the web view rendering, JavaScript bridge, and how PHP content is displayed
- [https://nativephp.com/docs/mobile/2/the-basics/splash-screens] Use these docs for configuring splash screens on iOS and Android
- [https://nativephp.com/docs/mobile/2/the-basics/app-icon] Use these docs for setting up app icons for both platforms
- [https://nativephp.com/docs/mobile/2/the-basics/assets] Use these docs for managing static assets, images, and files in your mobile app

## EDGE Components (Native UI)
- [https://nativephp.com/docs/mobile/2/edge-components/introduction] Use these docs for understanding EDGE (Element Definition and Generation Engine), how Blade components become native UI, server-driven UI approach, and the JSON compilation process
- [https://nativephp.com/docs/mobile/2/edge-components/bottom-nav] Use these docs for implementing bottom navigation bars with `native:bottom-nav` and `native:bottom-nav-item`, including icons, labels, URLs, and styling
- [https://nativephp.com/docs/mobile/2/edge-components/top-bar] Use these docs for implementing top app bars with `native:top-bar` and `native:top-bar-action`, including titles, navigation icons, and action buttons
- [https://nativephp.com/docs/mobile/2/edge-components/side-nav] Use these docs for implementing slide-out navigation drawers with `native:side-nav`, `native:side-nav-item`, `native:side-nav-header`, and `native:side-nav-group`
- [https://nativephp.com/docs/mobile/2/edge-components/icons] Use these docs for available icon names and how to use icons in EDGE components

## APIs (Device Features)
- [https://nativephp.com/docs/mobile/2/apis/camera] Use these docs for camera operations including `Camera::getPhoto()`, `Camera::recordVideo()`, `Camera::pickImages()`, PhotoTaken and VideoRecorded events
- [https://nativephp.com/docs/mobile/2/apis/microphone] Use these docs for audio recording with `Microphone::record()`, `->start()`, `->stop()`, `->pause()`, `->resume()`, `->getStatus()`, and MicrophoneRecorded events
- [https://nativephp.com/docs/mobile/2/apis/scanner] Use these docs for QR code and barcode scanning with `Scanner::scan()`, fluent configuration, CodeScanned events, and supported formats
- [https://nativephp.com/docs/mobile/2/apis/dialog] Use these docs for native dialogs with `Dialog::alert()`, `Dialog::toast()`, button configuration, and ButtonPressed events
- [https://nativephp.com/docs/mobile/2/apis/biometrics] Use these docs for Face ID/Touch ID authentication with `Biometrics::prompt()`, fluent API, and Completed events
- [https://nativephp.com/docs/mobile/2/apis/push-notifications] Use these docs for push notification enrollment with `PushNotifications::enroll()`, `->getToken()`, and TokenGenerated events (requires nativephp/mobile-firebase plugin)
- [https://nativephp.com/docs/mobile/2/apis/geolocation] Use these docs for location services with `Geolocation::getCurrentPosition()`, `->checkPermissions()`, `->requestPermissions()`, and LocationReceived events
- [https://nativephp.com/docs/mobile/2/apis/browser] Use these docs for opening URLs with `Browser::open()`, `Browser::inApp()`, `Browser::auth()` for OAuth flows (requires nativephp/browser plugin)
- [https://nativephp.com/docs/mobile/2/apis/secure-storage] Use these docs for secure credential storage with `SecureStorage::get()`, `->set()`, `->delete()` using device Keychain/KeyStore
- [https://nativephp.com/docs/mobile/2/apis/share] Use these docs for native share sheet with `Share::url()` and `Share::file()`
- [https://nativephp.com/docs/mobile/2/apis/file] Use these docs for file operations with `File::move()` and `File::copy()`
- [https://nativephp.com/docs/mobile/2/apis/network] Use these docs for network status checking with `Network::status()`
- [https://nativephp.com/docs/mobile/2/apis/haptics] Use these docs for haptic feedback with `Haptics::vibrate()` (prefer `Device::vibrate()`)
- [https://nativephp.com/docs/mobile/2/apis/device] Use these docs for device information with `Device::getId()`, `->getInfo()`, `->getBatteryInfo()`, `->vibrate()`, `->toggleFlashlight()`
- [https://nativephp.com/docs/mobile/2/apis/system] Use these docs for platform detection with `System::isIos()`, `System::isAndroid()`, `System::isMobile()`, `System::flashlight()`

## Concepts
- [https://nativephp.com/docs/mobile/2/concepts/databases] Use these docs for SQLite database usage, local data storage, and when to use local vs API storage
- [https://nativephp.com/docs/mobile/2/concepts/deep-links] Use these docs for configuring deep links, URL schemes, and universal links
- [https://nativephp.com/docs/mobile/2/concepts/push-notifications] Use these docs for comprehensive push notification setup including Firebase, APNs, and server-side integration
- [https://nativephp.com/docs/mobile/2/concepts/security] Use these docs for security best practices, secure storage, and protecting sensitive data
- [https://nativephp.com/docs/mobile/2/concepts/ci-cd] Use these docs for continuous integration and deployment pipelines for mobile apps
</available-docs>