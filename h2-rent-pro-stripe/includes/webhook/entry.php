<?php
// Datei: /includes/webhook/entry.php

// Beende HTTP-Verbindung sofort, damit Stripe nicht auf Verarbeitungszeit wartet
ignore_user_abort(true);

// Direkt den Handler ausführen (nicht per register_shutdown_function)
require __DIR__ . '/handler.php';