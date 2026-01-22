# Delete sensitive admin scripts from server
$ftpHost = "ftp://the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$remotePath = "/public_html/vpn.the-truth-publishing.com"

$ftpCred = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)

$filesToDelete = @(
    "$remotePath/admin/reset-database.php",
    "$remotePath/admin/fix-users-table.php"
)

foreach ($file in $filesToDelete) {
    try {
        $request = [System.Net.FtpWebRequest]::Create("$ftpHost$file")
        $request.Method = [System.Net.WebRequestMethods+Ftp]::DeleteFile
        $request.Credentials = $ftpCred
        $response = $request.GetResponse()
        $response.Close()
        Write-Host "Deleted: $file"
    } catch {
        Write-Host "Could not delete $file : $_"
    }
}
