<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$conversationId = $input['conversationId'] ?? null;

if (empty($conversationId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'conversationId no proporcionado.']);
    exit;
}

$logFilePath = __DIR__ . '/agent_responses.log';
$tempFilePath = __DIR__ . '/agent_responses.log.tmp';

if (!file_exists($logFilePath)) {
    echo json_encode(['success' => true, 'message' => 'El archivo de log no existe.']);
    exit;
}

$logFile = fopen($logFilePath, 'r');
$tempFile = fopen($tempFilePath, 'w');

if (!$logFile || !$tempFile) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo abrir el archivo de log para leer o escribir.']);
    exit;
}

$deleted = false;
while (($line = fgets($logFile)) !== false) {
    $logEntry = json_decode(trim($line), true);
    if (isset($logEntry['conversationId']) && $logEntry['conversationId'] === $conversationId) {
        $deleted = true;
        continue; // No escribir esta línea en el archivo temporal
    }
    fwrite($tempFile, $line);
}

fclose($logFile);
fclose($tempFile);

if (rename($tempFilePath, $logFilePath)) {
    echo json_encode(['success' => true, 'message' => 'Conversación eliminada con éxito.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el archivo de log.']);
    // Si rename falla, es posible que quieras eliminar el archivo temporal
    unlink($tempFilePath);
}

?>
