$ftpHost = "ftp://the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$ftpPath = "/public_html/vpn.the-truth-publishing.com/api/"

[System.Net.ServicePointManager]::SecurityProtocol = [System.Net.SecurityProtocolType]::Tls12

$ftp = [System.Net.FtpWebRequest]::Create($ftpHost + $ftpPath)
$ftp.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
$ftp.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
$ftp.EnableSsl = $false
$ftp.UsePassive = $true

try {
    $response = $ftp.GetResponse()
    $reader = New-Object System.IO.StreamReader($response.GetResponseStream())
    $listing = $reader.ReadToEnd()
    Write-Host "=== API FOLDER CONTENTS ==="
    Write-Host $listing
    $reader.Close()
    $response.Close()
} catch {
    Write-Host "Error: $_"
}
