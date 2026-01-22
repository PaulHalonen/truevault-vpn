# FTP Upload - Part 5 Billing APIs
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

function Upload-File($local, $remote) {
    try {
        $request = [System.Net.FtpWebRequest]::Create("$ftpHost$remote")
        $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $request.Credentials = $ftpCred
        $request.UseBinary = $true
        $content = [System.IO.File]::ReadAllBytes($local)
        $request.ContentLength = $content.Length
        $stream = $request.GetRequestStream()
        $stream.Write($content, 0, $content.Length)
        $stream.Close()
        $response = $request.GetResponse()
        $response.Close()
        Write-Host "Uploaded: $local"
    } catch {
        Write-Host "Error: $local - $_"
    }
}

Write-Host "=== Creating directories ==="
Create-FtpDir "$ftpHost$remotePath/api/billing"

Write-Host "`n=== Uploading PayPal class ==="
Upload-File "$localPath\includes\PayPal.php" "$remotePath/includes/PayPal.php"

Write-Host "`n=== Uploading Billing API files ==="
Upload-File "$localPath\api\billing\create-subscription.php" "$remotePath/api/billing/create-subscription.php"
Upload-File "$localPath\api\billing\paypal-webhook.php" "$remotePath/api/billing/paypal-webhook.php"
Upload-File "$localPath\api\billing\cancel-subscription.php" "$remotePath/api/billing/cancel-subscription.php"

Write-Host "`n=== Uploading Admin migration ==="
Upload-File "$localPath\admin\add-webhook-table.php" "$remotePath/admin/add-webhook-table.php"

Write-Host "`n=== Done! ==="
