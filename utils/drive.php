<?php
define('DRIVE_WEBAPP_URL', 'https://script.google.com/macros/s/AKfycbzhAVhqeRTy-Fy-YPBi-qpI0bbFKBP_cT-aOWAP9TnxybfMF5ICXEqMaGyRAkGS8mY9/exec');
define('DRIVE_SECRET', 'kknm-unpad-2026-KvWxDDLHMf');

function uploadToDrive(string $base64Data, string $filename, string $mimeType = 'image/png'): array
{
    $payload = json_encode([
        'secret'   => DRIVE_SECRET,
        'filename' => $filename,
        'mimeType' => $mimeType,
        'data'     => $base64Data,
    ]);

    $ch = curl_init(DRIVE_WEBAPP_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return ['success' => false, 'message' => "cURL error: $curlErr"];
    }

    $result = json_decode($response, true);

    if (!is_array($result)) {
        return ['success' => false, 'message' => 'Response Apps Script tidak valid: ' . substr((string)$response, 0, 200)];
    }

    return $result;
}

function fotoUrl(?string $value, string $localPrefix = '/public/uploads/'): string
{
    if (empty($value)) {
        return '/public/uploads/default.png';
    }
    if (str_starts_with($value, 'http')) {
        return $value;
    }
    return $localPrefix . $value;
}
?>
