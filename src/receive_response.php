<?php
// Establece la cabecera para indicar que la respuesta es JSON
header('Content-Type: application/json');

// Define el archivo donde se guardarán todas las respuestas del agente
$logFile = __DIR__ . '/agent_responses.log';

// Lee el cuerpo de la petición POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Valida que los datos necesarios (ID de conversación y mensaje) existan
if ($data && isset($data['conversationId']) && isset($data['message'])) {
    
    // Crea una estructura de datos para el registro
    $logEntry = [
        'timestamp' => time(),
        'conversationId' => $data['conversationId'],
        'message' => $data['message'],
        'direction' => 'incoming', // <-- CORRECCIÓN: Marca el mensaje como entrante (del agente)
        'metadata' => $data['metadata'] ?? null
    ];

    // Guarda la entrada como una nueva línea JSON en el archivo de log
    file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND);

    // Responde a n8n con un código 200 (OK) para confirmar la recepción
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Response received and logged.']);

} else {
    // Si faltan datos, responde con un error 400 (Bad Request)
    http_response_code(400);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid data. Missing "conversationId" or "message".'
    ]);
}
?>