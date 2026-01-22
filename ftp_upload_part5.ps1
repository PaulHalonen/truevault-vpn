# Upload Part 5 files
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
        Write-Host "Created: $dir"
    } catch {}
}

function Upload($file, $remote) {
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
}

Write-Host "=== Creating directories ==="
Create-FtpDir "$ftpHost$remotePath/api/billing"

Write-Host "`n=== Uploading Part 5 files ==="
Upload "$localPath\includes\PayPal.php" "$remotePath/includes/PayPal.php"
Upload "$localPath\api\billing\create-subscription.php" "$remotePath/api/billing/create-subscription.php"
Upload "$localPath\api\billing\paypal-webhook.php" "$remotePath/api/billing/paypal-webhook.php"
Upload "$localPath\admin\add-webhook-table.php" "$remotePath/admin/add-webhook-table.php"

Write-Host "`n=== Done! ==="
