<?php

// ==================================================================
// CONFIGURACIÓN PRINCIPAL
// ==================================================================

// URL de tu webhook de n8n (Asegúrate de que sea la correcta)
$webhookUrl = "https://n8n-aseoluz-n8n.wldsgu.easypanel.host/webhook/testing"; 

// ¿Cuántas solicitudes quieres enviar?
$numberOfRequests = 5; 

// ==================================================================
// MOTOR DE GENERACIÓN DE DATOS ALEATORIOS
// ==================================================================
function generateComplexWebhookData(): array
{
    // --- Fuentes de datos de clientes más realistas ---
    $pushNames = [
        "Hotel La Finca", "Restaurante El Roble", "Ana García", "Carlos Ruiz", 
        "Constructora ABC", "Conjunto Residencial El Portal", "Maria Fernanda", "Supermercado La Canasta"
    ];

    // --- CATEGORÍA 1: Saludos Generales ---
    $generalGreetings = [
        "hola", "buenas", "buenos dias", "q mas", "hey, estan ahi?", "hola, necesito informacion", "buenas tardes"
    ];

    // --- CATEGORÍA 2: Solicitudes de Pedido (Basado en productos de Aseoluz) ---
    $orderRequests = [
        "buen dia, quiero hacer un pedido",
        "necesito cotizar 10 galones de hipoclorito y 5 cajas de bolsas de basura de 55x75",
        "hola, me podrian ayudar con un pedido de límpido y jabón para manos por favor?",
        "venden ambientador en galon?",
        "cuanto cuestan las escobas y los traperos para una empresa?",
        "necesito un pedido grande para un hotel, por favor que me contacte un asesor",
        "manejan papel higienico jumbo?",
        "quiero pedir 3 cajas de vasos desechables de 7 onzas"
    ];

    // --- CATEGORÍA 3: Mensajes sin Relación, Extraños o Vulgares ---
    $irrelevantMessages = [
        "juepucha que precios tan caros",
        "mk necesito un domicilio ya",
        "ustedes venden unicornios?",
        "quiero una pizza con piña",
        "cuál es el sentido de la vida?",
        "ayer vi un ovni en Salento",
        "necesito la receta de las arepas",
        "deja de responderme como un robot",
        "<html><head></head><body><h1>Hola</h1></body></html>",
        "busco servicios de SEO para mi pagina"
    ];

    // Se combinan todas las categorías en una sola lista para elegir aleatoriamente
    $allMessages = array_merge($generalGreetings, $orderRequests, $irrelevantMessages);

    // --- Estructura del Webhook (igual que antes) ---
    $senderPhone = '573' . rand(10, 22) . rand(1000000, 9999999);
    
    return [
        "body" => [
            "sender" => $senderPhone . '@s.whatsapp.net',
            "data" => [
                "pushName" => $pushNames[array_rand($pushNames)],
                "message" => ["conversation" => $allMessages[array_rand($allMessages)]]
            ]
        ]
    ];
}

// ==================================================================
// MOTOR DE ENVÍO Y REGISTRO
// ==================================================================

echo "🚀 Iniciando proceso de envío masivo de webhooks..." . PHP_EOL;

for ($i = 1; $i <= $numberOfRequests; $i++) {
    
    $fullDataObject = generateComplexWebhookData();
    $payloadToSend = $fullDataObject['body'];
    
    // --- INICIA LA CORRECCIÓN ---

    // 1. Extrae los datos clave para el log del cliente
    $conversationId = $payloadToSend['sender'];
    $message = $payloadToSend['data']['message']['conversation'];

    // 2. Prepara la entrada para el log del cliente (mensaje saliente)
    $logEntry = [
        'timestamp' => time(),
        'conversationId' => $conversationId,
        'message' => $message,
        'direction' => 'outgoing', // <-- La clave: marca el mensaje como tuyo
        'metadata' => ['client' => 'PHP Test Script', 'test_run' => date("Y-m-d H:i:s")]
    ];

    // 3. Guarda el mensaje del cliente en el archivo de logs
    file_put_contents(__DIR__ . '/agent_responses.log', json_encode($logEntry) . PHP_EOL, FILE_APPEND);
    
    // --- TERMINA LA CORRECCIÓN ---

    // 4. Envía el webhook a n8n para que el agente responda
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payloadToSend));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    echo "Enviando solicitud #{$i}/{$numberOfRequests}... ";
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        echo "❌ Error de cURL: " . curl_error($ch) . PHP_EOL;
    } else {
        echo "✅ Recibido. Código HTTP: {$httpCode}" . PHP_EOL;
    }
    
    usleep(200000); // Pausa de 0.2 segundos
}

echo "🎉 Proceso completado." . PHP_EOL;
?>
p

