package com.truevault.helper.ui

import android.Manifest
import android.content.Intent
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.net.Uri
import android.os.Build
import android.os.Bundle
import android.provider.MediaStore
import android.widget.Toast
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import com.google.zxing.BinaryBitmap
import com.google.zxing.MultiFormatReader
import com.google.zxing.RGBLuminanceSource
import com.google.zxing.common.HybridBinarizer
import com.permissionx.guolindev.PermissionX
import com.truevault.helper.R
import com.truevault.helper.utils.WireGuardHelper

/**
 * ScreenshotScannerActivity - Scan QR codes from screenshots
 * 
 * Solves the #1 Android issue: Users can't scan QR on their own screen
 * Allows selecting a screenshot and extracting the QR code from it
 */
class ScreenshotScannerActivity : AppCompatActivity() {

    private val pickImage = registerForActivityResult(
        ActivityResultContracts.GetContent()
    ) { uri: Uri? ->
        uri?.let { processImage(it) }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        
        // No layout needed - immediately request permission and pick image
        requestStoragePermission()
    }

    private fun requestStoragePermission() {
        val permission = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            Manifest.permission.READ_MEDIA_IMAGES
        } else {
            Manifest.permission.READ_EXTERNAL_STORAGE
        }

        PermissionX.init(this)
            .permissions(permission)
            .onExplainRequestReason { scope, deniedList ->
                scope.showRequestReasonDialog(
                    deniedList,
                    getString(R.string.permission_storage),
                    "OK",
                    "Cancel"
                )
            }
            .request { allGranted, _, _ ->
                if (allGranted) {
                    pickImageFromGallery()
                } else {
                    Toast.makeText(
                        this,
                        R.string.permission_denied,
                        Toast.LENGTH_LONG
                    ).show()
                    finish()
                }
            }
    }

    private fun pickImageFromGallery() {
        pickImage.launch("image/*")
    }

    private fun processImage(imageUri: Uri) {
        try {
            // Load image as bitmap
            val bitmap = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.P) {
                val source = ImageDecoder.createSource(contentResolver, imageUri)
                ImageDecoder.decodeBitmap(source)
            } else {
                @Suppress("DEPRECATION")
                MediaStore.Images.Media.getBitmap(contentResolver, imageUri)
            }

            // Scan for QR code
            val qrText = scanQRFromBitmap(bitmap)
            
            if (qrText != null) {
                handleQRCode(qrText)
            } else {
                showError(getString(R.string.screenshot_error))
            }

        } catch (e: Exception) {
            showError("Error processing image: ${e.message}")
        }
    }

    private fun scanQRFromBitmap(bitmap: Bitmap): String? {
        return try {
            val width = bitmap.width
            val height = bitmap.height
            val pixels = IntArray(width * height)
            
            bitmap.getPixels(pixels, 0, width, 0, 0, width, height)
            
            val source = RGBLuminanceSource(width, height, pixels)
            val binaryBitmap = BinaryBitmap(HybridBinarizer(source))
            
            val reader = MultiFormatReader()
            val result = reader.decode(binaryBitmap)
            
            result.text
        } catch (e: Exception) {
            null
        }
    }

    private fun handleQRCode(qrText: String) {
        // Parse WireGuard config from QR code
        val isValid = WireGuardHelper.isValidConfig(qrText)
        
        if (isValid) {
            // Save config and prepare for import
            val success = WireGuardHelper.saveConfig(this, qrText)
            
            if (success) {
                Toast.makeText(
                    this,
                    R.string.screenshot_success,
                    Toast.LENGTH_SHORT
                ).show()
                
                // Import to WireGuard
                WireGuardHelper.importToWireGuard(this)
                finish()
            } else {
                showError("Failed to save configuration")
            }
        } else {
            showError(getString(R.string.qr_error))
        }
    }

    private fun showError(message: String) {
        Toast.makeText(this, message, Toast.LENGTH_LONG).show()
        finish()
    }
}

// Import for Android P+
import android.graphics.ImageDecoder
