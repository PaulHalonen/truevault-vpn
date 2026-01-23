# PowerShell FTP Upload Script for Task 10.9
# Uploads Android APK bundle files to server

$ftpHost = "ftp://the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$remotePath = "/vpn.the-truth-publishing.com"
$localPath = "E:\Documents\GitHub\truevault-vpn\website"

# Create FTP credentials
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
        Write-Host "Uploaded: $remoteFile" -ForegroundColor Green
        $response.Close()
        return $true
    } catch {
        Write-Host "Failed: $remoteFile - $($_.Exception.Message)" -ForegroundColor Red
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
        Write-Host "Created directory: $remoteDir" -ForegroundColor Cyan
        $response.Close()
    } catch {
        Write-Host "Directory may exist: $remoteDir" -ForegroundColor Yellow
    }
}

Write-Host "Starting FTP Upload for Task 10.9..." -ForegroundColor Cyan
Write-Host ""

# Create downloads directory
Create-FtpDirectory "$remotePath/downloads"

# Upload files
Write-Host "Uploading files..." -ForegroundColor Cyan

Upload-File "$localPath\downloads\.htaccess" "$remotePath/downloads/.htaccess"
Upload-File "$localPath\downloads\version.json" "$remotePath/downloads/version.json"
Upload-File "$localPath\downloads\README.md" "$remotePath/downloads/README.md"
Upload-File "$localPath\downloads\TrueVaultHelper.apk" "$remotePath/downloads/TrueVaultHelper.apk"
Upload-File "$localPath\dashboard\setup-device.php" "$remotePath/dashboard/setup-device.php"

Write-Host ""
Write-Host "Upload Complete!" -ForegroundColor Green
