# TrueVault Downloads Directory

This directory contains downloadable files for TrueVault VPN customers.

## Files

### TrueVaultHelper.apk
Android helper app that makes VPN setup easy:
- Auto-fix .conf.txt files to .conf
- Scan QR codes from screenshots
- One-tap import to WireGuard
- Full device search for config files

**Version:** See version.json for current version info

### version.json
Contains version information for all downloadable apps.

## Updating the APK

When a new version of the Android app is built:

1. Build signed APK in Android Studio:
   - ☰ → Build → Generate Signed Bundle / APK
   - Select APK → Next
   - Use existing keystore → Next
   - Select release → Create

2. Copy the APK to this folder:
   ```
   Copy from: android/app/release/TrueVault-VPN.apk
   Copy to:   website/downloads/TrueVaultHelper.apk
   ```

3. Update version.json with new version info

4. Upload to server via FTP

## Note

The APK file is NOT stored in git (see .gitignore).
Upload the APK to the server manually via FTP after building.
