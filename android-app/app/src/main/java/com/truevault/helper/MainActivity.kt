package com.truevault.helper

import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import com.google.android.material.card.MaterialCardView
import com.truevault.helper.databinding.ActivityMainBinding
import com.truevault.helper.ui.QRScannerActivity
import com.truevault.helper.ui.ScreenshotScannerActivity
import com.truevault.helper.services.FileMonitorService

/**
 * MainActivity - Main entry point of TrueVault Helper
 * 
 * Presents 3 action cards:
 * 1. Scan from Screenshot
 * 2. Scan with Camera
 * 3. Fix Downloaded Files (start file monitor)
 */
class MainActivity : AppCompatActivity() {

    private lateinit var binding: ActivityMainBinding
    private var isMonitoring = false

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)

        setupUI()
        setupClickListeners()
    }

    private fun setupUI() {
        // Set version text
        val versionName = packageManager.getPackageInfo(packageName, 0).versionName
        binding.versionText.text = getString(R.string.version, versionName)

        // Check if file monitor is running
        updateMonitorStatus()
    }

    private fun setupClickListeners() {
        // Card 1: Scan from Screenshot
        binding.cardScreenshot.setOnClickListener {
            startActivity(Intent(this, ScreenshotScannerActivity::class.java))
        }

        // Card 2: Scan with Camera
        binding.cardCamera.setOnClickListener {
            startActivity(Intent(this, QRScannerActivity::class.java))
        }

        // Card 3: Fix Downloaded Files
        binding.cardFixFiles.setOnClickListener {
            toggleFileMonitor()
        }

        // Support link
        binding.supportLink.setOnClickListener {
            sendSupportEmail()
        }
    }

    private fun toggleFileMonitor() {
        if (isMonitoring) {
            // Stop monitoring
            stopService(Intent(this, FileMonitorService::class.java))
            isMonitoring = false
            Toast.makeText(this, "File monitor stopped", Toast.LENGTH_SHORT).show()
        } else {
            // Start monitoring
            val intent = Intent(this, FileMonitorService::class.java)
            startForegroundService(intent)
            isMonitoring = true
            Toast.makeText(this, "File monitor started", Toast.LENGTH_SHORT).show()
        }
        updateMonitorStatus()
    }

    private fun updateMonitorStatus() {
        // Check if service is running
        isMonitoring = FileMonitorService.isRunning
        
        if (isMonitoring) {
            binding.monitorStatus.visibility = android.view.View.VISIBLE
            binding.monitorStatus.text = getString(R.string.monitor_title)
        } else {
            binding.monitorStatus.visibility = android.view.View.GONE
        }
    }

    private fun sendSupportEmail() {
        val intent = Intent(Intent.ACTION_SENDTO).apply {
            data = Uri.parse("mailto:${getString(R.string.support_email)}")
            putExtra(Intent.EXTRA_SUBJECT, "TrueVault Helper Support")
        }
        
        try {
            startActivity(intent)
        } catch (e: Exception) {
            Toast.makeText(this, "No email app found", Toast.LENGTH_SHORT).show()
        }
    }

    override fun onResume() {
        super.onResume()
        updateMonitorStatus()
    }
}
