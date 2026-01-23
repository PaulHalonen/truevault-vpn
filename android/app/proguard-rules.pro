# Add project specific ProGuard rules here.

# ZXing
-keep class com.google.zxing.** { *; }
-dontwarn com.google.zxing.**

# Keep WireGuard intent handling
-keep class com.wireguard.** { *; }
