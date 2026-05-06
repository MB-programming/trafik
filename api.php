<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// scanner/lookup endpoints are public; write operations require login
$publicActions = ['lookup','search','identify'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';
if (!in_array($action, $publicActions)) requireLogin();

header('Content-Type: application/json; charset=utf-8');

function json_out(array $data): void {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function validCategory(string $cat): string {
    $db  = getDB();
    $row = $db->prepare('SELECT name FROM categories WHERE name=?');
    $row->execute([$cat]);
    return $row->fetchColumn() ?: 'منوعات';
}

function saveImage(array $file, int $productId): string {
    if ($file['error'] !== UPLOAD_ERR_OK) return '';
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) return '';
    if ($file['size'] > 5 * 1024 * 1024) return '';
    $dir  = __DIR__ . '/uploads/';
    $name = 'prod_' . $productId . '_' . time() . '.' . $ext;
    $dest = $dir . $name;
    if (move_uploaded_file($file['tmp_name'], $dest)) return 'uploads/' . $name;
    return '';
}

// ── Product lookup ─────────────────────────────────────────────────────────
if ($action === 'lookup') {
    $barcode = trim($_GET['barcode'] ?? '');
    if (!$barcode) json_out(['found'=>false]);
    $st = getDB()->prepare('SELECT * FROM products WHERE barcode=?');
    $st->execute([$barcode]);
    $row = $st->fetch();
    if ($row) json_out(['found'=>true] + $row);
    json_out(['found'=>false,'barcode'=>$barcode]);
}

// ── Product search ─────────────────────────────────────────────────────────
if ($action === 'search') {
    $q     = trim($_GET['q']     ?? '');
    $cat   = trim($_GET['cat']   ?? '');
    $brand = trim($_GET['brand'] ?? '');
    $db    = getDB();
    $sql   = 'SELECT * FROM products WHERE (name LIKE ? OR barcode LIKE ?)';
    $p     = ["%$q%", "%$q%"];
    if ($cat   && $cat   !== 'الكل') { $sql .= ' AND category=?'; $p[] = $cat;   }
    if ($brand && $brand !== 'الكل') { $sql .= ' AND brand=?';    $p[] = $brand; }
    $sql .= ' ORDER BY name LIMIT 60';
    $st = $db->prepare($sql); $st->execute($p);
    json_out(['results' => $st->fetchAll()]);
}

// ── AI identify ────────────────────────────────────────────────────────────
if ($action === 'identify') {
    $provider = AI_PROVIDER;
    if (!$provider) json_out(['found'=>false,'error'=>'no_provider']);

    $body     = json_decode(file_get_contents('php://input'), true);
    $imageB64 = $body['image'] ?? '';
    if (!$imageB64) json_out(['found'=>false,'error'=>'no_image']);
    if (str_contains($imageB64, ',')) $imageB64 = explode(',', $imageB64, 2)[1];

    $prompt = 'ما هو المنتج في هذه الصورة؟ أجب فقط باسم المنتج أو البراند بدون أي شرح إضافي، لا تتجاوز 10 كلمات.';
    $identified = '';

    if ($provider === 'gemini') {
        if (!GEMINI_API_KEY) json_out(['found'=>false,'error'=>'no_gemini_key']);
        $payload = ['contents'=>[['parts'=>[
            ['inline_data'=>['mime_type'=>'image/jpeg','data'=>$imageB64]],
            ['text'=>$prompt],
        ]]],'generationConfig'=>['maxOutputTokens'=>80]];
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key='.GEMINI_API_KEY;
        $ch  = curl_init($url);
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_TIMEOUT=>20,
            CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
            CURLOPT_POSTFIELDS=>json_encode($payload)]);
        $resp = curl_exec($ch); $err = curl_error($ch); curl_close($ch);
        if ($err) json_out(['found'=>false,'error'=>$err]);
        $identified = trim(json_decode($resp,true)['candidates'][0]['content']['parts'][0]['text'] ?? '');

    } elseif ($provider === 'claude') {
        if (!ANTHROPIC_API_KEY) json_out(['found'=>false,'error'=>'no_claude_key']);
        $payload = ['model'=>'claude-haiku-4-5-20251001','max_tokens'=>80,'messages'=>[['role'=>'user','content'=>[
            ['type'=>'image','source'=>['type'=>'base64','media_type'=>'image/jpeg','data'=>$imageB64]],
            ['type'=>'text','text'=>$prompt],
        ]]]];
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_TIMEOUT=>20,
            CURLOPT_HTTPHEADER=>['x-api-key: '.ANTHROPIC_API_KEY,'anthropic-version: 2023-06-01','content-type: application/json'],
            CURLOPT_POSTFIELDS=>json_encode($payload)]);
        $resp = curl_exec($ch); $err = curl_error($ch); curl_close($ch);
        if ($err) json_out(['found'=>false,'error'=>$err]);
        $identified = trim(json_decode($resp,true)['content'][0]['text'] ?? '');
    }

    if (!$identified) json_out(['found'=>false,'error'=>'empty_response']);

    $db = getDB();
    $st = $db->prepare('SELECT * FROM products WHERE LOWER(name) LIKE LOWER(?) LIMIT 1');
    $st->execute(['%'.$identified.'%']); $row = $st->fetch();
    if (!$row) { foreach(explode(' ',$identified) as $w) { if(mb_strlen($w)<3) continue; $st->execute(['%'.$w.'%']); $row=$st->fetch(); if($row) break; } }
    if ($row) json_out(['found'=>true,'identified_as'=>$identified]+$row);
    json_out(['found'=>false,'identified_as'=>$identified]);
}

