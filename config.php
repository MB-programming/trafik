<?php
// ── Admin credentials ──────────────────────────────────────────────────────
define('ADMIN_USER', 'minaboules');
define('ADMIN_PASS', 'mina2002306');

// ── Currency ───────────────────────────────────────────────────────────────
define('CURRENCY_LABEL',  '€');      // عرض العملة
define('CURRENCY_NAME',   'يورو');   // الاسم الكامل

// ── AI Provider ────────────────────────────────────────────────────────────
// 'gemini' = مجاني | 'claude' = مدفوع | '' = بدون AI (باركود فقط)
define('AI_PROVIDER',       'gemini');
define('GEMINI_API_KEY',    'AIzaSyBoVAVNh7TIZ7dotXG-IZopp65Fv7Zrrls');
define('ANTHROPIC_API_KEY', '');

// ── Default categories ─────────────────────────────────────────────────────
define('DEFAULT_CATEGORIES', [
    ['name'=>'غذائية',  'icon'=>'fa-basket-shopping'],
    ['name'=>'مشروبات', 'icon'=>'fa-bottle-water'],
    ['name'=>'سجاير',   'icon'=>'fa-smoking'],
    ['name'=>'منوعات',  'icon'=>'fa-box-open'],
]);
