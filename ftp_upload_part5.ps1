# FTP Upload - Part 5 Billing & PayPal
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
Create-FtpDir "$ftpHost$remotePath/api/billing"

Write-Host "`n=== Uploading includes ==="
Upload "$localPath\includes\PayPal.php" "$remotePath/includes/PayPal.php"

Write-Host "`n=== Uploading billing API ==="
Upload "$localPath\api\billing\create-subscription.php" "$remotePath/api/billing/create-subscription.php"
Upload "$localPath\api\billing\paypal-webhook.php" "$remotePath/api/billing/paypal-webhook.php"
Upload "$localPath\api\billing\status.php" "$remotePath/api/billing/status.php"

Write-Host "`n=== Uploading dashboard pages ==="
Upload "$localPath\dashboard\subscription-success.php" "$remotePath/dashboard/subscription-success.php"
Upload "$localPath\dashboard\subscription-cancelled.php" "$remotePath/dashboard/subscription-cancelled.php"

Write-Host "`n=== Uploading admin setup ==="
Upload "$localPath\admin\setup-billing-tables.php" "$remotePath/admin/setup-billing-tables.php"

Write-Host "`n=== Done! ==="
