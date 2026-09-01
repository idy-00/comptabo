<?php
// IPN (Instant Payment Notification) - PayTech callback
header('Content-Type: application/json');

// Log des notifications
$logFile = 'ipn_logs.txt';
$data = $_POST;
$logEntry = date('Y-m-d H:i:s') . ' | ' . json_encode($data) . "\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Vérifier les paramètres PayTech
$typeEvent = $_POST['type_event'] ?? '';
$refCommand = $_POST['ref_command'] ?? '';
$itemPrice = $_POST['item_price'] ?? 0;
$paymentMethod = $_POST['payment_method'] ?? '';
$clientPhone = $_POST['client_phone'] ?? '';

if ($typeEvent === 'sale_complete') {
    // Paiement réussi - mettre à jour votre base de données
    // TODO: Implémenter votre logique métier

    echo json_encode([
        'success' => true,
        'message' => 'Notification reçue',
        'reference' => $refCommand
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Event type non géré: ' . $typeEvent
    ]);
}
?>