// ── Categories ─────────────────────────────────────────────────────────────
if ($action === 'add_category') {
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '📦');
    if (!$name) json_out(['ok'=>false,'error'=>'اسم القسم مطلوب']);
    $db = getDB();
    $max = (int)$db->query('SELECT COALESCE(MAX(sort),0) FROM categories')->fetchColumn();
    $db->prepare('INSERT OR IGNORE INTO categories (name,icon,sort) VALUES (?,?,?)')->execute([$name,$icon,$max+1]);
    json_out(['ok'=>true,'id'=>$db->lastInsertId()]);
}

if ($action === 'edit_category') {
    $id   = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '📦');
    if (!$name || !$id) json_out(['ok'=>false]);
    $db = getDB();
    // update products that use old name
    $old = $db->prepare('SELECT name FROM categories WHERE id=?'); $old->execute([$id]); $oldName=$old->fetchColumn();
    $db->prepare('UPDATE categories SET name=?,icon=? WHERE id=?')->execute([$name,$icon,$id]);
    if ($oldName && $oldName !== $name) $db->prepare('UPDATE products SET category=? WHERE category=?')->execute([$name,$oldName]);
    json_out(['ok'=>true]);
}

if ($action === 'delete_category') {
    $id = (int)($_POST['id'] ?? 0);
    $db = getDB();
    $name = $db->prepare('SELECT name FROM categories WHERE id=?'); $name->execute([$id]); $name=$name->fetchColumn();
    $db->prepare('DELETE FROM categories WHERE id=?')->execute([$id]);
    if ($name) $db->prepare("UPDATE products SET category='منوعات' WHERE category=?")->execute([$name]);
    json_out(['ok'=>true]);
}

if ($action === 'list_categories') {
    $cats = getDB()->query('SELECT * FROM categories ORDER BY sort,id')->fetchAll();
    json_out(['categories'=>$cats]);
}

// ── Brands ─────────────────────────────────────────────────────────────────
if ($action === 'add_brand') {
    $name = trim($_POST['name'] ?? '');
    if (!$name) json_out(['ok'=>false,'error'=>'اسم البراند مطلوب']);
    $db  = getDB();
    $max = (int)$db->query('SELECT COALESCE(MAX(sort),0) FROM brands')->fetchColumn();
    $db->prepare('INSERT OR IGNORE INTO brands (name,sort) VALUES (?,?)')->execute([$name,$max+1]);
    json_out(['ok'=>true,'id'=>$db->lastInsertId()]);
}

if ($action === 'edit_brand') {
    $id   = (int)($_POST['id']   ?? 0);
    $name = trim($_POST['name']  ?? '');
    if (!$name || !$id) json_out(['ok'=>false]);
    $db  = getDB();
    $old = $db->prepare('SELECT name FROM brands WHERE id=?'); $old->execute([$id]); $oldName=$old->fetchColumn();
    $db->prepare('UPDATE brands SET name=? WHERE id=?')->execute([$name,$id]);
    if ($oldName && $oldName !== $name) $db->prepare('UPDATE products SET brand=? WHERE brand=?')->execute([$name,$oldName]);
    json_out(['ok'=>true]);
}

