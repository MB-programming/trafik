<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['admin'])) { header('Location: pos.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    if ($user === ADMIN_USER && $pass === ADMIN_PASS) {
        $_SESSION['admin'] = true;
        header('Location: pos.php');
        exit;
    }
    $error = 'اسم المستخدم أو كلمة المرور غلط';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>تسجيل الدخول - ترافيك</title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{
      font-family:'Segoe UI',Tahoma,sans-serif;
      background:linear-gradient(135deg,#1e3a8a 0%,#1d4ed8 50%,#2563eb 100%);
      min-height:100dvh;display:flex;align-items:center;justify-content:center;padding:16px;
    }
    .card{
      background:#fff;border-radius:20px;padding:36px 30px;
      width:min(380px,100%);box-shadow:0 20px 60px rgba(0,0,0,.25);
    }
    .logo{text-align:center;margin-bottom:24px}
    .logo h1{font-size:2rem;color:#2563eb;font-weight:900}
    .logo p{color:#64748b;font-size:.9rem;margin-top:4px}

    label{display:block;font-size:.85rem;font-weight:600;color:#374151;margin-bottom:5px}
    input{
      width:100%;padding:12px 14px;border:2px solid #e2e8f0;border-radius:10px;
      font-size:1rem;margin-bottom:14px;transition:.15s;
    }
    input:focus{outline:none;border-color:#2563eb}
    .btn-login{
      width:100%;padding:13px;background:#2563eb;color:#fff;border:none;
      border-radius:10px;font-size:1.05rem;font-weight:800;cursor:pointer;
      transition:.15s;
    }
    .btn-login:hover{background:#1d4ed8}
    .error{
      background:#fee2e2;color:#991b1b;padding:10px 14px;
      border-radius:8px;font-size:.88rem;margin-bottom:14px;text-align:center;
    }
  </style>
</head>
<body>
<div class="card">
  <div class="logo">
    <h1>🛒 ترافيك</h1>
    <p>نظام نقطة البيع</p>
  </div>
  <?php if($error): ?>
  <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif ?>
  <form method="post">
    <label>اسم المستخدم</label>
    <input type="text" name="username" required autofocus value="<?= htmlspecialchars($_POST['username']??'') ?>"/>
    <label>كلمة المرور</label>
    <input type="password" name="password" required/>
    <button class="btn-login" type="submit">دخول</button>
  </form>
</div>
</body>
</html>
