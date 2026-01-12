# Upload register.php
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
        Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
    }
}

Upload-FtpFile -localFile "$localPath\api\auth\register.php" -remoteFile "$remotePath/api/auth/register.php"
Write-Host "Done!" -ForegroundColor Cyan
