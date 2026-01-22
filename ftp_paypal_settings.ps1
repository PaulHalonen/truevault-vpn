# Quick upload of PayPal settings script
$ftpHost = "ftp://the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$remotePath = "/public_html/vpn.the-truth-publishing.com"
$localPath = "E:\Documents\GitHub\truevault-vpn\website"

$ftpCred = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)

$request = [System.Net.FtpWebRequest]::Create("$ftpHost$remotePath/admin/add-paypal-settings.php")
$request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
$request.Credentials = $ftpCred
$request.UseBinary = $true
$content = [System.IO.File]::ReadAllBytes("$localPath\admin\add-paypal-settings.php")
$request.ContentLength = $content.Length
$stream = $request.GetRequestStream()
$stream.Write($content, 0, $content.Length)
$stream.Close()
$response = $request.GetResponse()
$response.Close()
Write-Host "Uploaded add-paypal-settings.php"
