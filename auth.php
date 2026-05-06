<?php
require_once __DIR__ . '/config.php';

function requireLogin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['admin'])) {
        header('Location: login.php');
        exit;
    }
}

function isLoggedIn(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return !empty($_SESSION['admin']);
}