if ($action === 'delete_brand') {
    $id = (int)($_POST['id'] ?? 0);
    $db = getDB();
    $name = $db->prepare('SELECT name FROM brands WHERE id=?'); $name->execute([$id]); $name=$name->fetchColumn();
    $db->prepare('DELETE FROM brands WHERE id=?')->execute([$id]);
    if ($name) $db->prepare("UPDATE products SET brand='' WHERE brand=?")->execute([$name]);
    json_out(['ok'=>true]);
}

if ($action === 'list_brands') {
    $brands = getDB()->query('SELECT * FROM brands ORDER BY sort,id')->fetchAll();
    json_out(['brands'=>$brands]);
}

// ── Products CRUD ──────────────────────────────────────────────────────────
if ($action === 'add') {
    $name     = trim($_POST['name']     ?? '');
    $price    = (float)($_POST['price'] ?? 0);
    $category = validCategory($_POST['category'] ?? '');
    $brand    = trim($_POST['brand']    ?? '');
    $barcode  = trim($_POST['barcode']  ?? '') ?: 'AUTO-'.uniqid();
    if (!$name) json_out(['ok'=>false,'error'=>'اسم المنتج مطلوب']);
    $db = getDB();
    $db->prepare('INSERT OR REPLACE INTO products (barcode,name,price,category,brand) VALUES (?,?,?,?,?)')->execute([$barcode,$name,$price,$category,$brand]);
    $pid = $db->lastInsertId();
    $img = '';
    if (!empty($_FILES['image']['name'])) { $img = saveImage($_FILES['image'], $pid); if($img) $db->prepare('UPDATE products SET image_path=? WHERE id=?')->execute([$img,$pid]); }
    json_out(['ok'=>true,'id'=>$pid,'image_path'=>$img]);
}

if ($action === 'edit') {
    $id       = (int)($_POST['id']      ?? 0);
    $barcode  = trim($_POST['barcode']  ?? '');
    $name     = trim($_POST['name']     ?? '');
    $price    = (float)($_POST['price'] ?? 0);
    $category = validCategory($_POST['category'] ?? '');
    $brand    = trim($_POST['brand']    ?? '');
    $db = getDB();
    $db->prepare('UPDATE products SET barcode=?,name=?,price=?,category=?,brand=? WHERE id=?')->execute([$barcode,$name,$price,$category,$brand,$id]);
    $img = '';
    if (!empty($_FILES['image']['name'])) { $img = saveImage($_FILES['image'], $id); if($img) $db->prepare('UPDATE products SET image_path=? WHERE id=?')->execute([$img,$id]); }
    json_out(['ok'=>true,'image_path'=>$img]);
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $db = getDB();
    $row = $db->prepare('SELECT image_path FROM products WHERE id=?'); $row->execute([$id]); $row=$row->fetch();
    if ($row && $row['image_path'] && file_exists(__DIR__.'/'.$row['image_path'])) @unlink(__DIR__.'/'.$row['image_path']);
    $db->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
    json_out(['ok'=>true]);
}

// ── Orders ─────────────────────────────────────────────────────────────────
if ($action === 'create_order') {
    $body = json_decode(file_get_contents('php://input'),true);
    $items = $body['items'] ?? []; $notes = trim($body['notes'] ?? '');
    if (empty($items)) json_out(['ok'=>false,'error'=>'السلة فاضية']);
    $total = array_sum(array_map(fn($i)=>$i['price']*$i['qty'], $items));
    $db = getDB(); $db->beginTransaction();
    $db->prepare('INSERT INTO orders (total,notes) VALUES (?,?)')->execute([$total,$notes]);
    $oid = $db->lastInsertId();
    $st  = $db->prepare('INSERT INTO order_items (order_id,product_id,barcode,name,price,qty) VALUES (?,?,?,?,?,?)');
    foreach ($items as $i) $st->execute([$oid,$i['id']??null,$i['barcode']??'',$i['name'],$i['price'],$i['qty']]);
    $db->commit();
    json_out(['ok'=>true,'order_id'=>$oid,'total'=>$total]);
}

if ($action === 'delete_order') {
    $id = (int)($_POST['id'] ?? 0);
    $db = getDB();
    $db->prepare('DELETE FROM order_items WHERE order_id=?')->execute([$id]);
    $db->prepare('DELETE FROM orders WHERE id=?')->execute([$id]);
    json_out(['ok'=>true]);
}

json_out(['error'=>'unknown action']);
