<?php
/**
 * Test POST to login endpoint
 */
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== POST Login Test ===\n\n";

$loginUrl = 'https://vpn.the-truth-publishing.com/api/auth/login.php';
$data = json_encode([
    'email' => 'paulhalonen@gmail.com',
    'password' => 'Asasasas4!'
]);

echo "URL: $loginUrl\n";
echo "Data: $data\n\n";

$ch = curl_init($loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
}
echo "\nResponse:\n";
echo $response;
echo "\n\n=== END ===\n";
