# Upload updated API files
$ftpHost = "ftp://the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$localPath = "E:\Documents\GitHub\truevault-vpn"
$remotePath = "/public_html/vpn.the-truth-publishing.com"

function Upload-FtpFile {
    param($localFile, $remoteFile)
    try {
        $uri = "$ftpHost$remoteFile"
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $request.UseBinary = $true
        $request.UsePassive = $true
        $fileContent = [System.IO.File]::ReadAllBytes($localFile)
        $request.ContentLength = $fileContent.Length
        $requestStream = $request.GetRequestStream()
        $requestStream.Write($fileContent, 0, $fileContent.Length)
        $requestStream.Close()
        $response = $request.GetResponse()
        Write-Host "Uploaded: $remoteFile" -ForegroundColor Green
        $response.Close()
    } catch {
        Write-Host "Error uploading $remoteFile : $($_.Exception.Message)" -ForegroundColor Red
    }
}

# Upload updated files
Upload-FtpFile -localFile "$localPath\api\config\database.php" -remoteFile "$remotePath/api/config/database.php"
Upload-FtpFile -localFile "$localPath\api\config\jwt.php" -remoteFile "$remotePath/api/config/jwt.php"
Upload-FtpFile -localFile "$localPath\api\helpers\auth.php" -remoteFile "$remotePath/api/helpers/auth.php"
Upload-FtpFile -localFile "$localPath\api\auth\login.php" -remoteFile "$remotePath/api/auth/login.php"

Write-Host "`nDone! Try logging in now." -ForegroundColor Cyan
