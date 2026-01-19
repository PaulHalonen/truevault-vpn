package com.truevault.helper.utils

import android.content.Context
import android.content.Intent
import android.net.Uri
import android.widget.Toast
import androidx.core.content.FileProvider
import java.io.File

/**
 * WireGuardHelper - Utility for handling WireGuard configs
 * 
 * - Validates WireGuard configuration format
 * - Saves configs to app directory
 * - Imports configs to WireGuard app
 * - Fixes .conf.txt file extensions
 */
object WireGuardHelper {

    private const val WIREGUARD_PACKAGE = "com.wireguard.android"
    
    /**
     * Check if config text is a valid WireGuard configuration
     */
    fun isValidConfig(configText: String): Boolean {
        return configText.contains("[Interface]") &&
               configText.contains("[Peer]") &&
               (configText.contains("PrivateKey") || configText.contains("Address"))
    }

    /**
     * Save WireGuard config to file
     * Returns true if successful
     */
    fun saveConfig(context: Context, configText: String): Boolean {
        return try {
            val fileName = "truevault_${System.currentTimeMillis()}.conf"
            val file = File(context.filesDir, fileName)
            file.writeText(configText)
            true
        } catch (e: Exception) {
            e.printStackTrace()
            false
        }
    }

    /**
     * Import config to WireGuard app
     * Opens file picker intent for WireGuard
     */
    fun importToWireGuard(context: Context) {
        // Check if WireGuard is installed
        if (!isWireGuardInstalled(context)) {
            showWireGuardInstallPrompt(context)
            return
        }

        // Get most recent config file
        val configFile = getMostRecentConfig(context)
        
        if (configFile == null) {
            Toast.makeText(
                context,
                "No configuration file found",
                Toast.LENGTH_SHORT
            ).show()
            return
        }

        // Create file URI using FileProvider
        val uri = FileProvider.getUriForFile(
            context,
            "${context.packageName}.fileprovider",
            configFile
        )

        // Create import intent
        val intent = Intent(Intent.ACTION_VIEW).apply {
            setDataAndType(uri, "application/x-wireguard-config")
            flags = Intent.FLAG_GRANT_READ_URI_PERMISSION
            setPackage(WIREGUARD_PACKAGE)
        }

        try {
            context.startActivity(intent)
        } catch (e: Exception) {
            // Fallback: Open share sheet
            val shareIntent = Intent(Intent.ACTION_SEND).apply {
                type = "application/x-wireguard-config"
                putExtra(Intent.EXTRA_STREAM, uri)
                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
            }
            context.startActivity(Intent.createChooser(shareIntent, "Import to WireGuard"))
        }
    }

    /**
     * Fix .conf.txt file by renaming to .conf
     * Returns new file path if successful
     */
    fun fixConfTxtFile(file: File): File? {
        return try {
            if (file.name.endsWith(".conf.txt")) {
                val newName = file.name.replace(".conf.txt", ".conf")
                val newFile = File(file.parent, newName)
                
                if (file.renameTo(newFile)) {
                    newFile
                } else {
                    null
                }
            } else {
                file
            }
        } catch (e: Exception) {
            e.printStackTrace()
            null
        }
    }

    /**
     * Check if WireGuard app is installed
     */
    fun isWireGuardInstalled(context: Context): Boolean {
        return try {
            context.packageManager.getPackageInfo(WIREGUARD_PACKAGE, 0)
            true
        } catch (e: Exception) {
            false
        }
    }

    /**
     * Prompt user to install WireGuard
     */
    private fun showWireGuardInstallPrompt(context: Context) {
        Toast.makeText(
            context,
            "WireGuard is not installed. Opening Play Store...",
            Toast.LENGTH_LONG
        ).show()

        // Open WireGuard on Play Store
        val intent = Intent(Intent.ACTION_VIEW).apply {
            data = Uri.parse("market://details?id=$WIREGUARD_PACKAGE")
        }

        try {
            context.startActivity(intent)
        } catch (e: Exception) {
            // Fallback to web browser
            val webIntent = Intent(Intent.ACTION_VIEW).apply {
                data = Uri.parse("https://play.google.com/store/apps/details?id=$WIREGUARD_PACKAGE")
            }
            context.startActivity(webIntent)
        }
    }

    /**
     * Get most recently created config file
     */
    private fun getMostRecentConfig(context: Context): File? {
        val files = context.filesDir.listFiles { file ->
            file.name.endsWith(".conf")
        }

        return files?.maxByOrNull { it.lastModified() }
    }

    /**
     * Get all config files in app directory
     */
    fun getAllConfigFiles(context: Context): List<File> {
        val files = context.filesDir.listFiles { file ->
            file.name.endsWith(".conf") || file.name.endsWith(".conf.txt")
        }

        return files?.toList() ?: emptyList()
    }

    /**
     * Delete old config files (keep only last 5)
     */
    fun cleanupOldConfigs(context: Context) {
        val configs = getAllConfigFiles(context)
            .sortedByDescending { it.lastModified() }

        // Delete all except last 5
        configs.drop(5).forEach { file ->
            try {
                file.delete()
            } catch (e: Exception) {
                e.printStackTrace()
            }
        }
    }
}
