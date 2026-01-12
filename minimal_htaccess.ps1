# Upload minimal htaccess
$ftpHost = "ftp://the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$remotePath = "/public_html/vpn.the-truth-publishing.com"
$localPath = "E:\Documents\GitHub\truevault-vpn"

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

# Create minimal htaccess
$minimalHtaccess = @"
DirectoryIndex index.html
"@

$htaccessPath = "$localPath\minimal.htaccess"
[System.IO.File]::WriteAllText($htaccessPath, $minimalHtaccess)

Write-Host "Uploading minimal .htaccess..." -ForegroundColor Cyan
Upload-FtpFile -localFile $htaccessPath -remoteFile "$remotePath/.htaccess"

Write-Host "`nDone. Test the site." -ForegroundColor Green
