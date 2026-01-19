# TrueVault Helper - Android App

**Native Android app to solve the `.conf.txt` file extension problem**

## 📱 Overview

TrueVault Helper is a native Android companion app that makes VPN setup seamless by:
- Scanning QR codes from screenshots (solves "can't scan own screen" issue)
- Scanning QR codes with camera (for desktop screens)
- Auto-fixing downloaded `.conf.txt` files to `.conf`
- One-tap import to WireGuard app

**Solves 60% of Android support tickets!**

---

## 🔧 Building the App

### Prerequisites
1. **Android Studio** (latest version)
   - Download: https://developer.android.com/studio
2. **JDK 17+**
   - Bundled with Android Studio or download separately
3. **Android SDK 34**
   - Installed via Android Studio SDK Manager

### Build Steps

1. **Open Project in Android Studio**
   ```
   File > Open > Select android-app folder
   ```

2. **Sync Gradle**
   ```
   Android Studio will automatically prompt to sync
   Or: File > Sync Project with Gradle Files
   ```

3. **Build APK**
   ```
   Build > Build Bundle(s) / APK(s) > Build APK(s)
   ```
   
   APK will be generated at:
   ```
   android-app/app/build/outputs/apk/debug/app-debug.apk
   ```

4. **Install on Device**
   ```
   adb install app/build/outputs/apk/debug/app-debug.apk
   ```

---

## 📦 Release Build (for Play Store)

### Generate Signing Key
```bash
keytool -genkey -v -keystore truevault-release.keystore \
  -alias truevault -keyalg RSA -keysize 2048 -validity 10000
```

### Configure Signing in Android Studio
1. Build > Generate Signed Bundle / APK
2. Select APK
3. Create new keystore or use existing
4. Fill in key details
5. Choose "release" build variant
6. Click Finish

### Upload to Play Store
1. Create Google Play Developer Account ($25 one-time fee)
2. Create new app in Play Console
3. Fill in app details, screenshots, description
4. Upload release APK
5. Submit for review

---

## 🧪 Testing

### Run on Emulator
1. Tools > Device Manager
2. Create Virtual Device (Pixel 6, API 34 recommended)
3. Run > Run 'app'

### Run on Physical Device
1. Enable Developer Options on device:
   - Settings > About Phone > Tap Build Number 7 times
2. Enable USB Debugging:
   - Settings > Developer Options > USB Debugging
3. Connect device via USB
4. Run > Run 'app'

### Test Cases
- [ ] Scan QR from screenshot (select image from gallery)
- [ ] Scan QR with camera (point at desktop screen)
- [ ] File monitor detects .conf.txt file in Downloads
- [ ] File monitor fixes .conf.txt to .conf
- [ ] Config imports successfully to WireGuard
- [ ] All permissions requested properly

---

## 📁 Project Structure

```
android-app/
├── app/
│   ├── src/main/
│   │   ├── java/com/truevault/helper/
│   │   │   ├── MainActivity.kt           # Main entry point
│   │   │   ├── ui/
│   │   │   │   ├── QRScannerActivity.kt  # Camera scanner
│   │   │   │   └── ScreenshotScannerActivity.kt  # Screenshot scanner
│   │   │   ├── utils/
│   │   │   │   └── WireGuardHelper.kt    # Config handling
│   │   │   └── services/
│   │   │       └── FileMonitorService.kt # Background monitor
│   │   ├── res/
│   │   │   ├── layout/                   # XML layouts
│   │   │   ├── values/                   # Strings, colors, themes
│   │   │   ├── drawable/                 # Icons, drawables
│   │   │   └── xml/                      # FileProvider paths
│   │   └── AndroidManifest.xml
│   └── build.gradle.kts
├── build.gradle.kts
└── settings.gradle.kts
```

---

## 🎨 Key Features Implemented

### 1. Screenshot QR Scanner
- Picks image from gallery
- Uses ZXing library to scan QR
- Extracts WireGuard config
- Validates config format
- Saves to app directory

### 2. Camera QR Scanner
- Real-time camera scanning
- Vibrant cyan/green laser effect
- Auto-detects QR codes
- Imports directly to WireGuard

### 3. File Monitor Service
- Runs in background as foreground service
- Watches Downloads folder
- Detects .conf.txt files
- Auto-fixes to .conf extension
- Shows notification when fixed

### 4. WireGuard Integration
- Validates WireGuard config format
- Saves configs with FileProvider
- Opens WireGuard app for import
- Handles missing WireGuard (opens Play Store)
- Cleans up old configs

---

## 🔒 Permissions

### Required Permissions
- **CAMERA** - For QR code scanning
- **READ_MEDIA_IMAGES** (Android 13+) - Access screenshots
- **READ_EXTERNAL_STORAGE** (Android 12 and below) - Access files
- **FOREGROUND_SERVICE** - Background file monitoring
- **POST_NOTIFICATIONS** - Show file fixed notifications

All permissions are requested at runtime using PermissionX library.

---

## 📚 Dependencies

```gradle
// QR Code Scanning
implementation 'com.google.zxing:core:3.5.2'
implementation 'com.journeyapps:zxing-android-embedded:4.3.0'

// Permissions
implementation 'com.guolindev.permissionx:permissionx:1.7.1'

// Android Core
implementation 'androidx.core:core-ktx:1.12.0'
implementation 'androidx.appcompat:appcompat:1.6.1'
implementation 'com.google.android.material:material:1.11.0'
```

---

## 🎨 Branding

**Colors:**
- Primary: Cyan (#00D9FF)
- Secondary: Green (#00FF88)
- Background: Dark (#0F0F1A)
- Cards: Dark (#1A1A2E)

**Logo:**
- Shield with QR pattern
- Cyan and green gradient
- Vector drawable (scalable)

---

## 🐛 Troubleshooting

### Build Errors
- **Gradle sync failed**: Update Gradle and Android Gradle Plugin
- **SDK not found**: Install SDK via SDK Manager (API 34)
- **Kotlin version mismatch**: Update Kotlin plugin

### Runtime Errors
- **Permission denied**: Check AndroidManifest.xml permissions
- **FileProvider error**: Verify file_paths.xml configuration
- **WireGuard not found**: App handles this, prompts install

### Testing Issues
- **QR not scanning**: Check camera permission granted
- **Screenshot not loading**: Check storage permission granted
- **File monitor not working**: Verify Downloads folder accessible

---

## 📈 Future Enhancements

- [ ] Batch QR scanning (multiple configs)
- [ ] Config editor (modify before import)
- [ ] Server location selector
- [ ] Speed test integration
- [ ] Connection statistics
- [ ] Auto-connect on boot
- [ ] Widget for quick connect

---

## 📞 Support

**Issues?** Contact Kah-Len at paulhalonen@gmail.com

**App Version:** 1.0.0  
**Min Android:** 8.0 (API 26)  
**Target Android:** 14 (API 34)

---

## 📄 License

Part of TrueVault VPN platform  
© 2026 TrueVault  
All rights reserved

---

**Built with ❤️ to solve the .conf.txt problem!**
