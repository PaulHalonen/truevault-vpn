package com.truevault.helper

import android.content.Intent
import android.content.pm.PackageManager
import android.net.Uri
import android.os.Bundle
import android.os.Environment
import android.widget.Toast
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import androidx.recyclerview.widget.LinearLayoutManager
import com.google.zxing.BinaryBitmap
import com.google.zxing.MultiFormatReader
import com.google.zxing.RGBLuminanceSource
import com.google.zxing.common.HybridBinarizer
import com.journeyapps.barcodescanner.ScanContract
import com.journeyapps.barcodescanner.ScanOptions
import com.permissionx.guolindev.PermissionX
import com.truevault.helper.databinding.ActivityMainBinding
import android.graphics.BitmapFactory
import android.Manifest
import android.os.Build
import android.provider.Settings
import java.io.File
import kotlinx.coroutines.*

class MainActivity : AppCompatActivity() {

    private lateinit var binding: ActivityMainBinding
    private var autoFixEnabled = true  // Default to ON
    private lateinit var configAdapter: ConfigAdapter
    private val configList = mutableListOf<ConfigFile>()
    private var scanJob: Job? = null

    // Image picker result
    private val pickImage = registerForActivityResult(ActivityResultContracts.GetContent()) { uri ->
        uri?.let { scanQRFromImage(it) }
    }

    // Camera scanner result
    private val scanQR = registerForActivityResult(ScanContract()) { result ->
        if (result.contents != null) {
            handleQRContent(result.contents)
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)

        setupUI()
        setupRecyclerView()
        checkWireGuardInstalled()
        
        // Set auto-fix to ON by default
        binding.switchAutoFix.isChecked = true
        autoFixEnabled = true
        
        // Auto-scan on launch
        requestStoragePermissionAndScan()
    }

    override fun onResume() {
        super.onResume()
        // Rescan when app comes back to foreground
        if (hasStoragePermission()) {
            scanEntireDevice()
        }
    }

    private fun setupUI() {
        // Close button (X in header)
        binding.btnClose.setOnClickListener {
            finishAndRemoveTask()
        }
        
        // Exit button at bottom
        binding.btnExit.setOnClickListener {
            finishAndRemoveTask()
        }
        
        // Refresh/Scan button
        binding.btnRefresh.setOnClickListener {
            if (hasStoragePermission()) {
                scanEntireDevice()
            } else {
                requestStoragePermissionAndScan()
            }
        }

        // Scan from screenshot button
        binding.btnScanScreenshot.setOnClickListener {
            requestStoragePermission {
                pickImage.launch("image/*")
            }
        }

        // Scan with camera button
        binding.btnScanCamera.setOnClickListener {
            requestCameraPermission {
                val options = ScanOptions().apply {
                    setDesiredBarcodeFormats(ScanOptions.QR_CODE)
                    setPrompt("Point at VPN QR code")
                    setCameraId(0)
                    setBeepEnabled(true)
                    setBarcodeImageEnabled(true)
                    setOrientationLocked(false)
                }
                scanQR.launch(options)
            }
        }

        // Auto-fix toggle
        binding.switchAutoFix.setOnCheckedChangeListener { _, isChecked ->
            autoFixEnabled = isChecked
            if (isChecked) {
                startFileMonitorService()
                binding.tvAutoFixStatus.text = "✅ Auto-fix enabled"
                binding.tvAutoFixStatus.visibility = android.view.View.VISIBLE
            } else {
                stopFileMonitorService()
                binding.tvAutoFixStatus.text = "Auto-fix is disabled"
                binding.tvAutoFixStatus.visibility = android.view.View.GONE
            }
        }

        // Install WireGuard button
        binding.btnInstallWireGuard.setOnClickListener {
            openPlayStore("com.wireguard.android")
        }

        // Open Dashboard button
        binding.btnOpenDashboard.setOnClickListener {
            openUrl("https://vpn.the-truth-publishing.com")
        }
    }

    private fun setupRecyclerView() {
        configAdapter = ConfigAdapter(configList) { config ->
            importConfigToWireGuard(config)
        }
        binding.rvConfigs.apply {
            layoutManager = LinearLayoutManager(this@MainActivity)
            adapter = configAdapter
        }
    }

