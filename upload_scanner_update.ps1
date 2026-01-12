# Upload new scanner and connect pages
$ftpHost = "the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$remotePath = "/vpn.the-truth-publishing.com"
$localPath = "E:\Documents\GitHub\truevault-vpn"

# Load .NET FTP classes
Add-Type -AssemblyName System.Net

# Files to upload
$files = @(
    @{ local = "$localPath\public\dashboard\scanner.html"; remote = "$remotePath/public/dashboard/scanner.html" },
    @{ local = "$localPath\public\dashboard\connect.html"; remote = "$remotePath/public/dashboard/connect.html" },
    @{ local = "$localPath\public\assets\js\app.js"; remote = "$remotePath/public/assets/js/app.js" },
    @{ local = "$localPath\public\assets\js\theme-loader.js"; remote = "$remotePath/public/assets/js/theme-loader.js" },
    @{ local = "$localPath\api\vpn\servers.php"; remote = "$remotePath/api/vpn/servers.php" },
    @{ local = "$localPath\api\vpn\connect.php"; remote = "$remotePath/api/vpn/connect.php" },
    @{ local = "$localPath\api\theme\index.php"; remote = "$remotePath/api/theme/index.php" },
    @{ local = "$localPath\api\cameras\sync.php"; remote = "$remotePath/api/cameras/sync.php" },
    @{ local = "$localPath\public\downloads\index.html"; remote = "$remotePath/public/downloads/index.html" },
    @{ local = "$localPath\public\downloads\scanner\truevault_scanner.py"; remote = "$remotePath/public/downloads/scanner/truevault_scanner.py" },
    @{ local = "$localPath\public\downloads\scanner\run_scanner.bat"; remote = "$remotePath/public/downloads/scanner/run_scanner.bat" },
    @{ local = "$localPath\public\downloads\scanner\run_scanner.sh"; remote = "$remotePath/public/downloads/scanner/run_scanner.sh" }
)

foreach ($file in $files) {
    Write-Host "Uploading $($file.local)..."
    try {
        $ftp = [System.Net.FtpWebRequest]::Create("ftp://$ftpHost$($file.remote)")
        $ftp.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $ftp.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $ftp.UseBinary = $true
        $ftp.UsePassive = $true
        
        $content = [System.IO.File]::ReadAllBytes($file.local)
        $ftp.ContentLength = $content.Length
        
        $stream = $ftp.GetRequestStream()
        $stream.Write($content, 0, $content.Length)
        $stream.Close()
        
        $response = $ftp.GetResponse()
        Write-Host "  OK: $($response.StatusDescription)"
        $response.Close()
    } catch {
        Write-Host "  ERROR: $_" -ForegroundColor Red
    }
}

Write-Host "`nDone uploading files!"
