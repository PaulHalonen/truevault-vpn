# TrueVault VPN - FTP Deploy Script
# Uploads all files to vpn.the-truth-publishing.com

$ftpHost = "ftp://the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$localPath = "E:\Documents\GitHub\truevault-vpn"
$remotePath = "/public_html/vpn.the-truth-publishing.com"

# Files/folders to skip
$skipPatterns = @(".git", "node_modules", ".env", "deploy.ps1", "*.log")

function Upload-FtpFile {
    param(
        [string]$LocalFile,
        [string]$RemoteFile
    )
    
    try {
        $uri = "$ftpHost$RemoteFile"
        $webclient = New-Object System.Net.WebClient
        $webclient.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $webclient.UploadFile($uri, $LocalFile)
        Write-Host "  OK: $RemoteFile" -ForegroundColor Green
        return $true
    }
    catch {
        Write-Host "  FAIL: $RemoteFile - $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

function Create-FtpDirectory {
    param([string]$RemoteDir)
    
    try {
        $uri = "$ftpHost$RemoteDir"
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $response = $request.GetResponse()
        $response.Close()
        Write-Host "  DIR: $RemoteDir" -ForegroundColor Cyan
    }
    catch {
        # Directory might already exist, that's OK
    }
}

Write-Host "========================================" -ForegroundColor Yellow
Write-Host "  TrueVault VPN - FTP Deployment" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Yellow
Write-Host ""
Write-Host "Target: $ftpHost$remotePath"
Write-Host "Source: $localPath"
Write-Host ""

# Get all files (excluding .git)
$files = Get-ChildItem -Path $localPath -Recurse -File | Where-Object {
    $_.FullName -notlike "*\.git\*" -and
    $_.FullName -notlike "*\node_modules\*" -and
    $_.Name -ne "deploy.ps1"
}

Write-Host "Found $($files.Count) files to upload" -ForegroundColor Cyan
Write-Host ""

# Collect unique directories
$dirs = @{}
foreach ($file in $files) {
    $relativePath = $file.FullName.Substring($localPath.Length).Replace("\", "/")
    $dirPath = Split-Path $relativePath -Parent
    if ($dirPath -and -not $dirs.ContainsKey($dirPath)) {
        $dirs[$dirPath] = $true
    }
}

# Sort directories by depth (create parent dirs first)
$sortedDirs = $dirs.Keys | Sort-Object { ($_ -split "/").Count }

Write-Host "Creating directories..." -ForegroundColor Yellow
foreach ($dir in $sortedDirs) {
    $remoteDir = "$remotePath$dir"
    Create-FtpDirectory -RemoteDir $remoteDir
}

Write-Host ""
Write-Host "Uploading files..." -ForegroundColor Yellow

$success = 0
$failed = 0

foreach ($file in $files) {
    $relativePath = $file.FullName.Substring($localPath.Length).Replace("\", "/")
    $remoteFile = "$remotePath$relativePath"
    
    if (Upload-FtpFile -LocalFile $file.FullName -RemoteFile $remoteFile) {
        $success++
    } else {
        $failed++
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Yellow
Write-Host "  Deployment Complete!" -ForegroundColor Green
Write-Host "  Success: $success files" -ForegroundColor Green
if ($failed -gt 0) {
    Write-Host "  Failed: $failed files" -ForegroundColor Red
}
Write-Host "========================================" -ForegroundColor Yellow
Write-Host ""
Write-Host "Next steps:"
Write-Host "1. Visit: https://vpn.the-truth-publishing.com/api/config/setup-databases.php"
Write-Host "2. Test: https://vpn.the-truth-publishing.com/"
Write-Host ""
