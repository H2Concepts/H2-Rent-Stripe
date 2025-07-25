<?php
// Datei: /includes/webhook/entry.php

// Beende HTTP-Verbindung sofort
ignore_user_abort(true);

register_shutdown_function(function () {
    require __DIR__ . '/handler.php';
});
