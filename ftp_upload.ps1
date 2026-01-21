# FTP Upload Script for TrueVault VPN
$ftpHost = "ftp://the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$remotePath = "/public_html/vpn.the-truth-publishing.com"
$localPath = "E:\Documents\GitHub\truevault-vpn\website"

# Create credentials
$ftpCred = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)

# Function to create FTP directory
function Create-FtpDirectory($uri) {
    try {
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $request.Credentials = $ftpCred
        $response = $request.GetResponse()
        $response.Close()
        Write-Host "Created: $uri"
    } catch {
        # Directory might already exist - that's OK
    }
}

# Function to upload file
function Upload-FtpFile($localFile, $remoteUri) {
    try {
        $request = [System.Net.FtpWebRequest]::Create($remoteUri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $request.Credentials = $ftpCred
        $request.UseBinary = $true
        
        $content = [System.IO.File]::ReadAllBytes($localFile)
        $request.ContentLength = $content.Length
        
        $stream = $request.GetRequestStream()
        $stream.Write($content, 0, $content.Length)
        $stream.Close()
        
        $response = $request.GetResponse()
        $response.Close()
        Write-Host "Uploaded: $localFile"
        return $true
    } catch {
        Write-Host "Error uploading $localFile : $_"
        return $false
    }
}

Write-Host "Starting FTP upload..."
Write-Host "Local: $localPath"
Write-Host "Remote: $ftpHost$remotePath"
Write-Host ""

# Create directories
$dirs = @(
    "/api",
    "/includes", 
    "/assets",
    "/assets/css",
    "/assets/js",
    "/assets/images",
    "/admin",
    "/dashboard",
    "/databases",
    "/logs",
    "/configs",
    "/temp"
)

foreach ($dir in $dirs) {
    $fullUri = "$ftpHost$remotePath$dir"
    Create-FtpDirectory $fullUri
}

Write-Host ""
Write-Host "Uploading files..."

# Upload .htaccess (root)
Upload-FtpFile "$localPath\.htaccess" "$ftpHost$remotePath/.htaccess"

# Upload config.php
Upload-FtpFile "$localPath\configs\config.php" "$ftpHost$remotePath/configs/config.php"

# Upload databases/.htaccess
Upload-FtpFile "$localPath\databases\.htaccess" "$ftpHost$remotePath/databases/.htaccess"

Write-Host ""
Write-Host "Upload complete!"
