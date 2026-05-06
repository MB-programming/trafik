<?php
// ── Admin credentials ──────────────────────────────────────────────────────
define('ADMIN_USER', 'minaboules');
define('ADMIN_PASS', 'mina2002306');

// ── AI Provider ────────────────────────────────────────────────────────────
// خيارات: 'gemini' (مجاني) | 'claude' | '' (بدون AI - باركود فقط)
define('AI_PROVIDER', '');

// Gemini مجاني — احصل على مفتاحك من: https://aistudio.google.com/apikey
define('GEMINI_API_KEY', '');

// Claude (مدفوع) — من: https://console.anthropic.com
define('ANTHROPIC_API_KEY', '');

// ── Default categories ─────────────────────────────────────────────────────
define('DEFAULT_CATEGORIES', [
    ['name'=>'غذائية',  'icon'=>'🥫'],
    ['name'=>'مشروبات', 'icon'=>'🧃'],
    ['name'=>'سجاير',   'icon'=>'🚬'],
    ['name'=>'منوعات',  'icon'=>'📦'],
]);
