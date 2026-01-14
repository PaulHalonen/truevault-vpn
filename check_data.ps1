$ftpUrl = "ftp://the-truth-publishing.com/public_html/vpn.the-truth-publishing.com/data/"
$username = "kahlen@the-truth-publishing.com"
$password = "AndassiAthena8"

try {
    $request = [System.Net.FtpWebRequest]::Create($ftpUrl)
    $request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $request.Credentials = New-Object System.Net.NetworkCredential($username, $password)
    $request.UsePassive = $true
    
    $response = $request.GetResponse()
    $stream = $response.GetResponseStream()
    $reader = New-Object System.IO.StreamReader($stream)
    
    Write-Host "=== DATA FOLDER (DATABASES) ON SERVER ==="
    while (-not $reader.EndOfStream) {
        $line = $reader.ReadLine()
        Write-Host $line
    }
    
    $reader.Close()
    $stream.Close()
    $response.Close()
}
catch {
    Write-Host "Error: $($_.Exception.Message)"
}
