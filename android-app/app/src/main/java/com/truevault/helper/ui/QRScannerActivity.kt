package com.truevault.helper.ui

import android.Manifest
import android.content.Intent
import android.os.Bundle
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import com.google.zxing.ResultPoint
import com.journeyapps.barcodescanner.BarcodeCallback
import com.journeyapps.barcodescanner.BarcodeResult
import com.journeyapps.barcodescanner.DecoratedBarcodeView
import com.permissionx.guolindev.PermissionX
import com.truevault.helper.R
import com.truevault.helper.utils.WireGuardHelper

/**
 * QRScannerActivity - Camera-based QR code scanner
 * 
 * Scans QR codes from desktop screens using device camera
 * Extracts WireGuard config and imports to WireGuard app
 */
class QRScannerActivity : AppCompatActivity() {

    private lateinit var barcodeView: DecoratedBarcodeView
    private var isScanning = false

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_qr_scanner)

        barcodeView = findViewById(R.id.barcodeScanner)
        
        requestCameraPermission()
    }

    private fun requestCameraPermission() {
        PermissionX.init(this)
            .permissions(Manifest.permission.CAMERA)
            .onExplainRequestReason { scope, deniedList ->
                scope.showRequestReasonDialog(
                    deniedList,
                    getString(R.string.permission_camera),
                    "OK",
                    "Cancel"
                )
            }
            .request { allGranted, _, _ ->
                if (allGranted) {
                    startScanning()
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

    private fun startScanning() {
        isScanning = true
        
        barcodeView.decodeContinuous(object : BarcodeCallback {
            override fun barcodeResult(result: BarcodeResult?) {
                if (result == null || !isScanning) return
                
                isScanning = false
                handleQRCode(result.text)
            }

            override fun possibleResultPoints(resultPoints: MutableList<ResultPoint>?) {
                // Optional: Draw dots on detected points
            }
        })
        
        barcodeView.resume()
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
                    R.string.qr_success,
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
        
        // Allow retry
        isScanning = true
        barcodeView.resume()
    }

    override fun onResume() {
        super.onResume()
        if (isScanning) {
            barcodeView.resume()
        }
    }

    override fun onPause() {
        super.onPause()
        barcodeView.pause()
    }
}
