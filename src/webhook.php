<?php
// ==================================================================
// CONFIGURACIÓN
// ==================================================================
$webhookUrl = "https://n8n-aseoluz-n8n.wldsgu.easypanel.host/webhook/testing";
$logFile = __DIR__ . '/agent_responses.log';

// ==================================================================
// PROCESAMIENTO DEL WEBHOOK ENTRANTE
// ==================================================================
header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['sender']) || !isset($data['data']['message'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Payload inválido.']);
    exit;
}

// 1. Extraer datos del payload
$conversationId = $data['sender'];
$pushName = $data['data']['pushName'] ?? explode('@', $conversationId)[0];
$messageData = $data['data']['message'];

$messageForLog = '';
$fileUrl = null;

// 2. Determinar el tipo de mensaje y formatear
if (is_string($messageData)) { // Mensaje de texto simple (formato antiguo)
    $messageForLog = $messageData;
} elseif (isset($messageData['conversation'])) { // Mensaje de texto
    $messageForLog = $messageData['conversation'];
} elseif (isset($messageData['image']['url'])) { // Mensaje con imagen
    $fileUrl = $messageData['image']['url'];
    $caption = $messageData['caption'] ?? '';
    $messageForLog = $caption . ($caption ? ' ' : '') . "[Archivo: {$fileUrl}]";
} elseif (isset($messageData['audio']['url'])) { // Mensaje con audio
    $fileUrl = $messageData['audio']['url'];
    $messageForLog = "[Audio: {$fileUrl}]";
}

if (empty($messageForLog)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Mensaje vacío o formato no reconocido.']);
    exit;
}

// 3. Registrar el mensaje entrante en el log del dashboard
$logEntry = [
    'timestamp' => time(),
    'conversationId' => $conversationId,
    'message' => $messageForLog,
    'direction' => 'outgoing', // Es 'outgoing' desde la perspectiva del dashboard (el usuario envía)
    'metadata' => ['client' => 'Webhook Real', 'received_at' => date("Y-m-d H:i:s")]
];

file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND);

// 4. Preparar y enviar el payload a n8n (que es el mismo que se recibió)
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $input); // Reenviar el payload original
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// 5. Responder al servicio de mensajería original para confirmar la recepción
if ($response === false) {
    // Incluso si n8n falla, respondemos 200 al webhook original para evitar reintentos.
    // Podríamos loggear el error de cURL para depuración interna.
    file_put_contents(__DIR__ . '/../logs/curl_errors.log', date("Y-m-d H:i:s") . " - cURL Error: " . $curlError . PHP_EOL, FILE_APPEND);
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Webhook recibido, pero error al reenviar a n8n.']);
} else {
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Webhook recibido y reenviado exitosamente.']);
}

?>
