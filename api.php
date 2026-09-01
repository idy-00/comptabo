<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Configuration PayTech
define('PAYTECH_API_KEY', '7126b7e274320ad63d3d17ecf95b8a66c6a1a6816e1fcae7ca34707fe577de36');
define('PAYTECH_API_SECRET', '666cbb401a9bd26cdd77ae597b76d50d1272313b68a4b4be9aa45098181ecb64');
define('PAYTECH_URL', 'https://paytech.sn/api/payment/request-payment');

// URLs de retour - MODIFIE AVEC TON DOMAINE
$baseUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);

// Récupérer les données POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

$amount = intval($input['amount'] ?? 0);
$description = $input['description'] ?? 'Paiement Comptabo';

if ($amount < 100) {
    echo json_encode(['success' => false, 'message' => 'Montant minimum: 100 FCFA']);
    exit;
}

// Générer une référence unique
$reference = 'CPT' . time() . rand(100, 999);

// Préparer la requête PayTech
$paymentData = [
    'item_name' => 'COMPTABO - ' . $description,
    'item_price' => $amount,
    'currency' => 'XOF',
    'ref_command' => $reference,
    'command_name' => $description,
    'env' => 'prod',
    'ipn_url' => $baseUrl . '/ipn.php',
    'success_url' => $baseUrl . '/success.html',
    'cancel_url' => $baseUrl . '/cancel.html',
    'custom_field' => json_encode([
        'reference' => $reference,
        'amount' => $amount
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

// Log pour debug
file_put_contents('paytech_log.txt', date('Y-m-d H:i:s') . " | Code: $httpCode | Response: $response | Error: $error\n", FILE_APPEND);

if ($error) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de connexion à PayTech'
    ]);
    exit;
}

$result = json_decode($response, true);

if (isset($result['success']) && $result['success'] == 1 && isset($result['redirect_url'])) {
    echo json_encode([
        'success' => true,
        'reference' => $reference,
        'redirect_url' => $result['redirect_url'],
        'token' => $result['token'] ?? null
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $result['message'] ?? 'Erreur PayTech',
        'debug' => $result
    ]);
}
?>
