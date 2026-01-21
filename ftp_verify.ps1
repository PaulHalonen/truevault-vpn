# FTP List Script - Verify files on server
$ftpHost = "ftp://the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$remotePath = "/public_html/vpn.the-truth-publishing.com"

$ftpCred = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)

function List-FtpDirectory($uri) {
    try {
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectoryDetails
        $request.Credentials = $ftpCred
        
        $response = $request.GetResponse()
        $stream = $response.GetResponseStream()
        $reader = New-Object System.IO.StreamReader($stream)
        $content = $reader.ReadToEnd()
        $reader.Close()
        $response.Close()
        
        return $content
    } catch {
        return "Error: $_"
    }
}

Write-Host "=== Files in $remotePath ==="
$listing = List-FtpDirectory "$ftpHost$remotePath"
Write-Host $listing

Write-Host ""
Write-Host "=== Files in $remotePath/configs ==="
$listing = List-FtpDirectory "$ftpHost$remotePath/configs"
Write-Host $listing

Write-Host ""
Write-Host "=== Files in $remotePath/databases ==="
$listing = List-FtpDirectory "$ftpHost$remotePath/databases"
Write-Host $listing
