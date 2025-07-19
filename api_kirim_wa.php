<?php
header('Content-Type: application/json');
session_start();

// Validasi CSRF Token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['status' => 'error', 'message' => 'Token CSRF tidak valid']);
    exit;
}

// Validasi input
$required_fields = ['phone', 'message'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
        exit;
    }
}

$phone = $_POST['phone'];
$message = $_POST['message'];

// Bersihkan nomor telepon
$phone = preg_replace('/[^0-9]/', '', $phone);

// Pilihan metode pengiriman:
// 1. Menggunakan WhatsApp Business API (Resmi)
// 2. Menggunakan gateway pihak ketiga
// 3. Menggunakan library WhatsApp seperti ChatAPI atau WART

// Contoh implementasi dengan Twilio WhatsApp API
$result = sendViaTwilio($phone, $message);

if ($result['success']) {
    echo json_encode(['status' => 'success', 'message' => 'Pesan terkirim']);
} else {
    echo json_encode(['status' => 'error', 'message' => $result['error']]);
}

// Fungsi contoh dengan Twilio
function sendViaTwilio($phone, $message) {
    $account_sid = 'YOUR_TWILIO_ACCOUNT_SID';
    $auth_token = 'YOUR_TWILIO_AUTH_TOKEN';
    $twilio_wa_number = 'YOUR_TWILIO_WHATSAPP_NUMBER'; // Format: +14155238886
    
    $url = "https://api.twilio.com/2010-04-01/Accounts/$account_sid/Messages.json";
    
    $data = [
        'From' => 'whatsapp:' . $twilio_wa_number,
        'To' => 'whatsapp:' . $phone,
        'Body' => $message
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_USERPWD, "$account_sid:$auth_token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 201) {
        return ['success' => true];
    } else {
        $response_data = json_decode($response, true);
        return ['success' => false, 'error' => $response_data['message'] ?? 'Unknown error'];
    }
}

// Alternatif: Jika menggunakan WhatsApp Business API langsung
function sendViaWhatsAppBusinessAPI($phone, $message) {
    $access_token = 'YOUR_FB_ACCESS_TOKEN';
    $phone_id = 'YOUR_PHONE_ID';
    $version = 'v18.0'; // Versi API terbaru
    
    $url = "https://graph.facebook.com/$version/$phone_id/messages";
    
    $data = [
        'messaging_product' => 'whatsapp',
        'to' => $phone,
        'type' => 'text',
        'text' => ['body' => $message]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $http_code == 200;
}
?>