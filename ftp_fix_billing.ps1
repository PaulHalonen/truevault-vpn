# Quick upload fix
$ftpHost = "ftp://the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$remotePath = "/public_html/vpn.the-truth-publishing.com"
$localPath = "E:\Documents\GitHub\truevault-vpn\website"

$ftpCred = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)

$request = [System.Net.FtpWebRequest]::Create("$ftpHost$remotePath/admin/setup-billing-tables.php")
$request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
$request.Credentials = $ftpCred
$request.UseBinary = $true
$content = [System.IO.File]::ReadAllBytes("$localPath\admin\setup-billing-tables.php")
$request.ContentLength = $content.Length
$stream = $request.GetRequestStream()
$stream.Write($content, 0, $content.Length)
$stream.Close()
$response = $request.GetResponse()
$response.Close()
Write-Host "Uploaded setup-billing-tables.php"
