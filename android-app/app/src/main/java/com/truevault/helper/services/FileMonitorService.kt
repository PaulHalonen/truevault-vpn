package com.truevault.helper.services

import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.Service
import android.content.Intent
import android.os.Build
import android.os.Environment
import android.os.FileObserver
import android.os.IBinder
import android.widget.Toast
import androidx.core.app.NotificationCompat
import com.truevault.helper.R
import com.truevault.helper.utils.WireGuardHelper
import java.io.File

/**
 * FileMonitorService - Background service to monitor Downloads folder
 * 
 * Watches for .conf.txt files and automatically fixes them to .conf
 * Runs as a foreground service with notification
 */
class FileMonitorService : Service() {

    private var fileObserver: FileObserver? = null
    private lateinit var notificationManager: NotificationManager

    companion object {
        private const val NOTIFICATION_ID = 1001
        private const val CHANNEL_ID = "file_monitor_channel"
        var isRunning = false
    }

    override fun onCreate() {
        super.onCreate()
        notificationManager = getSystemService(NOTIFICATION_SERVICE) as NotificationManager
        createNotificationChannel()
        startForeground(NOTIFICATION_ID, createNotification())
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        startMonitoring()
        isRunning = true
        return START_STICKY
    }

    private fun startMonitoring() {
        // Get Downloads directory
        val downloadsDir = Environment.getExternalStoragePublicDirectory(
            Environment.DIRECTORY_DOWNLOADS
        )

        if (!downloadsDir.exists()) {
            Toast.makeText(
                this,
                "Downloads folder not found",
                Toast.LENGTH_SHORT
            ).show()
            stopSelf()
            return
        }

        // Monitor Downloads folder for file changes
        fileObserver = object : FileObserver(
            downloadsDir.absolutePath,
            CREATE or MOVED_TO
        ) {
            override fun onEvent(event: Int, path: String?) {
                if (path != null && path.endsWith(".conf.txt")) {
                    handleConfTxtFile(File(downloadsDir, path))
                }
            }
        }

        fileObserver?.startWatching()
        
        // Also check for existing .conf.txt files
        checkExistingFiles(downloadsDir)
    }

    private fun checkExistingFiles(directory: File) {
        val confTxtFiles = directory.listFiles { file ->
            file.name.endsWith(".conf.txt")
        }

        confTxtFiles?.forEach { file ->
            handleConfTxtFile(file)
        }
    }

    private fun handleConfTxtFile(file: File) {
        try {
            // Read file content
            val content = file.readText()
            
            // Check if it's a valid WireGuard config
            if (WireGuardHelper.isValidConfig(content)) {
                // Fix the file extension
                val fixedFile = WireGuardHelper.fixConfTxtFile(file)
                
                if (fixedFile != null) {
                    // Show notification
                    showFileFixedNotification(fixedFile.name)
                    
                    // Update foreground notification
                    updateNotification("Fixed: ${fixedFile.name}")
                }
            }
        } catch (e: Exception) {
            e.printStackTrace()
        }
    }

    private fun showFileFixedNotification(fileName: String) {
        val notification = NotificationCompat.Builder(this, CHANNEL_ID)
            .setSmallIcon(R.drawable.ic_logo)
            .setContentTitle("Config File Fixed!")
            .setContentText("Fixed: $fileName")
            .setPriority(NotificationCompat.PRIORITY_HIGH)
            .setAutoCancel(true)
            .build()

        notificationManager.notify(
            fileName.hashCode(),
            notification
        )
    }

    private fun createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val channel = NotificationChannel(
                CHANNEL_ID,
                "File Monitor",
                NotificationManager.IMPORTANCE_LOW
            ).apply {
                description = "Monitors Downloads folder for VPN config files"
                setShowBadge(false)
            }
            notificationManager.createNotificationChannel(channel)
        }
    }

    private fun createNotification(): Notification {
        return NotificationCompat.Builder(this, CHANNEL_ID)
            .setSmallIcon(R.drawable.ic_logo)
            .setContentTitle(getString(R.string.monitor_title))
            .setContentText(getString(R.string.monitor_desc))
            .setPriority(NotificationCompat.PRIORITY_LOW)
            .setOngoing(true)
            .build()
    }

    private fun updateNotification(text: String) {
        val notification = NotificationCompat.Builder(this, CHANNEL_ID)
            .setSmallIcon(R.drawable.ic_logo)
            .setContentTitle(getString(R.string.monitor_title))
            .setContentText(text)
            .setPriority(NotificationCompat.PRIORITY_LOW)
            .setOngoing(true)
            .build()

        notificationManager.notify(NOTIFICATION_ID, notification)
    }

    override fun onDestroy() {
        super.onDestroy()
        fileObserver?.stopWatching()
        isRunning = false
    }

    override fun onBind(intent: Intent?): IBinder? = null
}
