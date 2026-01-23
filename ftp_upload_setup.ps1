# Quick upload setup.php
$ftpHost = "ftp://the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$correctPath = "/public_html/vpn.the-truth-publishing.com"
$localPath = "E:\Documents\GitHub\truevault-vpn\website"

$credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)

$uri = New-Object System.Uri("$ftpHost$correctPath/setup.php")
$request = [System.Net.FtpWebRequest]::Create($uri)
$request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
$request.Credentials = $credentials
$request.UseBinary = $true
$request.UsePassive = $true

$fileContent = [System.IO.File]::ReadAllBytes("$localPath\setup.php")
$request.ContentLength = $fileContent.Length

$stream = $request.GetRequestStream()
$stream.Write($fileContent, 0, $fileContent.Length)
$stream.Close()

$response = $request.GetResponse()
Write-Host "Uploaded setup.php" -ForegroundColor Green
$response.Close()

Write-Host ""
Write-Host "Run: https://vpn.the-truth-publishing.com/setup.php" -ForegroundColor Yellow
