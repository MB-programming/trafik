<?php
function getDB(): PDO {
    $path = __DIR__ . '/products.db';
    $db   = new PDO('sqlite:' . $path);
    $db->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $db;
}

function initDB(): void {
    $db = getDB();
    $db->exec("
        CREATE TABLE IF NOT EXISTS products (
            id       INTEGER PRIMARY KEY AUTOINCREMENT,
            barcode  TEXT    NOT NULL UNIQUE,
            name     TEXT    NOT NULL,
            price    REAL    NOT NULL,
            category TEXT    NOT NULL DEFAULT 'منوعات'
        )
    ");
}

initDB();
