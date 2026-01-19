# TrueVault Helper APK - Android App

## 📱 IMPORTANT: APK Build Required

The Android Helper app source code is complete in `/android-app/` directory.
**You need to build the APK file before users can download it.**

---

## 🔨 HOW TO BUILD THE APK

### Step 1: Install Android Studio
1. Download Android Studio: https://developer.android.com/studio
2. Install with default settings
3. Open Android Studio

### Step 2: Open Project
1. File > Open
2. Navigate to: `E:\Documents\GitHub\truevault-vpn\android-app\`
3. Click OK
4. Wait for Gradle sync (may take 5-10 minutes first time)

### Step 3: Build APK
1. Build > Build Bundle(s) / APK(s) > Build APK(s)
2. Wait for build to complete
3. APK location: `android-app/app/build/outputs/apk/debug/app-debug.apk`

### Step 4: Copy APK to Website
```
Copy: android-app/app/build/outputs/apk/debug/app-debug.apk
To: E:\Documents\GitHub\truevault-vpn\website\downloads\TrueVault-Helper.apk
```

### Step 5: Upload to Server
Use FTP to upload the APK:
```
Local: E:\Documents\GitHub\truevault-vpn\website\downloads\TrueVault-Helper.apk
Remote: /public_html/vpn.the-truth-publishing.com/downloads/TrueVault-Helper.apk
```

---

## 📦 RELEASE BUILD (For Play Store)

### Generate Signing Key
```bash
keytool -genkey -v -keystore truevault-release.keystore \
  -alias truevault -keyalg RSA -keysize 2048 -validity 10000
```

Enter details when prompted:
- Password: [save this securely]
- Name: Kah-Len / TrueVault
- Organization: TrueVault VPN
- City: Your city
- State: Your state
- Country: US

### Build Signed APK
1. Build > Generate Signed Bundle / APK
2. Select APK
3. Choose keystore file
4. Enter keystore password
5. Choose "release" build variant
6. Click Finish

Signed APK: `android-app/app/release/app-release.apk`

---

## 🚀 PLAY STORE PUBLISHING

### Prerequisites
- Google Play Developer Account ($25 one-time fee)
- Signed release APK
- App icon (1024x1024px)
- Screenshots (phone + tablet)
- App description

### Steps
1. Go to: https://play.google.com/console
2. Create new app: "TrueVault Helper"
3. Fill in app details:
   - Category: Tools
   - Content rating: Everyone
   - Privacy policy URL: https://vpn.the-truth-publishing.com/privacy
4. Upload screenshots
5. Upload release APK
6. Set pricing: Free
7. Submit for review (2-3 days)

---

## 📋 CURRENT STATUS

- ✅ Source code complete (24 files, 1,783 lines)
- ⏳ APK not built yet (requires Android Studio)
- ⏳ Website download link ready (waiting for APK)

---

## 🔧 TROUBLESHOOTING

### Build Errors
- **Gradle sync failed**: Update Gradle in Android Studio
- **SDK not found**: Install Android SDK 34 via SDK Manager
- **Kotlin error**: Update Kotlin plugin

### APK Issues
- **App not installing**: Enable "Install from unknown sources" in Settings
- **App crashes**: Check logcat in Android Studio
- **Permissions denied**: Grant camera and storage permissions

---

## 📞 SUPPORT

Build issues? Contact Kah-Len at paulhalonen@gmail.com

**App Version:** 1.0.0
**Min Android:** 8.0 (API 26)
**Target Android:** 14 (API 34)
**Package:** com.truevault.helper
