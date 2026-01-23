$baseDir = "E:\Documents\GitHub\truevault-vpn"
$ftpHost = "ftp://the-truth-publishing.com"
$ftpUser = "kahlen@the-truth-publishing.com"
$ftpPass = "AndassiAthena8"
$remotePath = "/vpn.the-truth-publishing.com"

$files = @(
    @{local="$baseDir\website\setup.php"; remote="setup.php"},
    @{local="$baseDir\website\pricing.php"; remote="pricing.php"},
    @{local="$baseDir\website\pricing-comparison.php"; remote="pricing-comparison.php"},
    @{local="$baseDir\website\includes\content-functions.php"; remote="includes/content-functions.php"}
)

foreach ($f in $files) {
    $uri = "$ftpHost$remotePath/$($f.remote)"
    $localFile = $f.local
    Write-Host "Uploading $localFile..."
    try {
        $req = [System.Net.FtpWebRequest]::Create($uri)
        $req.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
        $req.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
        $req.UseBinary = $true
        $req.UsePassive = $true
        $content = [System.IO.File]::ReadAllBytes($localFile)
        $req.ContentLength = $content.Length
        $stream = $req.GetRequestStream()
        $stream.Write($content, 0, $content.Length)
        $stream.Close()
        $response = $req.GetResponse()
        Write-Host "OK: $($response.StatusDescription)"
        $response.Close()
    } catch {
        Write-Host "ERROR: $_"
    }
}
Write-Host "Done!"
