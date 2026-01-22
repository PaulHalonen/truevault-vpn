# FTP Upload - Part 4 Device Management APIs
$ftpHost = "ftp://the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$remotePath = "/public_html/vpn.the-truth-publishing.com"
$localPath = "E:\Documents\GitHub\truevault-vpn\website"

$ftpCred = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)

function Create-FtpDir($dir) {
    try {
        $request = [System.Net.FtpWebRequest]::Create($dir)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $request.Credentials = $ftpCred
        $response = $request.GetResponse()
        $response.Close()
        Write-Host "Created dir: $dir"
    } catch {
        # Directory may already exist - ignore
    }
}

function Upload-FtpFile($localFile, $remoteUri) {
    try {
        $request = [System.Net.FtpWebRequest]::Create($remoteUri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $request.Credentials = $ftpCred
        $request.UseBinary = $true
        $content = [System.IO.File]::ReadAllBytes($localFile)
        $request.ContentLength = $content.Length
        $stream = $request.GetRequestStream()
        $stream.Write($content, 0, $content.Length)
        $stream.Close()
        $response = $request.GetResponse()
        $response.Close()
        Write-Host "Uploaded: $localFile"
    } catch {
        Write-Host "Error uploading $localFile : $_"
    }
}

Write-Host "=== Creating directories ==="
Create-FtpDir "$ftpHost$remotePath/api/devices"
Create-FtpDir "$ftpHost$remotePath/dashboard"

Write-Host "`n=== Uploading Device API files ==="
Upload-FtpFile "$localPath\api\devices\list.php" "$ftpHost$remotePath/api/devices/list.php"
Upload-FtpFile "$localPath\api\devices\add.php" "$ftpHost$remotePath/api/devices/add.php"
Upload-FtpFile "$localPath\api\devices\delete.php" "$ftpHost$remotePath/api/devices/delete.php"
Upload-FtpFile "$localPath\api\devices\config.php" "$ftpHost$remotePath/api/devices/config.php"

Write-Host "`n=== Uploading Dashboard files ==="
Upload-FtpFile "$localPath\dashboard\setup-device.php" "$ftpHost$remotePath/dashboard/setup-device.php"

Write-Host "`n=== Done! ==="
