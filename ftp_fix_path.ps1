# PowerShell FTP - Fix Path and Clean Wrong Folder
$ftpHost = "ftp://the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$correctPath = "/public_html/vpn.the-truth-publishing.com"
$wrongPath = "/vpn.the-truth-publishing.com"
$localPath = "E:\Documents\GitHub\truevault-vpn\website"

$credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)

function Upload-File {
    param($localFile, $remoteFile)
    try {
        $uri = New-Object System.Uri("$ftpHost$remoteFile")
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $request.Credentials = $credentials
        $request.UseBinary = $true
        $request.UsePassive = $true
        
        $fileContent = [System.IO.File]::ReadAllBytes($localFile)
        $request.ContentLength = $fileContent.Length
        
        $stream = $request.GetRequestStream()
        $stream.Write($fileContent, 0, $fileContent.Length)
        $stream.Close()
        
        $response = $request.GetResponse()
        Write-Host "OK: $remoteFile" -ForegroundColor Green
        $response.Close()
        return $true
    } catch {
        Write-Host "FAIL: $remoteFile - $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

function Create-FtpDirectory {
    param($remoteDir)
    try {
        $uri = New-Object System.Uri("$ftpHost$remoteDir")
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $request.Credentials = $credentials
        $request.UsePassive = $true
        $response = $request.GetResponse()
        Write-Host "DIR: $remoteDir" -ForegroundColor Cyan
        $response.Close()
    } catch {
        # Directory exists - OK
    }
}

function Delete-FtpFile {
    param($remoteFile)
    try {
        $uri = New-Object System.Uri("$ftpHost$remoteFile")
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::DeleteFile
        $request.Credentials = $credentials
        $request.UsePassive = $true
        $response = $request.GetResponse()
        Write-Host "DEL: $remoteFile" -ForegroundColor Yellow
        $response.Close()
    } catch {
        # File doesn't exist - OK
    }
}

function Delete-FtpDirectory {
    param($remoteDir)
    try {
        $uri = New-Object System.Uri("$ftpHost$remoteDir")
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::RemoveDirectory
        $request.Credentials = $credentials
        $request.UsePassive = $true
        $response = $request.GetResponse()
        Write-Host "RMDIR: $remoteDir" -ForegroundColor Yellow
        $response.Close()
    } catch {
        Write-Host "Could not remove: $remoteDir" -ForegroundColor Gray
    }
}

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "STEP 1: Cleaning wrong folder..." -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan

# Delete files from wrong location
$wrongFiles = @(
    "$wrongPath/.htaccess",
    "$wrongPath/test.php",
    "$wrongPath/downloads/.htaccess",
    "$wrongPath/downloads/version.json",
    "$wrongPath/downloads/README.md",
    "$wrongPath/downloads/TrueVaultHelper.apk",
    "$wrongPath/dashboard/setup-device.php"
)

foreach ($file in $wrongFiles) {
    Delete-FtpFile $file
}

# Try to remove wrong directories
Delete-FtpDirectory "$wrongPath/downloads"
Delete-FtpDirectory "$wrongPath/dashboard"
Delete-FtpDirectory "$wrongPath"

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "STEP 2: Creating directories..." -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan

# Create all needed directories
$dirs = @(
    "$correctPath/templates",
    "$correctPath/includes",
    "$correctPath/databases",
    "$correctPath/downloads",
    "$correctPath/configs",
    "$correctPath/assets",
    "$correctPath/assets/css",
    "$correctPath/assets/js",
    "$correctPath/assets/images",
    "$correctPath/dashboard",
    "$correctPath/admin",
    "$correctPath/api",
    "$correctPath/api/devices",
    "$correctPath/api/auth",
    "$correctPath/api/billing",
    "$correctPath/api/parental",
    "$correctPath/api/port-forwarding",
    "$correctPath/api/servers",
    "$correctPath/api/support",
    "$correctPath/api/themes",
    "$correctPath/logs",
    "$correctPath/temp"
)

foreach ($dir in $dirs) {
    Create-FtpDirectory $dir
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "STEP 3: Uploading core files..." -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan

# Upload core files
Upload-File "$localPath\.htaccess" "$correctPath/.htaccess"
Upload-File "$localPath\test.php" "$correctPath/test.php"
Upload-File "$localPath\index.php" "$correctPath/index.php"
Upload-File "$localPath\pricing.php" "$correctPath/pricing.php"
Upload-File "$localPath\features.php" "$correctPath/features.php"
Upload-File "$localPath\about.php" "$correctPath/about.php"
Upload-File "$localPath\contact.php" "$correctPath/contact.php"
Upload-File "$localPath\privacy.php" "$correctPath/privacy.php"
Upload-File "$localPath\terms.php" "$correctPath/terms.php"
Upload-File "$localPath\refund.php" "$correctPath/refund.php"
Upload-File "$localPath\login.php" "$correctPath/login.php"

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "STEP 4: Uploading templates..." -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan

Upload-File "$localPath\templates\header.php" "$correctPath/templates/header.php"
Upload-File "$localPath\templates\footer.php" "$correctPath/templates/footer.php"

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "STEP 5: Uploading includes..." -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan

Upload-File "$localPath\includes\content-functions.php" "$correctPath/includes/content-functions.php"
Upload-File "$localPath\includes\Database.php" "$correctPath/includes/Database.php"
Upload-File "$localPath\includes\JWT.php" "$correctPath/includes/JWT.php"
Upload-File "$localPath\includes\WireGuard.php" "$correctPath/includes/WireGuard.php"
Upload-File "$localPath\includes\PayPal.php" "$correctPath/includes/PayPal.php"
Upload-File "$localPath\includes\Email.php" "$correctPath/includes/Email.php"
Upload-File "$localPath\includes\EmailTemplate.php" "$correctPath/includes/EmailTemplate.php"
Upload-File "$localPath\includes\Validator.php" "$correctPath/includes/Validator.php"
Upload-File "$localPath\includes\Bandwidth.php" "$correctPath/includes/Bandwidth.php"
Upload-File "$localPath\includes\Contabo.php" "$correctPath/includes/Contabo.php"
Upload-File "$localPath\includes\FlyIO.php" "$correctPath/includes/FlyIO.php"
Upload-File "$localPath\includes\Failover.php" "$correctPath/includes/Failover.php"
Upload-File "$localPath\includes\CloudBypass.php" "$correctPath/includes/CloudBypass.php"
Upload-File "$localPath\includes\AutomationEngine.php" "$correctPath/includes/AutomationEngine.php"
Upload-File "$localPath\includes\Workflows.php" "$correctPath/includes/Workflows.php"
Upload-File "$localPath\includes\parental-enforcement.php" "$correctPath/includes/parental-enforcement.php"

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "STEP 6: Uploading configs..." -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan

Upload-File "$localPath\configs\config.php" "$correctPath/configs/config.php"

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "STEP 7: Uploading databases setup..." -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan

Upload-File "$localPath\databases\.htaccess" "$correctPath/databases/.htaccess"
Upload-File "$localPath\databases\setup-content.php" "$correctPath/databases/setup-content.php"

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "STEP 8: Uploading downloads..." -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan

Upload-File "$localPath\downloads\.htaccess" "$correctPath/downloads/.htaccess"
Upload-File "$localPath\downloads\version.json" "$correctPath/downloads/version.json"
Upload-File "$localPath\downloads\README.md" "$correctPath/downloads/README.md"
Upload-File "$localPath\downloads\TrueVaultHelper.apk" "$correctPath/downloads/TrueVaultHelper.apk"

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "STEP 9: Uploading dashboard..." -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan

Upload-File "$localPath\dashboard\setup-device.php" "$correctPath/dashboard/setup-device.php"
Upload-File "$localPath\dashboard\index.php" "$correctPath/dashboard/index.php"
Upload-File "$localPath\dashboard\port-forwarding.php" "$correctPath/dashboard/port-forwarding.php"
Upload-File "$localPath\dashboard\parental-controls.php" "$correctPath/dashboard/parental-controls.php"
Upload-File "$localPath\dashboard\device-rules.php" "$correctPath/dashboard/device-rules.php"
Upload-File "$localPath\dashboard\parental-stats.php" "$correctPath/dashboard/parental-stats.php"
Upload-File "$localPath\dashboard\cameras.php" "$correctPath/dashboard/cameras.php"
Upload-File "$localPath\dashboard\recordings.php" "$correctPath/dashboard/recordings.php"
Upload-File "$localPath\dashboard\motion.php" "$correctPath/dashboard/motion.php"
Upload-File "$localPath\dashboard\support.php" "$correctPath/dashboard/support.php"
Upload-File "$localPath\dashboard\subscription-success.php" "$correctPath/dashboard/subscription-success.php"
Upload-File "$localPath\dashboard\subscription-cancelled.php" "$correctPath/dashboard/subscription-cancelled.php"

Write-Host ""
Write-Host "============================================" -ForegroundColor Green
Write-Host "UPLOAD COMPLETE!" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Green
Write-Host ""
Write-Host "Test URL: https://vpn.the-truth-publishing.com/test.php" -ForegroundColor Yellow
Write-Host "Setup DB: https://vpn.the-truth-publishing.com/databases/setup-content.php" -ForegroundColor Yellow
