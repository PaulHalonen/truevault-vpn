# FTP Upload - setup-databases.php
$ftpHost = "ftp://the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$remotePath = "/public_html/vpn.the-truth-publishing.com"
$localPath = "E:\Documents\GitHub\truevault-vpn\website"

$ftpCred = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)

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
        return $true
    } catch {
        Write-Host "Error uploading $localFile : $_"
        return $false
    }
}

Write-Host "Uploading setup-databases.php..."
Upload-FtpFile "$localPath\admin\setup-databases.php" "$ftpHost$remotePath/admin/setup-databases.php"
Write-Host "Done!"
