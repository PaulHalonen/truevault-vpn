# PowerShell FTP Upload - Fix 403 Error
$ftpHost = "ftp://the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$remotePath = "/vpn.the-truth-publishing.com"
$localPath = "E:\Documents\GitHub\truevault-vpn\website"

$credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)

function Upload-File {
    param($localFile, $remoteFile)
    try {
        $uri = New-Object System.Uri("$ftpHost$remoteFile")
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $request.Credentials = $credentials
        $request.UseBinary = $true
        $request.UsePassive = $true
        
        $fileContent = [System.IO.File]::ReadAllBytes($localFile)
        $request.ContentLength = $fileContent.Length
        
        $stream = $request.GetRequestStream()
        $stream.Write($fileContent, 0, $fileContent.Length)
        $stream.Close()
        
        $response = $request.GetResponse()
        Write-Host "Uploaded: $remoteFile" -ForegroundColor Green
        $response.Close()
        return $true
    } catch {
        Write-Host "Failed: $remoteFile - $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

Write-Host "Uploading files to fix 403 error..." -ForegroundColor Cyan

# Upload fixed .htaccess and test file
Upload-File "$localPath\.htaccess" "$remotePath/.htaccess"
Upload-File "$localPath\test.php" "$remotePath/test.php"

Write-Host ""
Write-Host "Done! Try: https://vpn.the-truth-publishing.com/test.php" -ForegroundColor Green
