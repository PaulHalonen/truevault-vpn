# FTP - Copy index.html to root and fix permissions
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

# Upload index.html to ROOT (not just public folder)
Write-Host "Uploading index.html to ROOT..." -ForegroundColor Yellow
$indexLocal = "$localPath\public\index.html"
Upload-FtpFile -localFile $indexLocal -remoteFile "$remotePath/index.html"

# Upload a simpler .htaccess
$simpleHtaccess = @"
# TrueVault VPN
DirectoryIndex index.html index.php

# Enable rewrite engine
RewriteEngine On

# If file exists, serve it directly
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# Route API requests
RewriteRule ^api/(.*)$ api/$1 [L]

# Route to public folder for assets
RewriteRule ^assets/(.*)$ public/assets/$1 [L]

# Route dashboard
RewriteRule ^dashboard/?$ public/dashboard/index.html [L]
RewriteRule ^dashboard/(.*)$ public/dashboard/$1 [L]

# Route admin
RewriteRule ^admin/?$ public/admin/index.html [L]
RewriteRule ^admin/(.*)$ public/admin/$1 [L]

# Route login/register
RewriteRule ^login/?$ public/login.html [L]
RewriteRule ^register/?$ public/register.html [L]
"@

$htaccessPath = "$localPath\simple.htaccess"
$simpleHtaccess | Out-File -FilePath $htaccessPath -Encoding ascii -NoNewline
Upload-FtpFile -localFile $htaccessPath -remoteFile "$remotePath/.htaccess"

Write-Host "`nDone! Try visiting the site now." -ForegroundColor Green
