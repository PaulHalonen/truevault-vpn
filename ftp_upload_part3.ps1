# FTP Upload - Part 3 Auth System Files
$ftpHost = "ftp://the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$remotePath = "/public_html/vpn.the-truth-publishing.com"
$localPath = "E:\Documents\GitHub\truevault-vpn\website"

$ftpCred = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)

function Create-FtpDirectory($uri) {
    try {
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $request.Credentials = $ftpCred
        $response = $request.GetResponse()
        $response.Close()
        Write-Host "Created directory: $uri"
    } catch {
        # Directory might already exist, that's OK
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
        return $true
    } catch {
        Write-Host "Error uploading $localFile : $_"
        return $false
    }
}

Write-Host "=== Creating directories ==="
Create-FtpDirectory "$ftpHost$remotePath/api/auth"

Write-Host "`n=== Uploading includes/ ==="
Upload-FtpFile "$localPath\includes\Database.php" "$ftpHost$remotePath/includes/Database.php"
Upload-FtpFile "$localPath\includes\JWT.php" "$ftpHost$remotePath/includes/JWT.php"
Upload-FtpFile "$localPath\includes\Validator.php" "$ftpHost$remotePath/includes/Validator.php"

Write-Host "`n=== Uploading api/auth/ ==="
Upload-FtpFile "$localPath\api\auth\register.php" "$ftpHost$remotePath/api/auth/register.php"
Upload-FtpFile "$localPath\api\auth\login.php" "$ftpHost$remotePath/api/auth/login.php"
Upload-FtpFile "$localPath\api\auth\me.php" "$ftpHost$remotePath/api/auth/me.php"
Upload-FtpFile "$localPath\api\auth\logout.php" "$ftpHost$remotePath/api/auth/logout.php"

Write-Host "`n=== Done! ==="
