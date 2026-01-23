# PowerShell FTP Upload Script for Part 12
# Uploads database-driven landing pages to server

$ftpHost = "ftp://the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$remotePath = "/vpn.the-truth-publishing.com"
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

Write-Host "Starting FTP Upload for Part 12 - Landing Pages..." -ForegroundColor Cyan
Write-Host ""

# Create templates directory
Create-FtpDirectory "$remotePath/templates"

# Upload files
Write-Host "Uploading landing pages..." -ForegroundColor Cyan

# Templates
Upload-File "$localPath\templates\header.php" "$remotePath/templates/header.php"
Upload-File "$localPath\templates\footer.php" "$remotePath/templates/footer.php"

# Landing pages
Upload-File "$localPath\index.php" "$remotePath/index.php"
Upload-File "$localPath\pricing.php" "$remotePath/pricing.php"
Upload-File "$localPath\features.php" "$remotePath/features.php"
Upload-File "$localPath\about.php" "$remotePath/about.php"
Upload-File "$localPath\contact.php" "$remotePath/contact.php"
Upload-File "$localPath\privacy.php" "$remotePath/privacy.php"
Upload-File "$localPath\terms.php" "$remotePath/terms.php"
Upload-File "$localPath\refund.php" "$remotePath/refund.php"

# Also upload content-functions.php and setup-content.php
Upload-File "$localPath\includes\content-functions.php" "$remotePath/includes/content-functions.php"
Upload-File "$localPath\databases\setup-content.php" "$remotePath/databases/setup-content.php"

Write-Host ""
Write-Host "Upload Complete!" -ForegroundColor Green
Write-Host ""
Write-Host "IMPORTANT: Run database setup at:" -ForegroundColor Yellow
Write-Host "https://vpn.the-truth-publishing.com/databases/setup-content.php" -ForegroundColor Cyan
