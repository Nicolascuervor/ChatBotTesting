<?php

function processWebhook($data){
    if (isset($data["order_id"])) {
        $orderId = $data["order_id"];
        $customerName = $data["customer_name"];
        $totalAmount = $data["total_amount"];

        file_put_contents(__DIR__ . "/../logs/webhook_log.txt", "Procesando pedido: ID $orderId, Cliente $customerName, Total $totalAmount" . PHP_EOL, FILE_APPEND);
    }
}