# FTP Upload - Pricing Comparison Fix
# January 23, 2026

$ftpHost = "the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$remotePath = "/public_html/vpn.the-truth-publishing.com"
$localPath = "E:\Documents\GitHub\truevault-vpn\website"

Write-Host "===== Uploading Corrected Files =====" -ForegroundColor Cyan

# Load .NET FTP
Add-Type -AssemblyName System.Net

function Upload-File($local, $remote) {
    try {
        $uri = "ftp://${ftpHost}${remote}"
        $ftp = [System.Net.FtpWebRequest]::Create($uri)
        $ftp.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $ftp.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $ftp.UseBinary = $true
        $ftp.UsePassive = $true
        
        $content = [System.IO.File]::ReadAllBytes($local)
        $ftp.ContentLength = $content.Length
        
        $stream = $ftp.GetRequestStream()
        $stream.Write($content, 0, $content.Length)
        $stream.Close()
        
        $response = $ftp.GetResponse()
        Write-Host "  OK: $remote" -ForegroundColor Green
        $response.Close()
        return $true
    } catch {
        Write-Host "  FAIL: $remote - $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

# Upload setup.php (corrected with competitor data)
Write-Host "`n1. Uploading setup.php (corrected)..." -ForegroundColor Yellow
Upload-File "$localPath\setup.php" "$remotePath/setup.php"

# Upload pricing-comparison.php (new)
Write-Host "`n2. Uploading pricing-comparison.php (new)..." -ForegroundColor Yellow
Upload-File "$localPath\pricing-comparison.php" "$remotePath/pricing-comparison.php"

Write-Host "`n===== Upload Complete =====" -ForegroundColor Green
Write-Host "`nNEXT STEPS:" -ForegroundColor Cyan
Write-Host "1. Run: https://vpn.the-truth-publishing.com/setup.php"
Write-Host "2. This will recreate database with CORRECT data"
Write-Host "3. Delete setup.php after running"
Write-Host "4. Test: https://vpn.the-truth-publishing.com/pricing-comparison.php"
