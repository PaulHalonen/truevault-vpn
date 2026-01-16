# MASTER CHECKLIST - PART 10: ANDROID HELPER APP

**Blueprint Section:** SECTION_21_ANDROID_APP.md  
**Created:** January 16, 2026  
**Estimated Time:** 15-20 hours (3 weeks development)  
**Priority:** HIGH - Solves 60% of Android support tickets  

---

## 📋 OVERVIEW

Native Android app that solves the #1 Android setup problem: `.conf.txt` file extension issue.

**App Name:** TrueVault Helper  
**Language:** Kotlin  
**Min SDK:** Android 8.0 (API 26)  
**Target SDK:** Android 14 (API 34)

**Key Features:**
- QR Scanner from Screenshots (can't scan own screen)
- Auto-Fix .conf.txt files (background monitor)
- Camera QR Scanner (for desktop screens)
- One-tap import to WireGuard

---

## 📌 PREREQUISITES

Before starting PART 10, ensure:
- [ ] Android Studio installed (latest version)
- [ ] Android SDK 34 installed
- [ ] Java JDK 17+ installed
- [ ] Physical Android device for testing (or emulator)
- [ ] Google Play Developer account ($25 one-time fee) - optional
- [ ] PART 1-8 completed (VPN backend working)

---

## 🔧 TASK 10.1: Project Setup

**Time Estimate:** 1 hour

### 10.1.1 Create Android Studio Project
- [ ] Open Android Studio
- [ ] New Project → Empty Activity
- [ ] Name: TrueVaultHelper
- [ ] Package: com.truevault.helper
- [ ] Language: Kotlin
- [ ] Minimum SDK: API 26 (Android 8.0)
- [ ] Build configuration: Kotlin DSL

### 10.1.2 Configure build.gradle
- [ ] Add dependencies:
  ```gradle
  // QR Code Scanning
  implementation 'com.google.zxing:core:3.5.2'
  implementation 'com.journeyapps:zxing-android-embedded:4.3.0'
  
  // Permissions
  implementation 'com.guolindev.permissionx:permissionx:1.7.1'
  
  // Android Components
  implementation 'androidx.core:core-ktx:1.12.0'
  implementation 'com.google.android.material:material:1.11.0'
  implementation 'androidx.lifecycle:lifecycle-runtime-ktx:2.7.0'
  ```
- [ ] Set targetSdk = 34
- [ ] Set minSdk = 26
- [ ] Enable viewBinding

### 10.1.3 Configure AndroidManifest.xml
- [ ] Add permissions:
  ```xml
  <uses-permission android:name="android.permission.CAMERA" />
  <uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />
  <uses-permission android:name="android.permission.WRITE_EXTERNAL_STORAGE" />
  <uses-permission android:name="android.permission.READ_MEDIA_IMAGES" />
  <uses-permission android:name="android.permission.FOREGROUND_SERVICE" />
  ```
- [ ] Add FileProvider for sharing configs
- [ ] Register all activities and services

### 10.1.4 Create Project Structure
- [ ] Create package: utils/
- [ ] Create package: services/
- [ ] Create package: ui/
- [ ] Create res/drawable/ icons
- [ ] Create res/values/ colors and strings

**Verification:**
- [ ] Project builds successfully
- [ ] App runs on emulator (blank screen OK)

---

## 🔧 TASK 10.2: App Branding & Design

**Time Estimate:** 1 hour

### 10.2.1 Create App Icon
- [ ] Design shield icon with QR pattern
- [ ] Gradient: Cyan (#00D9FF) → Green (#00FF88)
- [ ] Create launcher_foreground.xml
- [ ] Create launcher_background.xml
- [ ] Generate all icon sizes (mdpi to xxxhdpi)

### 10.2.2 Define Color Scheme
- [ ] Create colors.xml:
  ```xml
  <color name="cyan">#00D9FF</color>
  <color name="green">#00FF88</color>
  <color name="dark_bg">#0F0F1A</color>
  <color name="dark_card">#1A1A2E</color>
  <color name="white">#FFFFFF</color>
  <color name="gray">#888888</color>
  ```

### 10.2.3 Create Theme
- [ ] Create dark theme matching TrueVault website
- [ ] Set status bar color
- [ ] Set navigation bar color
- [ ] Configure button styles

### 10.2.4 Create String Resources
- [ ] App name: "TrueVault Helper"
- [ ] All UI text strings
- [ ] Error messages
- [ ] Success messages

**Verification:**
- [ ] App icon displays correctly
- [ ] Dark theme applied throughout

---

## 🔧 TASK 10.3: Main Activity (3 Action Cards)

**Time Estimate:** 2 hours

### 10.3.1 Create activity_main.xml Layout
- [ ] TrueVault logo at top
- [ ] Welcome text: "Setup your VPN in seconds"
- [ ] Card 1: "📸 Scan from Screenshot"
- [ ] Card 2: "📷 Scan with Camera"
- [ ] Card 3: "🔧 Fix Downloaded Configs"
- [ ] Footer: Version number and support link

### 10.3.2 Implement MainActivity.kt
- [ ] Extend AppCompatActivity
- [ ] Setup viewBinding
- [ ] Card 1 onClick → QRScannerActivity (gallery mode)
- [ ] Card 2 onClick → QRScannerActivity (camera mode)
- [ ] Card 3 onClick → scanAndFixConfigs()
- [ ] Check and start FileMonitorService

### 10.3.3 Add Permission Handling
- [ ] Request camera permission for card 2
- [ ] Request storage permission for card 1 & 3
- [ ] Handle Android 13+ media permissions
- [ ] Show rationale dialogs

### 10.3.4 Add Service Start
- [ ] Check if FileMonitorService running
- [ ] Start service if not running
- [ ] Show toggle for auto-monitoring

**Verification:**
- [ ] Main screen displays 3 cards
- [ ] Cards are clickable
- [ ] Permissions requested correctly

---

## 🔧 TASK 10.4: QR Scanner Activity

**Time Estimate:** 3 hours

### 10.4.1 Create activity_qr_scanner.xml Layout
- [ ] Camera preview area (full screen)
- [ ] Overlay with scan frame
- [ ] "Scanning..." text
- [ ] Gallery button (bottom left)
- [ ] Flash toggle (bottom right)
- [ ] Close button (top right)

### 10.4.2 Implement Camera Scanner
- [ ] Initialize ZXing barcode scanner
- [ ] Handle camera lifecycle
- [ ] Process QR codes in real-time
- [ ] Vibrate on successful scan
- [ ] Parse WireGuard config from QR

### 10.4.3 Implement Gallery Scanner
- [ ] Open image picker intent
- [ ] Load selected image
- [ ] Scan image for QR codes
- [ ] Handle rotation/orientation
- [ ] Support multiple image formats

### 10.4.4 Handle Scan Results
- [ ] Validate WireGuard config format
- [ ] Show success animation
- [ ] Ask: "Import to WireGuard?"
- [ ] Call WireGuardHelper.importConfig()
- [ ] Handle errors gracefully

### 10.4.5 Add Screenshot Detection
- [ ] Check if image is from Screenshots folder
- [ ] Auto-suggest screenshot scanning
- [ ] Sort by most recent first

**Verification:**
- [ ] Camera scanner works on physical device
- [ ] Gallery scanner detects QR in images
- [ ] Config imports to WireGuard app

---

## 🔧 TASK 10.5: WireGuard Import Helper

**Time Estimate:** 2 hours

### 10.5.1 Create WireGuardHelper.kt
- [ ] Create object WireGuardHelper
- [ ] Check if WireGuard app installed
- [ ] Create importConfig(context, configContent, callback) function
- [ ] Create importConfigFile(context, file) function
- [ ] Handle FileProvider URI creation

### 10.5.2 Implement Config Import
- [ ] Save config to temp file
- [ ] Create content:// URI via FileProvider
- [ ] Create ACTION_VIEW intent
- [ ] Set MIME type: application/x-wireguard-config
- [ ] Add FLAG_GRANT_READ_URI_PERMISSION
- [ ] Start activity

### 10.5.3 Handle WireGuard Not Installed
- [ ] Check if WireGuard package exists
- [ ] Show dialog: "WireGuard app required"
- [ ] "Install from Play Store" button
- [ ] Open Play Store listing

### 10.5.4 Validate Config Format
- [ ] Check for [Interface] section
- [ ] Check for PrivateKey
- [ ] Check for Address
- [ ] Check for [Peer] section
- [ ] Show helpful error for invalid configs

**Verification:**
- [ ] Config opens in WireGuard app
- [ ] Invalid configs show error message
- [ ] Missing WireGuard prompts install

---

## 🔧 TASK 10.6: File Helper & Auto-Fix

**Time Estimate:** 2 hours

### 10.6.1 Create FileHelper.kt
- [ ] Create object FileHelper
- [ ] Get Downloads directory path
- [ ] scanForConfTxtFiles() function
- [ ] renameConfTxtToConf(file) function
- [ ] scanAndFixConfigs(context, callback) function

### 10.6.2 Implement .conf.txt Detection
- [ ] List files in Downloads folder
- [ ] Filter for *.conf.txt pattern
- [ ] Also check: *.conf.txt.1, *.conf.txt.2 (duplicates)
- [ ] Return list of fixable files

### 10.6.3 Implement Auto-Rename
- [ ] Create new filename (remove .txt)
- [ ] Check if target exists (add suffix if needed)
- [ ] Perform rename operation
- [ ] Verify rename successful
- [ ] Return success/failure

### 10.6.4 Manual Fix UI
- [ ] Show list of .conf.txt files found
- [ ] "Fix All" button
- [ ] Individual fix buttons
- [ ] Show success count
- [ ] Offer to import fixed configs

**Verification:**
- [ ] Finds .conf.txt files in Downloads
- [ ] Successfully renames to .conf
- [ ] Reports accurate fix count

---

## 🔧 TASK 10.7: Background File Monitor Service

**Time Estimate:** 3 hours

### 10.7.1 Create FileMonitorService.kt
- [ ] Extend Service
- [ ] Implement foreground service (required Android 8+)
- [ ] Create notification channel
- [ ] Show persistent notification: "Monitoring Downloads"

### 10.7.2 Implement FileObserver
- [ ] Observe Downloads directory
- [ ] Listen for CREATE events
- [ ] Filter for .conf.txt files
- [ ] Auto-rename when detected
- [ ] Show notification: "Fixed: filename.conf"

### 10.7.3 Implement Auto-Import (Optional)
- [ ] Check Settings.autoImport preference
- [ ] If enabled, auto-import after fix
- [ ] Open WireGuard with config
- [ ] User can disable in settings

### 10.7.4 Service Lifecycle
- [ ] Start on boot (optional)
- [ ] Handle device sleep/wake
- [ ] Battery optimization handling
- [ ] Restart if killed

### 10.7.5 Create NotificationHelper.kt
- [ ] Create notification channel
- [ ] showMonitoringNotification() - persistent
- [ ] showConfigFixedNotification(filename) - alert
- [ ] Make notification tappable (opens fixed file)

**Verification:**
- [ ] Service starts and shows notification
- [ ] Detects new .conf.txt files automatically
- [ ] Auto-renames and notifies user
- [ ] Service survives app close

---

## 🔧 TASK 10.8: Settings Activity

**Time Estimate:** 1 hour

### 10.8.1 Create activity_settings.xml
- [ ] Toggle: "Auto-monitor Downloads folder"
- [ ] Toggle: "Auto-import fixed configs"
- [ ] Toggle: "Start on device boot"
- [ ] Toggle: "Vibrate on scan success"
- [ ] About section with version
- [ ] Support link

### 10.8.2 Implement SettingsActivity.kt
- [ ] Use SharedPreferences for settings
- [ ] Implement toggle change listeners
- [ ] Start/stop service based on toggle
- [ ] Handle boot receiver registration

### 10.8.3 Create BootReceiver
- [ ] Extend BroadcastReceiver
- [ ] Listen for BOOT_COMPLETED
- [ ] Start FileMonitorService if enabled
- [ ] Register in manifest

**Verification:**
- [ ] Settings save and persist
- [ ] Service toggle works
- [ ] Boot start works (test by restarting device)

---

## 🔧 TASK 10.9: Error Handling & UX Polish

**Time Estimate:** 2 hours

### 10.9.1 Add Loading States
- [ ] Show progress indicators during operations
- [ ] Disable buttons while processing
- [ ] Add smooth animations

### 10.9.2 Add Error Handling
- [ ] Catch all exceptions
- [ ] Show user-friendly error messages
- [ ] Log errors for debugging
- [ ] Offer retry options

### 10.9.3 Add Success Feedback
- [ ] Vibrate on successful scan
- [ ] Show success animation/checkmark
- [ ] Auto-close after import
- [ ] Toast messages for quick actions

### 10.9.4 Add Help/Tutorial
- [ ] First-launch tutorial overlay
- [ ] "How to use" button
- [ ] Step-by-step instructions
- [ ] FAQ section

### 10.9.5 Test on Multiple Devices
- [ ] Test on Android 8.0 device
- [ ] Test on Android 10 device
- [ ] Test on Android 13+ device
- [ ] Test on Samsung (One UI)
- [ ] Test on Pixel (stock Android)

**Verification:**
- [ ] No crashes in normal usage
- [ ] Error messages are helpful
- [ ] Works across Android versions

---

## 🔧 TASK 10.10: Build & Release

**Time Estimate:** 2 hours

### 10.10.1 Generate Signed APK
- [ ] Create keystore file (store securely!)
- [ ] Configure signing in build.gradle
- [ ] Build release APK
- [ ] Test release APK on device

### 10.10.2 Host Direct Download
- [ ] Upload APK to: vpn.the-truth-publishing.com/downloads/
- [ ] Create download page with instructions
- [ ] Add "Download TrueVault Helper" button
- [ ] Show version number and size

### 10.10.3 Google Play Store (Optional)
- [ ] Create Play Console listing
- [ ] Upload screenshots (phone + tablet)
- [ ] Write description and keywords
- [ ] Set content rating
- [ ] Submit for review

### 10.10.4 Website Integration
- [ ] Add Android helper CTA on setup page
- [ ] Add to FAQ: "Android setup taking too long?"
- [ ] Add to support pages
- [ ] Update device setup instructions

**Verification:**
- [ ] APK downloadable from website
- [ ] APK installs successfully
- [ ] All features work in release build

---

## ✅ PART 10 COMPLETION CHECKLIST

### Core Features
- [ ] QR scanner from camera works
- [ ] QR scanner from gallery/screenshots works
- [ ] .conf.txt auto-fix works
- [ ] WireGuard import works
- [ ] Background monitor works

### User Experience
- [ ] Clean, branded UI
- [ ] Smooth animations
- [ ] Helpful error messages
- [ ] Tutorial/help available

### Distribution
- [ ] Signed APK created
- [ ] APK hosted on website
- [ ] Download page created
- [ ] Integration with main site

### Testing
- [ ] Tested on 3+ devices
- [ ] Tested Android 8-14
- [ ] No crashes reported
- [ ] All permissions handled

---

## 🧪 TESTING CHECKLIST

### QR Scanning
- [ ] Scan QR from desktop screen (camera)
- [ ] Scan QR from screenshot (gallery)
- [ ] Scan rotated QR code
- [ ] Scan low-quality QR code
- [ ] Handle invalid QR gracefully

### File Fixing
- [ ] Download .conf.txt from TrueVault
- [ ] Verify auto-detection
- [ ] Verify auto-rename
- [ ] Verify notification
- [ ] Verify import prompt

### Service
- [ ] Service starts on app launch
- [ ] Service survives app close
- [ ] Service restarts on boot
- [ ] Battery usage acceptable

### Edge Cases
- [ ] WireGuard not installed
- [ ] No storage permission
- [ ] No camera permission
- [ ] Empty Downloads folder
- [ ] Multiple .conf.txt files

---

## 📝 DOCUMENTATION

After completing PART 10:
- [ ] Update BUILD_PROGRESS.md
- [ ] Update chat_log.txt
- [ ] Create ANDROID_APP_README.md
- [ ] Document keystore location (SECURE!)
- [ ] Update MAPPING.md

---

## 📊 SUCCESS METRICS

After launch, track:
- [ ] Android support tickets reduced
- [ ] App download count
- [ ] App crash rate (<0.1%)
- [ ] User ratings (if Play Store)

**Target:**
- Support tickets: -90% Android issues
- Setup time: <60 seconds
- User satisfaction: +40%

---

## ⏭️ NEXT STEPS

After PART 10 complete, proceed to:
- **PART 11:** Advanced Parental Controls (SECTION_22)

---

**PART 10 STATUS:** ⬜ NOT STARTED  
**Last Updated:** January 16, 2026

