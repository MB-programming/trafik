<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function json_out(array $data): void {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function validCategory(string $cat): string {
    return in_array($cat, CATEGORIES) ? $cat : 'منوعات';
}

// ── Barcode lookup ─────────────────────────────────────────────────────────
if ($action === 'lookup') {
    $barcode = trim($_GET['barcode'] ?? '');
    if (!$barcode) json_out(['found' => false]);
    $db  = getDB();
    $st  = $db->prepare('SELECT * FROM products WHERE barcode = ?');
    $st->execute([$barcode]);
    $row = $st->fetch();
    if ($row) json_out(['found' => true, 'id' => $row['id'], 'name' => $row['name'],
                        'price' => $row['price'], 'category' => $row['category'],
                        'barcode' => $row['barcode']]);
    json_out(['found' => false, 'barcode' => $barcode]);
}

// ── Product search by name ─────────────────────────────────────────────────
if ($action === 'search') {
    $q  = trim($_GET['q'] ?? '');
    $db = getDB();
    $st = $db->prepare('SELECT * FROM products WHERE name LIKE ? OR barcode LIKE ? ORDER BY name LIMIT 20');
    $st->execute(["%$q%", "%$q%"]);
    json_out(['results' => $st->fetchAll()]);
}

// ── Image identify via Claude Vision ───────────────────────────────────────
if ($action === 'identify') {
    if (!ANTHROPIC_API_KEY) json_out(['found' => false, 'error' => 'no_api_key']);
    $body     = json_decode(file_get_contents('php://input'), true);
    $imageB64 = $body['image'] ?? '';
    if (!$imageB64) json_out(['found' => false, 'error' => 'no_image']);
    if (str_contains($imageB64, ',')) $imageB64 = explode(',', $imageB64, 2)[1];

    $payload = ['model' => 'claude-haiku-4-5-20251001', 'max_tokens' => 120,
        'messages' => [['role' => 'user', 'content' => [
            ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/jpeg', 'data' => $imageB64]],
            ['type' => 'text',  'text'   => 'ما هو المنتج أو العلامة التجارية في هذه الصورة؟ أجب فقط باسم المنتج بدون أي شرح، لا تتجاوز 10 كلمات.'],
        ]]]];

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['x-api-key: '.ANTHROPIC_API_KEY, 'anthropic-version: 2023-06-01', 'content-type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload)]);
    $resp = curl_exec($ch); $err = curl_error($ch); curl_close($ch);
    if ($err) json_out(['found' => false, 'error' => $err]);

    $identified = trim(json_decode($resp, true)['content'][0]['text'] ?? '');
    if (!$identified) json_out(['found' => false, 'error' => 'empty_response']);

    $db  = getDB();
    $st  = $db->prepare('SELECT * FROM products WHERE LOWER(name) LIKE LOWER(?) LIMIT 1');
    $st->execute(['%'.$identified.'%']); $row = $st->fetch();
    if (!$row) {
        foreach (explode(' ', $identified) as $word) {
            if (mb_strlen($word) < 3) continue;
            $st->execute(['%'.$word.'%']); $row = $st->fetch();
            if ($row) break;
        }
    }
    if ($row) json_out(['found' => true, 'id' => $row['id'], 'name' => $row['name'],
                        'price' => $row['price'], 'category' => $row['category'], 'identified_as' => $identified]);
    json_out(['found' => false, 'identified_as' => $identified]);
}

// ── Create order ───────────────────────────────────────────────────────────
if ($action === 'create_order') {
    $body  = json_decode(file_get_contents('php://input'), true);
    $items = $body['items'] ?? [];
    $notes = trim($body['notes'] ?? '');
    if (empty($items)) json_out(['ok' => false, 'error' => 'no items']);

    $total = 0;
    foreach ($items as $item) $total += ($item['price'] * $item['qty']);

    $db = getDB();
    $db->beginTransaction();
    $db->prepare('INSERT INTO orders (total, notes) VALUES (?, ?)')->execute([$total, $notes]);
    $orderId = $db->lastInsertId();
    $st = $db->prepare('INSERT INTO order_items (order_id, product_id, barcode, name, price, qty) VALUES (?,?,?,?,?,?)');
    foreach ($items as $item) {
        $st->execute([$orderId, $item['id'] ?? null, $item['barcode'] ?? '', $item['name'], $item['price'], $item['qty']]);
    }
    $db->commit();
    json_out(['ok' => true, 'order_id' => $orderId, 'total' => $total]);
}

// ── Delete order ───────────────────────────────────────────────────────────
if ($action === 'delete_order') {
    $id = (int)($_POST['id'] ?? 0);
    $db = getDB();
    $db->prepare('DELETE FROM order_items WHERE order_id=?')->execute([$id]);
    $db->prepare('DELETE FROM orders WHERE id=?')->execute([$id]);
    json_out(['ok' => true]);
}

// ── Products CRUD ──────────────────────────────────────────────────────────
if ($action === 'add') {
    $barcode  = trim($_POST['barcode']  ?? '');
    $name     = trim($_POST['name']     ?? '');
    $price    = (float)($_POST['price'] ?? 0);
    $category = validCategory($_POST['category'] ?? '');
    if (!$barcode || !$name) json_out(['ok' => false, 'error' => 'missing fields']);
    $db = getDB();
    $db->prepare('INSERT OR REPLACE INTO products (barcode, name, price, category) VALUES (?,?,?,?)')->execute([$barcode, $name, $price, $category]);
    json_out(['ok' => true, 'id' => $db->lastInsertId()]);
}

if ($action === 'edit') {
    $id = (int)($_POST['id'] ?? 0);
    $db = getDB();
    $db->prepare('UPDATE products SET barcode=?, name=?, price=?, category=? WHERE id=?')
       ->execute([trim($_POST['barcode']??''), trim($_POST['name']??''), (float)($_POST['price']??0), validCategory($_POST['category']??''), $id]);
    json_out(['ok' => true]);
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    getDB()->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
    json_out(['ok' => true]);
}

json_out(['error' => 'unknown action']);
