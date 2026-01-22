# FTP Upload - Part 6 Port Forwarding
$ftpHost = "ftp://the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$remotePath = "/public_html/vpn.the-truth-publishing.com"
$localPath = "E:\Documents\GitHub\truevault-vpn\website"

$ftpCred = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)

function Create-Dir($dir) { try { $r = [System.Net.FtpWebRequest]::Create($dir); $r.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory; $r.Credentials = $ftpCred; $r.GetResponse().Close() } catch {} }
function Upload($local, $remote) {
    $r = [System.Net.FtpWebRequest]::Create("$ftpHost$remote"); $r.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile; $r.Credentials = $ftpCred; $r.UseBinary = $true
    $c = [System.IO.File]::ReadAllBytes($local); $r.ContentLength = $c.Length; $s = $r.GetRequestStream(); $s.Write($c, 0, $c.Length); $s.Close(); $r.GetResponse().Close()
    Write-Host "Uploaded: $local"
}

Create-Dir "$ftpHost$remotePath/api/port-forwarding"

Upload "$localPath\dashboard\port-forwarding.php" "$remotePath/dashboard/port-forwarding.php"
Upload "$localPath\api\port-forwarding\list.php" "$remotePath/api/port-forwarding/list.php"
Upload "$localPath\api\port-forwarding\add.php" "$remotePath/api/port-forwarding/add.php"
Upload "$localPath\api\port-forwarding\delete.php" "$remotePath/api/port-forwarding/delete.php"

Write-Host "Done!"
