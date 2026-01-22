# Delete cleanup script from server
$ftpHost = "ftp://the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$remotePath = "/public_html/vpn.the-truth-publishing.com"

$ftpCred = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)

$request = [System.Net.FtpWebRequest]::Create("$ftpHost$remotePath/admin/cleanup-config.php")
$request.Method = [System.Net.WebRequestMethods+Ftp]::DeleteFile
$request.Credentials = $ftpCred
try {
    $response = $request.GetResponse()
    $response.Close()
    Write-Host "Deleted cleanup-config.php from server"
} catch {
    Write-Host "Error deleting: $_"
}
