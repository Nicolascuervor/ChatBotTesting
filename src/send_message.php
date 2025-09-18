<?php


$webhookUrl = "https://n8n-aseoluz-n8n.wldsgu.easypanel.host/webhook/testing";
$publicBaseUrl = "https://gulf-lance-url-hospitality.trycloudflare.com"; // URL pública de tu túnel
$uploadDir = __DIR__ . '/uploads/';


header('Content-Type: application/json');


$conversationId = $_POST['conversationId'] ?? null;
$message = $_POST['message'] ?? '';
$file = $_FILES['file'] ?? null;

if (!$conversationId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID de conversación no proporcionado.']);
    exit;
}

$fileUrl = null;

// 2. Manejar la subida de archivos
if ($file && $file['error'] === UPLOAD_ERR_OK) {
    $fileName = uniqid() . '-' . basename($file['name']);
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Construir la URL pública del archivo usando la URL base del túnel
        $uri = str_replace($_SERVER['DOCUMENT_ROOT'], '', $uploadDir);
        $uri = str_replace(DIRECTORY_SEPARATOR, '/', $uri);
        $fileUrl = rtrim($publicBaseUrl, '/') . $uri . $fileName;
        
        $messageForLog = $message . ($message ? " " : "") . "[Archivo: {$fileUrl}]";
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error al mover el archivo subido.']);
        exit;
    }
} else {
    $messageForLog = $message;
}

if (empty($messageForLog)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No hay mensaje ni archivo para enviar.']);
    exit;
}


// 3. Preparar la entrada para el log
$logEntry = [
    'timestamp' => time(),
    'conversationId' => $conversationId,
    'message' => $messageForLog,
    'direction' => 'outgoing',
    'metadata' => ['client' => 'Dashboard Manual', 'sent_at' => date("Y-m-d H:i:s")]
];

file_put_contents(__DIR__ . '/agent_responses.log', json_encode($logEntry) . PHP_EOL, FILE_APPEND);

// 4. Preparar el payload para n8n
$pushName = explode('@', $conversationId)[0];

$messageData = [];
if ($fileUrl) {
    $fileType = mime_content_type($uploadDir . $fileName);
    if (strpos($fileType, 'image/') === 0) {
        $messageData['image'] = ['url' => $fileUrl];
        if ($message) {
            $messageData['caption'] = $message;
        }
    } elseif (strpos($fileType, 'audio/') === 0) {
        $messageData['audio'] = ['url' => $fileUrl];
        // Caption for audio is not standard, so we send it as a separate text message if needed
    }
} else {
    $messageData['conversation'] = $message;
}

$payloadToSend = [
    "sender" => $conversationId,
    "data" => [
        "pushName" => $pushName,
        "message" => $messageData
    ]
];

// 5. Enviar el webhook a n8n
$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payloadToSend));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// 6. Responder a la solicitud del dashboard
if ($response === false) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error de cURL: ' . $curlError]);
} else {
    echo json_encode(['status' => 'success', 'http_code' => $httpCode, 'response' => $response]);
}

?>
