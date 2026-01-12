# TrueVault VPN - FTP Deploy Script v2
# Creates directories first, then uploads files

$ftpHost = "ftp://the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$localPath = "E:\Documents\GitHub\truevault-vpn"
$remotePath = "/public_html/vpn.the-truth-publishing.com"

$creds = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)

function Create-FtpDirectory {
    param([string]$Path)
    
    try {
        $uri = "$ftpHost$Path/"
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $request.Credentials = $creds
        $request.UseBinary = $true
        $request.UsePassive = $true
        $response = $request.GetResponse()
        $response.Close()
        Write-Host "  [DIR] $Path" -ForegroundColor Cyan
        return $true
    }
    catch {
        # Directory exists or other error - try anyway
        return $false
    }
}

function Upload-File {
    param(
        [string]$LocalFile,
        [string]$RemotePath
    )
    
    try {
        $uri = "$ftpHost$RemotePath"
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $request.Credentials = $creds
        $request.UseBinary = $true
        $request.UsePassive = $true
        
        $fileContent = [System.IO.File]::ReadAllBytes($LocalFile)
        $request.ContentLength = $fileContent.Length
        
        $requestStream = $request.GetRequestStream()
        $requestStream.Write($fileContent, 0, $fileContent.Length)
        $requestStream.Close()
        
        $response = $request.GetResponse()
        $response.Close()
        
        Write-Host "  [OK] $RemotePath" -ForegroundColor Green
        return $true
    }
    catch {
        Write-Host "  [FAIL] $RemotePath - $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

Write-Host "========================================" -ForegroundColor Yellow
Write-Host "  TrueVault VPN - FTP Deployment v2" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Yellow
Write-Host ""
Write-Host "Target: $ftpHost$remotePath"
Write-Host ""

# Get all files (excluding .git)
$files = Get-ChildItem -Path $localPath -Recurse -File | Where-Object {
    $_.FullName -notlike "*\.git\*" -and
    $_.FullName -notlike "*\node_modules\*" -and
    $_.Name -ne "deploy.ps1" -and
    $_.Name -ne "deploy2.ps1"
}

Write-Host "Found $($files.Count) files to upload" -ForegroundColor Cyan
Write-Host ""

# Collect and sort directories
$allDirs = @{}
foreach ($file in $files) {
    $relativePath = $file.FullName.Substring($localPath.Length).Replace("\", "/")
    $parts = $relativePath.Split("/") | Where-Object { $_ }
    
    # Build incremental paths
    $currentPath = $remotePath
    for ($i = 0; $i -lt ($parts.Count - 1); $i++) {
        $currentPath = "$currentPath/$($parts[$i])"
        if (-not $allDirs.ContainsKey($currentPath)) {
            $allDirs[$currentPath] = $true
        }
    }
}

# Sort by depth
$sortedDirs = $allDirs.Keys | Sort-Object { ($_ -split "/").Count }

Write-Host "Creating $($sortedDirs.Count) directories..." -ForegroundColor Yellow
foreach ($dir in $sortedDirs) {
    Create-FtpDirectory -Path $dir | Out-Null
}

Write-Host ""
Write-Host "Uploading $($files.Count) files..." -ForegroundColor Yellow

$success = 0
$failed = 0

foreach ($file in $files) {
    $relativePath = $file.FullName.Substring($localPath.Length).Replace("\", "/")
    $remoteFile = "$remotePath$relativePath"
    
    if (Upload-File -LocalFile $file.FullName -RemotePath $remoteFile) {
        $success++
    } else {
        $failed++
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Yellow
Write-Host "  Deployment Results" -ForegroundColor $(if ($failed -eq 0) { "Green" } else { "Yellow" })
Write-Host "  Success: $success files" -ForegroundColor Green
if ($failed -gt 0) {
    Write-Host "  Failed: $failed files" -ForegroundColor Red
}
Write-Host "========================================" -ForegroundColor Yellow
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Create databases: https://vpn.the-truth-publishing.com/api/config/setup-databases.php"
Write-Host "2. Test site: https://vpn.the-truth-publishing.com/"
Write-Host ""
