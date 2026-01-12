# FTP Upload Script - Upload missing files
$ftpHost = "ftp://the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$localPath = "E:\Documents\GitHub\truevault-vpn"
$remotePath = "/public_html/vpn.the-truth-publishing.com"

function Upload-FtpFile {
    param($localFile, $remoteFile)
    
    try {
        $uri = "$ftpHost$remoteFile"
        Write-Host "Uploading: $localFile -> $remoteFile" -ForegroundColor Cyan
        
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $request.UseBinary = $true
        $request.UsePassive = $true
        
        $fileContent = [System.IO.File]::ReadAllBytes($localFile)
        $request.ContentLength = $fileContent.Length
        
        $requestStream = $request.GetRequestStream()
        $requestStream.Write($fileContent, 0, $fileContent.Length)
        $requestStream.Close()
        
        $response = $request.GetResponse()
        Write-Host "  Success: $($response.StatusDescription)" -ForegroundColor Green
        $response.Close()
        return $true
    } catch {
        Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

# Check public folder contents
Write-Host "`nChecking public folder contents..." -ForegroundColor Cyan
try {
    $request = [System.Net.FtpWebRequest]::Create("$ftpHost$remotePath/public/")
    $request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
    $request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $response = $request.GetResponse()
    $reader = New-Object System.IO.StreamReader($response.GetResponseStream())
    $listing = $reader.ReadToEnd()
    $reader.Close()
    $response.Close()
    Write-Host "public/ folder contents:" -ForegroundColor Green
    Write-Host $listing
} catch {
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
}

# Upload .htaccess to root
Write-Host "`nUploading .htaccess file..." -ForegroundColor Yellow
$htaccessLocal = "$localPath\.htaccess"
if (Test-Path $htaccessLocal) {
    Upload-FtpFile -localFile $htaccessLocal -remoteFile "$remotePath/.htaccess"
} else {
    Write-Host ".htaccess not found locally!" -ForegroundColor Red
}

# Check if index.html exists in public
Write-Host "`nChecking for index.html..." -ForegroundColor Cyan
$indexLocal = "$localPath\public\index.html"
if (Test-Path $indexLocal) {
    Write-Host "index.html found locally, uploading to public/" -ForegroundColor Green
    Upload-FtpFile -localFile $indexLocal -remoteFile "$remotePath/public/index.html"
}
