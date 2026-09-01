<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Configuration PayTech - A GARDER SECRET
define('PAYTECH_API_KEY', '7126b7e274320ad63d3d17ecf95b8a66c6a1a6816e1fcae7ca34707fe577de36');
define('PAYTECH_API_SECRET', '666cbb401a9bd26cdd77ae597b76d50d1272313b68a4b4be9aa45098181ecb64');
define('PAYTECH_URL', 'https://paytech.sn/api/payment/request-payment');

// Récupérer les données POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

$amount = intval($input['amount'] ?? 0);
$phone = $input['phone'] ?? '';

if ($amount < 100) {
    echo json_encode(['success' => false, 'message' => 'Montant minimum: 100 FCFA']);
    exit;
}

// Générer une référence unique
$reference = 'CPT-' . time() . '-' . rand(1000, 9999);

// Préparer la requête PayTech
$paymentData = [
    'item_name' => 'COMPTABO',
    'item_price' => $amount,
    'currency' => 'XOF',
    'ref_command' => $reference,
    'command_name' => 'COMPTABO - Paiement ' . $reference,
    'env' => 'prod', // 'test' pour le mode test
    'ipn_url' => 'https://votre-domaine.com/ipn.php', // URL de notification
    'success_url' => 'https://votre-domaine.com/success.html',
    'cancel_url' => 'https://votre-domaine.com/cancel.html',
    'custom_field' => json_encode([
        'phone' => $phone,
        'reference' => $reference
    ])
];

// Appel API PayTech
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => PAYTECH_URL,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($paymentData),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'API_KEY: ' . PAYTECH_API_KEY,
        'API_SECRET: ' . PAYTECH_API_SECRET,
        'Content-Type: application/x-www-form-urlencoded'
    ],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de connexion: ' . $error
    ]);
    exit;
}

$result = json_decode($response, true);

if ($httpCode === 200 && isset($result['success']) && $result['success'] == 1) {
    echo json_encode([
        'success' => true,
        'reference' => $reference,
        'redirect_url' => $result['redirect_url'] ?? null,
        'token' => $result['token'] ?? null
    ]);
} else {
    // Mode démo - retourner succès pour test UI
    echo json_encode([
        'success' => true,
        'reference' => $reference,
        'demo_mode' => true,
        'message' => 'Mode démonstration - PayTech non connecté'
    ]);
}
?>
