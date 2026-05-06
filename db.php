<?php
function getDB(): PDO {
    $db = new PDO('sqlite:' . __DIR__ . '/products.db');
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
        );

        CREATE TABLE IF NOT EXISTS orders (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            created_at TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
            total      REAL    NOT NULL,
            notes      TEXT    DEFAULT ''
        );

        CREATE TABLE IF NOT EXISTS order_items (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id   INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
            product_id INTEGER,
            barcode    TEXT,
            name       TEXT    NOT NULL,
            price      REAL    NOT NULL,
            qty        INTEGER NOT NULL DEFAULT 1
        );
    ");
}

initDB();
