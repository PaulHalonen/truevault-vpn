package com.truevault.helper

import android.app.*
import android.content.Intent
import android.os.*
import androidx.core.app.NotificationCompat
import java.io.File

class FileMonitorService : Service() {

    private val CHANNEL_ID = "truevault_monitor"
    private val NOTIFICATION_ID = 1
    private var fileObserver: FileObserver? = null
    private val handler = Handler(Looper.getMainLooper())

    override fun onCreate() {
        super.onCreate()
        createNotificationChannel()
        startForeground(NOTIFICATION_ID, createNotification("Monitoring for VPN configs..."))
        startMonitoring()
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        return START_STICKY
    }

    override fun onBind(intent: Intent?): IBinder? = null

    override fun onDestroy() {
        super.onDestroy()
        fileObserver?.stopWatching()
    }

    private fun startMonitoring() {
        val downloadDir = Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_DOWNLOADS)
        
        fileObserver = object : FileObserver(downloadDir.path, CREATE or MOVED_TO or CLOSE_WRITE) {
            override fun onEvent(event: Int, path: String?) {
                if (path == null) return
                
                // Check for .conf.txt files
                if (path.endsWith(".conf.txt") || path.endsWith(".conf (1).txt")) {
                    handler.post {
                        fixConfigFile(File(downloadDir, path))
                    }
                }
            }
        }
        fileObserver?.startWatching()

        // Also scan existing files
        scanExistingFiles(downloadDir)
    }

    private fun scanExistingFiles(dir: File) {
        dir.listFiles()?.forEach { file ->
            if (file.name.endsWith(".conf.txt") || file.name.contains(".conf") && file.name.endsWith(".txt")) {
                fixConfigFile(file)
            }
        }
    }

    private fun fixConfigFile(file: File) {
        try {
            if (!file.exists()) return

            // Generate new name without .txt
            val newName = file.name.replace(".conf.txt", ".conf")
                .replace(".conf (1).txt", "_1.conf")
                .replace(Regex("\\.conf.*\\.txt$"), ".conf")

            val newFile = File(file.parent, newName)
            
            // Don't overwrite if exists
            if (newFile.exists()) {
                val timestamp = System.currentTimeMillis()
                val uniqueName = newName.replace(".conf", "_$timestamp.conf")
                val uniqueFile = File(file.parent, uniqueName)
                file.renameTo(uniqueFile)
                showNotification("Fixed: $uniqueName", "Tap to import to WireGuard")
            } else {
                file.renameTo(newFile)
                showNotification("Fixed: $newName", "Tap to import to WireGuard")
            }
        } catch (e: Exception) {
            e.printStackTrace()
        }
    }

    private fun createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val channel = NotificationChannel(
                CHANNEL_ID,
                "TrueVault Monitor",
                NotificationManager.IMPORTANCE_LOW
            ).apply {
                description = "Monitors for VPN config files"
            }
            val manager = getSystemService(NotificationManager::class.java)
            manager.createNotificationChannel(channel)
        }
    }

    private fun createNotification(text: String): Notification {
        val intent = Intent(this, MainActivity::class.java)
        val pendingIntent = PendingIntent.getActivity(
            this, 0, intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        return NotificationCompat.Builder(this, CHANNEL_ID)
            .setContentTitle("TrueVault Helper")
            .setContentText(text)
            .setSmallIcon(android.R.drawable.ic_menu_upload)
            .setContentIntent(pendingIntent)
            .setOngoing(true)
            .build()
    }

    private fun showNotification(title: String, text: String) {
        val intent = Intent(this, MainActivity::class.java)
        val pendingIntent = PendingIntent.getActivity(
            this, 0, intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        val notification = NotificationCompat.Builder(this, CHANNEL_ID)
            .setContentTitle(title)
            .setContentText(text)
            .setSmallIcon(android.R.drawable.ic_menu_upload)
            .setContentIntent(pendingIntent)
            .setAutoCancel(true)
            .build()

        val manager = getSystemService(NotificationManager::class.java)
        manager.notify(System.currentTimeMillis().toInt(), notification)
    }
}
