# FTP Upload - Part 6 Dashboard & Port Forwarding
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
        Write-Host "Created: $dir"
    } catch { }
}

function Upload($file, $remote) {
    try {
        $request = [System.Net.FtpWebRequest]::Create("$ftpHost$remote")
        $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $request.Credentials = $ftpCred
        $request.UseBinary = $true
        $content = [System.IO.File]::ReadAllBytes($file)
        $request.ContentLength = $content.Length
        $stream = $request.GetRequestStream()
        $stream.Write($content, 0, $content.Length)
        $stream.Close()
        $response = $request.GetResponse()
        $response.Close()
        Write-Host "Uploaded: $file"
    } catch {
        Write-Host "Error: $_"
    }
}

Write-Host "=== Creating directories ==="
Create-FtpDir "$ftpHost$remotePath/api/port-forwarding"

Write-Host "`n=== Uploading Port Forwarding API ==="
Upload "$localPath\api\port-forwarding\list.php" "$remotePath/api/port-forwarding/list.php"
Upload "$localPath\api\port-forwarding\create.php" "$remotePath/api/port-forwarding/create.php"
Upload "$localPath\api\port-forwarding\delete.php" "$remotePath/api/port-forwarding/delete.php"

Write-Host "`n=== Uploading Dashboard ==="
Upload "$localPath\dashboard\index.php" "$remotePath/dashboard/index.php"
Upload "$localPath\login.php" "$remotePath/login.php"

Write-Host "`n=== Done! ==="
