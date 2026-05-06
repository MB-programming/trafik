<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── Helpers ────────────────────────────────────────────────────────────────

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

    if ($row) {
        json_out(['found' => true, 'name' => $row['name'],
                  'price' => $row['price'], 'category' => $row['category'],
                  'barcode' => $row['barcode']]);
    }
    json_out(['found' => false, 'barcode' => $barcode]);
}

// ── Image identify via Claude Vision ───────────────────────────────────────
if ($action === 'identify') {
    if (!ANTHROPIC_API_KEY) json_out(['found' => false, 'error' => 'no_api_key']);

    $body      = json_decode(file_get_contents('php://input'), true);
    $imageB64  = $body['image'] ?? '';
    if (!$imageB64) json_out(['found' => false, 'error' => 'no_image']);

    // strip data-URL prefix
    if (str_contains($imageB64, ',')) {
        $imageB64 = explode(',', $imageB64, 2)[1];
    }

    $payload = [
        'model'      => 'claude-haiku-4-5-20251001',
        'max_tokens' => 120,
        'messages'   => [[
            'role'    => 'user',
            'content' => [
                [
                    'type'   => 'image',
                    'source' => [
                        'type'       => 'base64',
                        'media_type' => 'image/jpeg',
                        'data'       => $imageB64,
                    ],
                ],
                [
                    'type' => 'text',
                    'text' => 'ما هو المنتج أو العلامة التجارية في هذه الصورة؟ أجب فقط باسم المنتج بدون أي شرح، لا تتجاوز 10 كلمات.',
                ],
            ],
        ]],
    ];

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => [
            'x-api-key: '          . ANTHROPIC_API_KEY,
            'anthropic-version: 2023-06-01',
            'content-type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
    ]);

    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err) json_out(['found' => false, 'error' => $err]);

    $result     = json_decode($resp, true);
    $identified = trim($result['content'][0]['text'] ?? '');

    if (!$identified) json_out(['found' => false, 'error' => 'empty_response']);

    // search DB
    $db  = getDB();
    $row = null;

    $st = $db->prepare('SELECT * FROM products WHERE LOWER(name) LIKE LOWER(?) LIMIT 1');
    $st->execute(['%' . $identified . '%']);
    $row = $st->fetch();

    if (!$row) {
        foreach (explode(' ', $identified) as $word) {
            if (mb_strlen($word) < 3) continue;
            $st->execute(['%' . $word . '%']);
            $row = $st->fetch();
            if ($row) break;
        }
    }

    if ($row) {
        json_out(['found' => true, 'name' => $row['name'],
                  'price' => $row['price'], 'category' => $row['category'],
                  'identified_as' => $identified]);
    }
    json_out(['found' => false, 'identified_as' => $identified]);
}

// ── Add product ────────────────────────────────────────────────────────────
if ($action === 'add') {
    $barcode  = trim($_POST['barcode']  ?? '');
    $name     = trim($_POST['name']     ?? '');
    $price    = (float)($_POST['price'] ?? 0);
    $category = validCategory($_POST['category'] ?? '');

    if (!$barcode || !$name) json_out(['ok' => false, 'error' => 'missing fields']);

    $db = getDB();
    $st = $db->prepare('INSERT OR REPLACE INTO products (barcode, name, price, category) VALUES (?,?,?,?)');
    $st->execute([$barcode, $name, $price, $category]);
    json_out(['ok' => true, 'id' => $db->lastInsertId()]);
}

// ── Edit product ───────────────────────────────────────────────────────────
if ($action === 'edit') {
    $id       = (int)($_POST['id']      ?? 0);
    $barcode  = trim($_POST['barcode']  ?? '');
    $name     = trim($_POST['name']     ?? '');
    $price    = (float)($_POST['price'] ?? 0);
    $category = validCategory($_POST['category'] ?? '');

    $db = getDB();
    $st = $db->prepare('UPDATE products SET barcode=?, name=?, price=?, category=? WHERE id=?');
    $st->execute([$barcode, $name, $price, $category, $id]);
    json_out(['ok' => true]);
}

// ── Delete product ─────────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $db = getDB();
    $db->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
    json_out(['ok' => true]);
}

json_out(['error' => 'unknown action']);
