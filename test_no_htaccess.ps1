# Test - Try minimal htaccess
$ftpHost = "ftp://the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$remotePath = "/public_html/vpn.the-truth-publishing.com"

# Rename .htaccess to .htaccess.bak
Write-Host "Renaming .htaccess to test..." -ForegroundColor Yellow
try {
    $request = [System.Net.FtpWebRequest]::Create("$ftpHost$remotePath/.htaccess")
    $request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
    $request.Method = [System.Net.WebRequestMethods+Ftp]::Rename
    $request.RenameTo = ".htaccess.bak"
    $response = $request.GetResponse()
    Write-Host "Renamed .htaccess to .htaccess.bak" -ForegroundColor Green
    $response.Close()
} catch {
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`nTry the site now without .htaccess" -ForegroundColor Cyan
