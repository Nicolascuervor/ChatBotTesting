<?php

$logFile = __DIR__ . '/../logs/webhook_log.txt';
$logs = file_get_contents($logFile);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webhook Notificaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body class="bg-dark">
    <div class="container mt-5">
        <h1 class="text-secondary">Webhook de Notificaciones</h1>
        <pre class="bg-light p-3"><?php echo htmlspecialchars($logs ?: "No se han recibido notificaciones aún."); ?></pre>
    </div>
</body>
</html>