    private fun requestStoragePermissionAndScan() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
            // Android 11+ needs MANAGE_EXTERNAL_STORAGE for full access
            if (!Environment.isExternalStorageManager()) {
                try {
                    val intent = Intent(Settings.ACTION_MANAGE_APP_ALL_FILES_ACCESS_PERMISSION)
                    intent.data = Uri.parse("package:$packageName")
                    startActivity(intent)
                    showToast("Please enable 'All files access' and return")
                } catch (e: Exception) {
                    val intent = Intent(Settings.ACTION_MANAGE_ALL_FILES_ACCESS_PERMISSION)
                    startActivity(intent)
                }
            } else {
                scanEntireDevice()
            }
        } else if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            PermissionX.init(this)
                .permissions(Manifest.permission.READ_MEDIA_IMAGES)
                .request { allGranted, _, _ ->
                    if (allGranted) scanEntireDevice()
                }
        } else {
            PermissionX.init(this)
                .permissions(
                    Manifest.permission.READ_EXTERNAL_STORAGE,
                    Manifest.permission.WRITE_EXTERNAL_STORAGE
                )
                .request { allGranted, _, _ ->
                    if (allGranted) scanEntireDevice()
                }
        }
    }

    private fun hasStoragePermission(): Boolean {
        return if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
            Environment.isExternalStorageManager()
        } else {
            checkSelfPermission(Manifest.permission.READ_EXTERNAL_STORAGE) == PackageManager.PERMISSION_GRANTED
        }
    }

    private fun scanEntireDevice() {
        // Cancel any existing scan
        scanJob?.cancel()
        
        // Show scanning status
        runOnUiThread {
            binding.tvScanStatus.visibility = android.view.View.VISIBLE
            binding.tvScanStatus.text = "🔍 Scanning entire device..."
            binding.btnRefresh.isEnabled = false
            binding.btnRefresh.text = "⏳"
        }
        
        scanJob = CoroutineScope(Dispatchers.IO).launch {
            val foundConfigs = mutableListOf<ConfigFile>()
            var scannedDirs = 0
            
            // Start from external storage root
            val storageRoot = Environment.getExternalStorageDirectory()
            
            // Recursively scan all directories
            scanDirectory(storageRoot, foundConfigs) { currentDir ->
                scannedDirs++
                if (scannedDirs % 50 == 0) {
                    runOnUiThread {
                        binding.tvScanStatus.text = "🔍 Scanning... ($scannedDirs folders, ${foundConfigs.size} found)"
                    }
                }
            }
            
            // Also check internal app directories
            val internalDirs = listOf(
                filesDir,
                cacheDir,
                getExternalFilesDir(null)
            )
            internalDirs.filterNotNull().forEach { dir ->
                scanDirectory(dir, foundConfigs) {}
            }
            
            // Update UI with results
            runOnUiThread {
                binding.tvScanStatus.visibility = android.view.View.GONE
                binding.btnRefresh.isEnabled = true
                binding.btnRefresh.text = "🔄 Scan"
                
                if (foundConfigs.isEmpty()) {
                    binding.tvNoConfigs.visibility = android.view.View.VISIBLE
                    binding.rvConfigs.visibility = android.view.View.GONE
                    binding.tvNoConfigs.text = "No .conf or .conf.txt files found"
                    binding.tvStatus.text = "Scanned $scannedDirs folders - no configs found"
                } else {
                    binding.tvNoConfigs.visibility = android.view.View.GONE
                    binding.rvConfigs.visibility = android.view.View.VISIBLE
                    binding.tvStatus.text = "✅ Found ${foundConfigs.size} config file(s)"
                    
                    // Sort by most recent first
                    foundConfigs.sortByDescending { it.file.lastModified() }
                    configAdapter.updateConfigs(foundConfigs)
                }
            }
        }
    }

    private fun scanDirectory(dir: File, foundConfigs: MutableList<ConfigFile>, onProgress: (String) -> Unit) {
        if (!dir.exists() || !dir.isDirectory || !dir.canRead()) return
        
        // Skip certain directories to speed up scan
        val skipDirs = listOf("Android/data", "Android/obb", ".cache", ".thumbnails", "DCIM/.thumbnails")
        if (skipDirs.any { dir.absolutePath.contains(it) }) return
        
        onProgress(dir.name)
        
        try {
            dir.listFiles()?.forEach { file ->
                if (file.isDirectory) {
                    scanDirectory(file, foundConfigs, onProgress)
                } else {
                    processFile(file, foundConfigs)
                }
            }
        } catch (e: Exception) {
            // Skip directories we can't read
        }
    }

    private fun processFile(file: File, foundConfigs: MutableList<ConfigFile>) {
        val name = file.name.lowercase()
        
        // Find .conf.txt files and auto-fix them
        if (name.endsWith(".conf.txt")) {
            if (autoFixEnabled) {
                val newFile = File(file.parent, file.name.replace(".conf.txt", ".conf"))
                try {
                    if (file.renameTo(newFile)) {
                        synchronized(foundConfigs) {
                            foundConfigs.add(ConfigFile(
                                file = newFile,
                                name = newFile.name,
                                wasFixed = true,
                                status = "✅ Fixed! ${getShortPath(newFile)}"
                            ))
                        }
                        return
                    }
                } catch (e: Exception) { }
            }
            // Couldn't fix or auto-fix disabled
            synchronized(foundConfigs) {
                foundConfigs.add(ConfigFile(
                    file = file,
                    name = file.name,
                    wasFixed = false,
                    status = "⚠️ Needs rename: ${getShortPath(file)}"
                ))
            }
        }
        // Find already-correct .conf files
        else if (name.endsWith(".conf")) {
            try {
                val content = file.readText(Charsets.UTF_8)
                if (content.contains("[Interface]") || content.contains("[Peer]")) {
                    synchronized(foundConfigs) {
                        foundConfigs.add(ConfigFile(
                            file = file,
                            name = file.name,
                            wasFixed = false,
                            status = "📂 ${getShortPath(file)}"
                        ))
                    }
                }
            } catch (e: Exception) {
                // Skip files we can't read
            }
        }
    }

    private fun getShortPath(file: File): String {
        val path = file.absolutePath
        val storage = Environment.getExternalStorageDirectory().absolutePath
        return if (path.startsWith(storage)) {
            path.removePrefix(storage).trimStart('/')
        } else {
            file.parentFile?.name ?: ""
        }
    }

    private fun importConfigToWireGuard(config: ConfigFile) {
        if (!isAppInstalled("com.wireguard.android")) {
            showToast("Please install WireGuard first")
            openPlayStore("com.wireguard.android")
            return
        }

        try {
            // Copy to cache with .conf extension to ensure WireGuard accepts it
            val cacheFile = File(cacheDir, config.name.replace(".conf.txt", ".conf"))
            config.file.copyTo(cacheFile, overwrite = true)

            val uri = androidx.core.content.FileProvider.getUriForFile(
                this,
                "${packageName}.fileprovider",
                cacheFile
            )

            val intent = Intent(Intent.ACTION_VIEW).apply {
                setDataAndType(uri, "application/octet-stream")
                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
                setPackage("com.wireguard.android")
            }
            startActivity(intent)
            
            binding.tvStatus.text = "✅ Sent ${config.name} to WireGuard!"
            showToast("Config sent to WireGuard!")
        } catch (e: Exception) {
            showToast("Error: ${e.message}")
            binding.tvStatus.text = "❌ Failed to import: ${e.message}"
        }
    }

    private fun checkWireGuardInstalled() {
        val installed = isAppInstalled("com.wireguard.android")
        binding.tvWireGuardStatus.text = if (installed) {
            "✅ WireGuard is installed"
        } else {
            "❌ WireGuard not installed - tap below to install"
        }
        binding.btnInstallWireGuard.visibility = if (installed) {
            android.view.View.GONE
        } else {
            android.view.View.VISIBLE
        }
    }

    private fun isAppInstalled(packageName: String): Boolean {
        return try {
            packageManager.getPackageInfo(packageName, 0)
            true
        } catch (e: PackageManager.NameNotFoundException) {
            false
        }
    }

    private fun requestStoragePermission(onGranted: () -> Unit) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            PermissionX.init(this)
                .permissions(Manifest.permission.READ_MEDIA_IMAGES)
                .request { allGranted, _, _ ->
                    if (allGranted) onGranted()
                    else showToast("Storage permission required")
                }
        } else {
            PermissionX.init(this)
                .permissions(Manifest.permission.READ_EXTERNAL_STORAGE)
                .request { allGranted, _, _ ->
                    if (allGranted) onGranted()
                    else showToast("Storage permission required")
                }
        }
    }

    private fun requestCameraPermission(onGranted: () -> Unit) {
        PermissionX.init(this)
            .permissions(Manifest.permission.CAMERA)
            .request { allGranted, _, _ ->
                if (allGranted) onGranted()
                else showToast("Camera permission required")
            }
    }

    private fun scanQRFromImage(uri: Uri) {
        try {
            val inputStream = contentResolver.openInputStream(uri)
            val bitmap = BitmapFactory.decodeStream(inputStream)
            inputStream?.close()

            if (bitmap == null) {
                showToast("Could not load image")
                return
            }

            val width = bitmap.width
            val height = bitmap.height
            val pixels = IntArray(width * height)
            bitmap.getPixels(pixels, 0, width, 0, 0, width, height)

            val source = RGBLuminanceSource(width, height, pixels)
            val binaryBitmap = BinaryBitmap(HybridBinarizer(source))

            val reader = MultiFormatReader()
            val result = reader.decode(binaryBitmap)

            handleQRContent(result.text)
        } catch (e: Exception) {
            showToast("No QR code found in image")
            binding.tvStatus.text = "❌ No QR code found. Try a clearer screenshot."
        }
    }

    private fun handleQRContent(content: String) {
        binding.tvStatus.text = "✅ QR code scanned successfully!"

        // Check if it's a WireGuard config
        if (content.contains("[Interface]") || content.contains("[Peer]")) {
            // Save and offer to import
            saveAndImportConfig(content)
        } else {
            showToast("This doesn't appear to be a VPN config")
            binding.tvStatus.text = "⚠️ Invalid QR code - not a VPN config"
        }
    }

    private fun saveAndImportConfig(configContent: String) {
        try {
            // Save config to cache
            val configFile = File(cacheDir, "truevault.conf")
            configFile.writeText(configContent)

            // Try to import to WireGuard
            if (isAppInstalled("com.wireguard.android")) {
                val uri = androidx.core.content.FileProvider.getUriForFile(
                    this,
                    "${packageName}.fileprovider",
                    configFile
                )

                val intent = Intent(Intent.ACTION_VIEW).apply {
                    setDataAndType(uri, "application/octet-stream")
                    addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
                    setPackage("com.wireguard.android")
                }
                startActivity(intent)
                binding.tvStatus.text = "✅ Config sent to WireGuard!"
            } else {
                showToast("Please install WireGuard first")
                binding.tvStatus.text = "⚠️ Install WireGuard to import config"
            }
        } catch (e: Exception) {
            showToast("Error importing config: ${e.message}")
        }
    }

    private fun startFileMonitorService() {
        val intent = Intent(this, FileMonitorService::class.java)
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            startForegroundService(intent)
        } else {
            startService(intent)
        }
    }

    private fun stopFileMonitorService() {
        val intent = Intent(this, FileMonitorService::class.java)
        stopService(intent)
    }

    private fun openPlayStore(packageName: String) {
        try {
            startActivity(Intent(Intent.ACTION_VIEW, Uri.parse("market://details?id=$packageName")))
        } catch (e: Exception) {
            startActivity(Intent(Intent.ACTION_VIEW, Uri.parse("https://play.google.com/store/apps/details?id=$packageName")))
        }
    }

    private fun openUrl(url: String) {
        startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url)))
    }

    private fun showToast(message: String) {
        Toast.makeText(this, message, Toast.LENGTH_SHORT).show()
    }
    
    override fun onDestroy() {
        super.onDestroy()
        scanJob?.cancel()
    }
}
