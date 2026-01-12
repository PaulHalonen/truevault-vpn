# FTP Upload Script for TrueVault VPN
$ftpHost = "ftp://the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$localPath = "E:\Documents\GitHub\truevault-vpn"
$remotePath = "/public_html/vpn.the-truth-publishing.com"

Write-Host "Checking FTP connection..." -ForegroundColor Cyan

try {
    # List root directory
    $request = [System.Net.FtpWebRequest]::Create("$ftpHost/public_html/")
    $request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
    $request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $response = $request.GetResponse()
    $reader = New-Object System.IO.StreamReader($response.GetResponseStream())
    $listing = $reader.ReadToEnd()
    $reader.Close()
    $response.Close()
    
    Write-Host "public_html contents:" -ForegroundColor Green
    Write-Host $listing
    
} catch {
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
}

# Check vpn subdomain folder
Write-Host "`nChecking vpn.the-truth-publishing.com folder..." -ForegroundColor Cyan
try {
    $request2 = [System.Net.FtpWebRequest]::Create("$ftpHost/public_html/vpn.the-truth-publishing.com/")
    $request2.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
    $request2.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $response2 = $request2.GetResponse()
    $reader2 = New-Object System.IO.StreamReader($response2.GetResponseStream())
    $listing2 = $reader2.ReadToEnd()
    $reader2.Close()
    $response2.Close()
    
    if ($listing2.Trim() -eq "") {
        Write-Host "Folder is EMPTY - needs upload!" -ForegroundColor Yellow
    } else {
        Write-Host "vpn folder contents:" -ForegroundColor Green
        Write-Host $listing2
    }
} catch {
    Write-Host "Error accessing vpn folder: $($_.Exception.Message)" -ForegroundColor Red
}
