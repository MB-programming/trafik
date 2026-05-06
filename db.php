<?php
function getDB(): PDO {
    $db = new PDO('sqlite:' . __DIR__ . '/products.db');
    $db->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec('PRAGMA foreign_keys = ON');
    return $db;
}

function initDB(): void {
    require_once __DIR__ . '/config.php';
    $db = getDB();

    $db->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id   INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT    NOT NULL UNIQUE,
            icon TEXT    NOT NULL DEFAULT '📦',
            sort INTEGER NOT NULL DEFAULT 0
        );

        CREATE TABLE IF NOT EXISTS brands (
            id   INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT    NOT NULL UNIQUE,
            sort INTEGER NOT NULL DEFAULT 0
        );

        CREATE TABLE IF NOT EXISTS products (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            barcode    TEXT    NOT NULL UNIQUE,
            name       TEXT    NOT NULL,
            price      REAL    NOT NULL,
            category   TEXT    NOT NULL DEFAULT 'منوعات',
            brand      TEXT    NOT NULL DEFAULT '',
            image_path TEXT    NOT NULL DEFAULT ''
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

    // migrations for older schemas
    try { $db->exec("ALTER TABLE products ADD COLUMN image_path TEXT NOT NULL DEFAULT ''"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE products ADD COLUMN brand TEXT NOT NULL DEFAULT ''"); } catch (Exception $e) {}

    // seed default categories if empty
    $count = (int)$db->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    if ($count === 0) {
        $st = $db->prepare('INSERT OR IGNORE INTO categories (name, icon, sort) VALUES (?,?,?)');
        foreach (DEFAULT_CATEGORIES as $i => $c) {
            $st->execute([$c['name'], $c['icon'], $i]);
        }
    }
}

initDB();
